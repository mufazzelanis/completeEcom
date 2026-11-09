<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * The store's logo, faintly, on product photos customers see up close. The uploaded originals are
 * never touched: the mark exists only on generated copies at storage/wm/{version}/{width}/{path},
 * which the web server serves as plain files after the first request (same scheme as
 * ImageThumbController). So it reaches every product, old and new, with nothing to re-upload.
 *
 *   Only 400 px and up. Card thumbnails below that stay as they were: a mark is unreadable there.
 *   Versioned. Changing the logo or the look makes a new version with new URLs (a year-long
 *   browser/Cloudflare cache can't show the old one), and `watermark:build` prepares all its
 *   files in the background; the site switches over when they're ready, so no customer ever waits.
 *   Google Merchant refuses watermarked photos, so the feed gets clean signed URLs (cleanUrl()).
 */
class Watermark
{
    public const WIDTHS = [400, 800, 1200];

    public const POSITIONS = ['center' => 'Center', 'tiled' => 'Tiled (repeats across the photo)', 'corner' => 'Bottom-right corner'];

    public const TONES = ['auto' => 'Smart (dark on light photos, white on dark)', 'original' => "Logo's own colours"];

    public const DEFAULTS = ['position' => 'center', 'size' => 35, 'opacity' => 8, 'tone' => 'auto'];

    private const LOGO_DIR = 'settings/watermark';

    private static array $parsed = [];

    private static ?array $excluded = null;

    private static array $logos = [];

    private static array $masks = [];

    /** The look customers are being served now; null when watermarking is off. */
    public static function live(): ?array
    {
        return self::parse('watermark_live');
    }

    /** The look the admin last saved: same as live() unless a new version is still being prepared. */
    public static function desired(): ?array
    {
        return self::parse('watermark_desired');
    }

    private static function parse(string $key): ?array
    {
        $raw = (string) Setting::get($key, '');
        if ($raw === '') {
            return null;
        }
        if (!array_key_exists($raw, self::$parsed)) { // image_thumb() asks once per image on a page
            self::$parsed[$raw] = json_decode($raw, true) ?: null;
        }

        return self::$parsed[$raw];
    }

    /** The look that belongs to a version in a URL: the live one, or the one being prepared. */
    public static function configFor(int $version): ?array
    {
        foreach ([self::live(), self::desired()] as $cfg) {
            if ($cfg && (int) $cfg['v'] === $version) {
                return $cfg;
            }
        }

        return null;
    }

    public static function covers(string $path): bool
    {
        return str_starts_with($path, 'products/') && preg_match('/\.(jpe?g|png|webp)$/i', $path) === 1;
    }

    /** Should this image at this width be shown with the mark right now? */
    public static function applies(string $path, int $width): bool
    {
        return in_array($width, self::WIDTHS, true) && self::live() && self::covers($path) && !isset(self::excluded()[$path]);
    }

    /** Is the untouched file at /storage/products/… kept from the public? */
    public static function hidesOriginal(string $path): bool
    {
        return Setting::get('watermark_protect', '1') === '1' && self::applies($path, 1200);
    }

    public static function url(string $path, int $width, string $ext = 'webp'): string
    {
        return Storage::disk('public')->url(self::file((int) self::live()['v'], $width, $path, $ext));
    }

    public static function file(int $version, int $width, string $path, string $ext): string
    {
        return "wm/{$version}/{$width}/{$path}.{$ext}";
    }

    /**
     * For what machines read from the page (og:image, JSON-LD, image sitemap): the marked copy as a
     * JPEG, the format every link-preview crawler accepts, and never a redirect.
     */
    public static function publicUrl(?string $path): ?string
    {
        if (!$path || str_starts_with($path, 'http')) {
            return $path;
        }

        return url(self::applies($path, 1200) ? self::url($path, 1200, 'jpg') : Storage::url($path));
    }

    /** Unmarked image for Google Merchant, at a signed address that can't be guessed from a thumbnail's. */
    public static function cleanUrl(string $path): string
    {
        return self::hidesOriginal($path)
            ? route('image.clean', ['sig' => self::signature($path), 'path' => $path])
            : url(Storage::url($path));
    }

    public static function signature(string $path): string
    {
        return substr(hash_hmac('sha256', 'wm-clean|' . $path, (string) config('app.key')), 0, 24);
    }

    /** path => true for photos of products the mark is switched off for. */
    public static function excluded(): array
    {
        return self::$excluded ??= Cache::remember('watermark:excluded', 3600, function () {
            $skipSellers = Setting::get('watermark_skip_sellers', '0') === '1';
            $ids = DB::table('products')
                ->where(fn ($q) => $q->where('watermark_off', true)->when($skipSellers, fn ($w) => $w->orWhereNotNull('seller_id')))
                ->pluck('id');
            if ($ids->isEmpty()) {
                return [];
            }

            return array_fill_keys(collect()
                ->merge(DB::table('products')->whereIn('id', $ids)->whereNotNull('image')->pluck('image'))
                ->merge(DB::table('product_images')->whereIn('product_id', $ids)->pluck('image'))
                ->merge(DB::table('product_colors')->whereIn('product_id', $ids)->whereNotNull('image')->pluck('image'))
                ->all(), true);
        });
    }

    public static function forgetExclusions(): void
    {
        self::$excluded = null;
        Cache::forget('watermark:excluded');
    }

    /** Clears what this process remembers: settings may have changed underneath it. */
    public static function flush(): void
    {
        self::$parsed = self::$logos = self::$masks = [];
        self::$excluded = null;
    }

    // ---- saving the admin's choices -------------------------------------------------------------

    /** Saves a look. A different look gets a new version, which goes live once its files are built. */
    public static function saveLook(array $look): void
    {
        $live = self::live();
        $desired = self::desired();
        $current = $desired ?? $live;
        $keys = ['logo', 'position', 'size', 'opacity', 'tone'];
        if ($current && array_intersect_key($current, array_flip($keys)) == array_intersect_key($look, array_flip($keys))) {
            return;
        }
        $version = max((int) ($live['v'] ?? 0), (int) ($desired['v'] ?? 0)) + 1;
        Setting::set('watermark_desired', json_encode(['v' => $version] + $look), 'watermark');
        self::flush();
    }

    /** Puts the saved look in front of customers. Any file not built yet is made on its first view. */
    public static function publish(): void
    {
        Setting::set('watermark_live', json_encode(self::desired()), 'watermark');
        self::flush();
    }

    /** Back to plain photos, at once. */
    public static function disable(): void
    {
        Setting::set('watermark_live', '', 'watermark');
        Setting::set('watermark_desired', '', 'watermark');
        self::flush();
    }

    /** Removes files of versions nothing points at, and logos no longer used. */
    public static function cleanup(): void
    {
        $disk = Storage::disk('public');
        $keep = array_filter([(int) (self::live()['v'] ?? 0), (int) (self::desired()['v'] ?? 0)]);
        foreach ($disk->directories('wm') as $dir) {
            if (!in_array((int) basename($dir), $keep, true)) {
                $disk->deleteDirectory($dir);
            }
        }

        $logos = array_filter([self::live()['logo'] ?? null, self::desired()['logo'] ?? null]);
        foreach ($disk->files(self::LOGO_DIR) as $file) {
            // a day's grace: the admin may have just uploaded it and be trying it in the preview
            if (!in_array($file, $logos, true) && $disk->lastModified($file) < now()->subDay()->getTimestamp()) {
                $disk->delete($file);
            }
        }
    }

    public static function isLogoPath(string $path): bool
    {
        return str_starts_with($path, self::LOGO_DIR . '/') && !str_contains($path, '..');
    }

    public static function logoDirectory(): string
    {
        return self::LOGO_DIR;
    }

    /** A logo needs a see-through background, or the "faint logo" is a faint rectangle. */
    public static function hasTransparentBackground(string $bytes): bool
    {
        $img = @imagecreatefromstring($bytes);
        if (!$img) {
            return false;
        }
        imagepalettetotruecolor($img);
        [$w, $h] = [imagesx($img) - 1, imagesy($img) - 1];
        foreach ([[0, 0], [$w, 0], [0, $h], [$w, $h]] as [$x, $y]) {
            if ((imagecolorat($img, $x, $y) >> 24 & 0x7F) > 100) {
                return true;
            }
        }

        return false;
    }

    // ---- making the image -----------------------------------------------------------------------

    /** Creates (if needed) the marked copy on disk. False when the source can't be read. */
    public static function write(string $source, int $width, string $ext, array $cfg): bool
    {
        $disk = Storage::disk('public');
        $target = self::file((int) $cfg['v'], $width, $source, $ext);
        if ($disk->exists($target)) {
            return true;
        }
        if (!$disk->exists($source) || !($image = self::render($disk->get($source), $width, $cfg))) {
            return false;
        }

        $disk->makeDirectory(dirname($target));
        // written aside then moved, so a visitor can never be served a half-finished file
        $tmp = $disk->path($target) . '.' . getmypid() . '.tmp';
        self::encode($image, $ext, $tmp);
        imagedestroy($image);

        return rename($tmp, $disk->path($target));
    }

    /** Encoded bytes of a marked image without saving it (the admin's live preview). */
    public static function previewBytes(string $source, int $width, array $cfg): ?string
    {
        $image = self::render(Storage::disk('public')->get($source), $width, $cfg);
        if (!$image) {
            return null;
        }
        ob_start();
        imagewebp($image, null, 80);
        imagedestroy($image);

        return ob_get_clean();
    }

    private static function encode(\GdImage $image, string $ext, string $to): void
    {
        if ($ext === 'webp') {
            imagewebp($image, $to, 80);

            return;
        }
        $flat = imagecreatetruecolor(imagesx($image), imagesy($image));
        imagefill($flat, 0, 0, imagecolorallocate($flat, 255, 255, 255));
        imagecopy($flat, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));
        imagejpeg($flat, $to, 85);
        imagedestroy($flat);
    }

    private static function render(string $bytes, int $width, array $cfg): ?\GdImage
    {
        $source = @imagecreatefromstring($bytes);
        if (!$source) {
            return null;
        }
        imagepalettetotruecolor($source);
        $srcW = imagesx($source);
        $srcH = imagesy($source);
        $w = min($width, $srcW); // never upscale
        $h = (int) round($srcH * $w / $srcW);

        $image = imagecreatetruecolor($w, $h);
        imagealphablending($image, false);
        imagesavealpha($image, true);
        imagecopyresampled($image, $source, 0, 0, 0, 0, $w, $h, $srcW, $srcH);
        imagedestroy($source);

        $logo = self::logo((string) ($cfg['logo'] ?? ''));
        if ($logo) {
            imagealphablending($image, true);
            self::stampOnto($image, $logo, $cfg);
            imagealphablending($image, false);
        }

        return $image;
    }

    private static function logo(string $path): ?\GdImage
    {
        if (array_key_exists($path, self::$logos)) {
            return self::$logos[$path];
        }
        $disk = Storage::disk('public');
        $logo = $path !== '' && $disk->exists($path) ? @imagecreatefromstring($disk->get($path)) : null;
        if ($logo) {
            imagepalettetotruecolor($logo);
            imagealphablending($logo, false);
            imagesavealpha($logo, true);
            $logo = self::trim($logo);
        }

        return self::$logos[$path] = $logo ?: null;
    }

    /** Cuts the empty margins a logo file often has: they'd make the mark smaller and off-centre. */
    private static function trim(\GdImage $logo): \GdImage
    {
        $w = imagesx($logo);
        $h = imagesy($logo);
        [$left, $top, $right, $bottom] = [$w, $h, -1, -1];
        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                if ((imagecolorat($logo, $x, $y) >> 24 & 0x7F) < 120) {
                    $left = min($left, $x);
                    $right = max($right, $x);
                    $top = min($top, $y);
                    $bottom = max($bottom, $y);
                }
            }
        }
        if ($right < 0 || ($left === 0 && $top === 0 && $right === $w - 1 && $bottom === $h - 1)) {
            return $logo;
        }
        $cut = imagecrop($logo, ['x' => $left, 'y' => $top, 'width' => $right - $left + 1, 'height' => $bottom - $top + 1]);
        if (!$cut) {
            return $logo;
        }
        imagedestroy($logo);
        imagealphablending($cut, false);
        imagesavealpha($cut, true);

        return $cut;
    }

    private static function stampOnto(\GdImage $image, \GdImage $logo, array $cfg): void
    {
        $W = imagesx($image);
        $H = imagesy($image);
        $ratio = imagesy($logo) / imagesx($logo);
        $position = $cfg['position'] ?? 'center';

        $sw = max(24, (int) round($W * ((int) $cfg['size']) / 100 * ($position === 'tiled' ? .6 : 1)));
        if ($sw * $ratio > $H * .6) { // a tall logo shouldn't fill a short photo
            $sw = max(24, (int) round($H * .6 / $ratio));
        }
        $sh = max(8, (int) round($sw * $ratio));

        if ($position === 'tiled') {
            $mask = self::mask($logo, $sw, $sh, true);
            $tw = imagesx($mask);
            $th = imagesy($mask);
            $stepX = max(1, (int) round($tw * 1.25));
            $stepY = max(1, (int) round($th * 1.25));
            for ($row = 0, $y = -$th / 2; $y < $H; $row++, $y += $stepY) {
                for ($x = -$tw + ($row % 2 ? $stepX / 2 : 0); $x < $W; $x += $stepX) {
                    self::paint($image, $mask, (int) $x, (int) $y, $cfg);
                }
            }

            return;
        }

        $margin = (int) round($W * .03);
        [$x, $y] = $position === 'corner'
            ? [$W - $sw - $margin, $H - $sh - $margin]
            : [(int) (($W - $sw) / 2), (int) (($H - $sh) / 2)];
        self::paint($image, self::mask($logo, $sw, $sh, false), $x, $y, $cfg);
    }

    /** The logo at this size. Kept for the run: every photo of that size reuses it. */
    private static function mask(\GdImage $logo, int $w, int $h, bool $rotated): \GdImage
    {
        $key = implode('|', [spl_object_id($logo), $w, $h, (int) $rotated]);
        if (isset(self::$masks[$key])) {
            return self::$masks[$key];
        }

        $mask = imagecreatetruecolor($w, $h);
        imagealphablending($mask, false);
        imagesavealpha($mask, true);
        imagefilledrectangle($mask, 0, 0, $w, $h, imagecolorallocatealpha($mask, 0, 0, 0, 127));
        imagecopyresampled($mask, $logo, 0, 0, 0, 0, $w, $h, imagesx($logo), imagesy($logo));

        if ($rotated) {
            $turned = imagerotate($mask, 30, imagecolorallocatealpha($mask, 0, 0, 0, 127));
            imagedestroy($mask);
            imagealphablending($turned, false);
            imagesavealpha($turned, true);
            $mask = $turned;
        }

        return self::$masks[$key] = $mask;
    }

    /**
     * Lays the mask on the photo, faded. In smart tone the colour is chosen per small patch, dark
     * over light areas and white over dark ones, so the mark doesn't vanish where it crosses a
     * dark product on a white background.
     */
    private static function paint(\GdImage $image, \GdImage $mask, int $atX, int $atY, array $cfg): void
    {
        $mw = imagesx($mask);
        $mh = imagesy($mask);
        $W = imagesx($image);
        $H = imagesy($image);
        $smart = ($cfg['tone'] ?? 'auto') !== 'original';
        $strength = ((int) $cfg['opacity']) / 100;
        $cell = max(6, (int) round(max($mw, $mh) / 24));
        $dark = $smart ? self::darkPatches($image, $atX, $atY, $mw, $mh, $cell) : [];

        for ($my = max(0, -$atY); $my < $mh && $atY + $my < $H; $my++) {
            for ($mx = max(0, -$atX); $mx < $mw && $atX + $mx < $W; $mx++) {
                $c = imagecolorat($mask, $mx, $my);
                $visible = (127 - ($c >> 24 & 0x7F)) / 127;
                if ($visible < .03) {
                    continue;
                }
                $rgb = $c & 0xFFFFFF;
                $amount = $strength;
                if ($smart) {
                    $useWhite = $dark[(int) ($my / $cell)][(int) ($mx / $cell)] ?? false;
                    $rgb = $useWhite ? 0xFFFFFF : 0x000000;
                    $amount = $strength * ($useWhite ? 1.3 : 1); // white reads weaker than black
                }
                $alpha = 127 - (int) round(127 * $visible * min(1, $amount));
                imagesetpixel($image, $atX + $mx, $atY + $my, ($alpha << 24) | $rgb);
            }
        }
    }

    /** For each small patch under the mark: is the photo dark there? See-through counts as the page's white. */
    private static function darkPatches(\GdImage $image, int $x, int $y, int $w, int $h, int $cell): array
    {
        $cols = (int) ceil($w / $cell);
        $rows = (int) ceil($h / $cell);
        $x1 = max(0, $x);
        $y1 = max(0, $y);
        $x2 = min(imagesx($image), $x + $w);
        $y2 = min(imagesy($image), $y + $h);
        if ($x2 <= $x1 || $y2 <= $y1) {
            return [];
        }

        // one shrink of the whole area is far cheaper than a resample per patch
        $probe = imagecreatetruecolor($cols, $rows);
        imagealphablending($probe, false);
        imagesavealpha($probe, true);
        imagefilledrectangle($probe, 0, 0, $cols, $rows, imagecolorallocatealpha($probe, 255, 255, 255, 127));
        imagecopyresampled($probe, $image,
            (int) (($x1 - $x) / $cell), (int) (($y1 - $y) / $cell), $x1, $y1,
            max(1, (int) ceil(($x2 - $x1) / $cell)), max(1, (int) ceil(($y2 - $y1) / $cell)), $x2 - $x1, $y2 - $y1);

        $out = [];
        for ($r = 0; $r < $rows; $r++) {
            for ($c = 0; $c < $cols; $c++) {
                $p = imagecolorat($probe, $c, $r);
                $opaque = (127 - ($p >> 24 & 0x7F)) / 127;
                $lum = .299 * ($p >> 16 & 0xFF) + .587 * ($p >> 8 & 0xFF) + .114 * ($p & 0xFF);
                $out[$r][$c] = $lum * $opaque + 255 * (1 - $opaque) < 120;
            }
        }
        imagedestroy($probe);

        return $out;
    }
}
