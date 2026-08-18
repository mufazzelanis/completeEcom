<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Every upload path in the admin/vendor panels (banners, product/category/brand images,
 * logos) previously just did `$file->store(...)` with no processing — a raw camera-resolution
 * photo (multi-MB) could end up served as-is on the storefront. A single ~1.9MB unresized hero
 * banner — a photo saved as PNG, with no transparency — was directly responsible for a 22s
 * Largest Contentful Paint in production.
 *
 * This resizes (never upscales) to a sane max dimension and re-encodes at a reasonable quality,
 * converting to WebP (smaller than both JPEG and PNG, and — unlike JPEG — still supports
 * transparency, so this covers photos and transparent logos/icons alike) whenever GD can encode
 * it, which on any reasonably current PHP build is always. GIFs are stored untouched, since GD's
 * GIF decoder only reads the first frame and would silently kill any animation.
 */
class ImageOptimizer
{
    /**
     * Set whenever optimize() falls back to null because of a caught exception (not because
     * GD simply doesn't handle the format) — images:optimize surfaces this so a failure isn't
     * silently indistinguishable from "nothing to do here".
     */
    public static ?string $lastError = null;

    /**
     * Resize + re-encode an uploaded image and store it, returning the stored path — a drop-in
     * replacement for `$file->store($directory, $disk)`.
     */
    public static function store(UploadedFile $file, string $directory, string $disk = 'public', int $maxDimension = 1600, int $quality = 82): string
    {
        $result = self::optimize($file->getRealPath(), $file->getMimeType(), $maxDimension, $quality);

        $extension = $result['extension'] ?? ($file->getClientOriginalExtension() ?: $file->extension());
        $bytes = $result['bytes'] ?? file_get_contents($file->getRealPath());
        $path = trim($directory, '/') . '/' . Str::random(40) . '.' . $extension;

        Storage::disk($disk)->put($path, $bytes);

        return $path;
    }

    /**
     * Returns ['bytes' => string, 'extension' => string], or null if the file isn't a format
     * GD can safely round-trip (falls back to storing the original untouched). Public so
     * `images:optimize` can re-run this against files that are already on disk, not just
     * fresh uploads.
     */
    public static function optimize(string $path, ?string $mime, int $maxDimension, int $quality): ?array
    {
        self::$lastError = null;

        if (! extension_loaded('gd')) {
            self::$lastError = 'the GD extension is not loaded';
            return null;
        }

        try {
            $image = match ($mime) {
                'image/jpeg' => imagecreatefromjpeg($path),
                'image/png'  => imagecreatefrompng($path),
                'image/webp' => function_exists('imagecreatefromwebp') ? imagecreatefromwebp($path) : null,
                default      => null, // gif (animation-unsafe), svg, avif, etc. — leave untouched
            };

            if (! $image) {
                // A recognized mime whose GD decoder still returned false is an actual
                // failure (corrupt file, unsupported PNG color mode, etc.) worth surfacing —
                // as opposed to a format we intentionally never attempt (gif, svg, ...).
                if (in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
                    self::$lastError = "GD couldn't decode this {$mime} file";
                }
                return null;
            }

            $width = imagesx($image);
            $height = imagesy($image);
            $scale = min(1, $maxDimension / max($width, $height));

            if ($scale < 1) {
                $newWidth = max(1, (int) round($width * $scale));
                $newHeight = max(1, (int) round($height * $scale));
                $resized = imagecreatetruecolor($newWidth, $newHeight);

                // Preserve transparency while resizing instead of flattening it to black —
                // whether the output actually keeps it depends on the PNG-vs-JPEG decision below.
                imagealphablending($resized, false);
                imagesavealpha($resized, true);
                $transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
                imagefilledrectangle($resized, 0, 0, $newWidth, $newHeight, $transparent);

                imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                imagedestroy($image);
                $image = $resized;
                $width = $newWidth;
                $height = $newHeight;
            }

            // WebP beats both JPEG and PNG for basically any content — photos *and* the
            // transparency PNG was carrying — so it's always the preferred output when GD
            // can encode it (near-universal browser support today; every mainstream browser
            // has supported it for years). Only fall back to the old JPEG/PNG choice on a GD
            // build without WebP encoding, where a non-transparent PNG still becomes a JPEG
            // (PNG is a poor format for photographic content — lossless can't touch what
            // lossy JPEG/WebP does with gradients/photos) and a transparent one stays PNG.
            if (function_exists('imagewebp')) {
                $outputMime = 'image/webp';
                imagesavealpha($image, true);
            } elseif ($mime === 'image/png' && ! self::hasTransparency($image, $width, $height)) {
                $outputMime = 'image/jpeg';
                $flattened = imagecreatetruecolor($width, $height);
                imagefill($flattened, 0, 0, imagecolorallocate($flattened, 255, 255, 255));
                imagecopy($flattened, $image, 0, 0, 0, 0, $width, $height);
                imagedestroy($image);
                $image = $flattened;
            } else {
                $outputMime = $mime;
            }

            ob_start();
            match ($outputMime) {
                'image/jpeg' => imagejpeg($image, null, $quality),
                'image/png'  => imagepng($image, null, 9), // lossless either way — always max compression
                'image/webp' => imagewebp($image, null, $quality),
            };
            $bytes = ob_get_clean();

            imagedestroy($image);

            if ($bytes === false || $bytes === '') {
                return null;
            }

            return [
                'bytes' => $bytes,
                'extension' => match ($outputMime) {
                    'image/jpeg' => 'jpg',
                    'image/png'  => 'png',
                    'image/webp' => 'webp',
                },
            ];
        } catch (\Throwable $e) {
            self::$lastError = $e->getMessage();
            Log::warning('ImageOptimizer: falling back to unprocessed upload — ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Samples a bounded grid of pixels (not every pixel — cheap even on a large image) looking
     * for any that aren't fully opaque.
     */
    private static function hasTransparency($image, int $width, int $height): bool
    {
        if (! imageistruecolor($image)) {
            return imagecolortransparent($image) !== -1;
        }

        $steps = 60; // ~3600 sample points regardless of image size
        for ($x = 0; $x < $steps; $x++) {
            for ($y = 0; $y < $steps; $y++) {
                $px = (int) floor($width * $x / $steps);
                $py = (int) floor($height * $y / $steps);
                $alpha = (imagecolorat($image, $px, $py) >> 24) & 0x7F; // 0 = opaque, 127 = fully transparent
                if ($alpha > 0) {
                    return true;
                }
            }
        }

        return false;
    }
}
