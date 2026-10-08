<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;

/**
 * On-demand resized WebP thumbnails for public-disk images (see image_thumb()).
 *
 * Product photos are uploaded at 800×800+ but shown at ~200px in cards, and category/brand
 * images are full-size uploads shown as small icons — PageSpeed flagged ~1.7 MB of avoidable
 * image weight on the homepage alone. The thumb URL points straight at
 * storage/thumbs/{width}/{original path}.webp: the first request for it misses the static
 * file, falls through public/.htaccess to this route, which writes the file to exactly that
 * path — every later request is served directly by the web server, never touching PHP.
 */
class ImageThumbController extends Controller
{
    public const WIDTHS = [96, 200, 400, 800];

    public function show(int $width, string $path)
    {
        abort_unless(in_array($width, self::WIDTHS, true), 404);
        abort_unless(str_ends_with($path, '.webp') && !str_contains($path, '..'), 404);

        $disk = Storage::disk('public');
        $source = substr($path, 0, -5);
        abort_unless($disk->exists($source) && preg_match('/\.(jpe?g|png|webp)$/i', $source), 404);

        $target = "thumbs/{$width}/{$path}";
        if (!$disk->exists($target)) {
            $image = @imagecreatefromstring($disk->get($source));
            if (!$image || !function_exists('imagewebp')) {
                return redirect($disk->url($source));
            }

            $srcW = imagesx($image);
            $srcH = imagesy($image);
            $newW = min($width, $srcW); // never upscale
            $newH = (int) round($srcH * $newW / $srcW);

            $thumb = imagecreatetruecolor($newW, $newH);
            imagealphablending($thumb, false);
            imagesavealpha($thumb, true);
            imagecopyresampled($thumb, $image, 0, 0, 0, 0, $newW, $newH, $srcW, $srcH);

            $disk->makeDirectory(dirname($target));
            imagewebp($thumb, $disk->path($target), 80);
            imagedestroy($image);
            imagedestroy($thumb);
        }

        return response()->file($disk->path($target), [
            'Content-Type' => 'image/webp',
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }
}
