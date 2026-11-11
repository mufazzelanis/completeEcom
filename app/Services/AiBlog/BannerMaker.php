<?php

namespace App\Services\AiBlog;

use App\Models\BlogPost;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * The post's featured image (1200×630, the size social shares use), drawn on the server from
 * the real product photos — so the picture always shows the actual product, never an AI guess
 * at its label. Brand-colour background, products on white cards, the English title as text
 * (GD can't shape Bangla letters correctly, so a Bangla-only post shows the shop name instead).
 * Saved as WebP. No API call, no cost.
 */
class BannerMaker
{
    private const W = 1200;
    private const H = 630;

    public static function make(BlogPost $post, Collection $products): ?string
    {
        if (!function_exists('imagecreatetruecolor') || !function_exists('imagewebp')) {
            return null;
        }

        $img = imagecreatetruecolor(self::W, self::H);
        imagealphablending($img, true);

        [$r, $g, $b] = self::rgb((string) (setting('primary_color') ?: '#ea580c'));
        // diagonal gradient: brand colour → a deeper shade of it
        for ($x = 0; $x < self::W; $x++) {
            $t = $x / self::W;
            $c = imagecolorallocate($img, (int) ($r * (1 - .45 * $t)), (int) ($g * (1 - .45 * $t)), (int) ($b * (1 - .45 * $t)));
            imageline($img, $x, 0, $x, self::H, $c);
        }
        // soft decorative circles
        $soft = imagecolorallocatealpha($img, 255, 255, 255, 112);
        imagefilledellipse($img, 1080, 80, 420, 420, $soft);
        imagefilledellipse($img, 120, 600, 360, 360, $soft);

        $photos = $products->filter(fn ($p) => $p->image && Storage::disk('public')->exists($p->image))->take(3)->values();
        $boxes = match ($photos->count()) {
            0       => [],
            1       => [[700, 75, 440]],
            2       => [[650, 130, 300], [890, 200, 300]],
            default => [[625, 60, 245], [890, 60, 245], [757, 325, 245]],
        };
        foreach ($photos as $i => $p) {
            [$x, $y, $size] = $boxes[$i];
            self::card($img, $x, $y, $size, Storage::disk('public')->path($p->image));
        }

        $bold = storage_path('fonts/HindSiliguri-Bold.ttf');
        $regular = storage_path('fonts/HindSiliguri-Regular.ttf');
        $white = imagecolorallocate($img, 255, 255, 255);
        $faint = imagecolorallocatealpha($img, 255, 255, 255, 30);
        $textWidth = $photos->isEmpty() ? 1060 : 540;

        $title = $post->primaryLang() === 'en' ? $post->title : ($post->hasAlt() ? $post->alt_title : null);
        $site = (string) setting('site_name', parse_url(config('app.url'), PHP_URL_HOST));
        if (is_file($bold) && function_exists('imagettftext')) {
            $lines = $title && !preg_match('/\p{Bengali}/u', $title) ? self::wrap($title, $bold, 46, $textWidth, 4) : [$site];
            $size = $title ? 46 : 64;
            $y = (int) (self::H / 2 - (count($lines) * $size * 1.3) / 2 + $size);
            foreach ($lines as $line) {
                imagettftext($img, $size, 0, 70, $y, $white, $bold, $line);
                $y += (int) ($size * 1.3);
            }
            if ($title && is_file($regular)) {
                imagettftext($img, 22, 0, 70, self::H - 50, $faint, $regular, $site . '  ·  Blog');
            }
        }

        ob_start();
        imagewebp($img, null, 82);
        $bytes = ob_get_clean();
        imagedestroy($img);

        $path = 'blog/posts/ai-' . $post->id . '-' . substr(md5(uniqid('', true)), 0, 6) . '.webp';
        Storage::disk('public')->put($path, $bytes);

        return $path;
    }

    /** White rounded card with the product photo contained in it. */
    private static function card($img, int $x, int $y, int $size, string $file): void
    {
        $white = imagecolorallocate($img, 255, 255, 255);
        $shadow = imagecolorallocatealpha($img, 0, 0, 0, 105);
        self::roundedRect($img, $x + 8, $y + 12, $size, $size, 28, $shadow);
        self::roundedRect($img, $x, $y, $size, $size, 28, $white);

        $src = @imagecreatefromstring((string) @file_get_contents($file));
        if (!$src) {
            return;
        }
        $sw = imagesx($src);
        $sh = imagesy($src);
        $pad = (int) ($size * .08);
        $scale = min(($size - 2 * $pad) / $sw, ($size - 2 * $pad) / $sh);
        $dw = (int) ($sw * $scale);
        $dh = (int) ($sh * $scale);
        imagecopyresampled($img, $src, $x + (int) (($size - $dw) / 2), $y + (int) (($size - $dh) / 2), 0, 0, $dw, $dh, $sw, $sh);
        imagedestroy($src);
    }

    private static function roundedRect($img, int $x, int $y, int $w, int $h, int $r, int $color): void
    {
        imagefilledrectangle($img, $x + $r, $y, $x + $w - $r, $y + $h, $color);
        imagefilledrectangle($img, $x, $y + $r, $x + $w, $y + $h - $r, $color);
        foreach ([[$x + $r, $y + $r], [$x + $w - $r, $y + $r], [$x + $r, $y + $h - $r], [$x + $w - $r, $y + $h - $r]] as [$cx, $cy]) {
            imagefilledellipse($img, $cx, $cy, $r * 2, $r * 2, $color);
        }
    }

    /** Word-wrap to $maxWidth px, at most $maxLines lines (the last one ends in "…" if cut). */
    private static function wrap(string $text, string $font, int $size, int $maxWidth, int $maxLines): array
    {
        $lines = [];
        $line = '';
        foreach (preg_split('/\s+/u', trim($text)) as $word) {
            $try = $line === '' ? $word : "$line $word";
            $box = imagettfbbox($size, 0, $font, $try);
            if ($line !== '' && ($box[2] - $box[0]) > $maxWidth) {
                $lines[] = $line;
                $line = $word;
            } else {
                $line = $try;
            }
        }
        if ($line !== '') {
            $lines[] = $line;
        }
        if (count($lines) > $maxLines) {
            $lines = array_slice($lines, 0, $maxLines);
            $lines[$maxLines - 1] = rtrim($lines[$maxLines - 1], ' ,.:;') . '…';
        }

        return $lines;
    }

    private static function rgb(string $hex): array
    {
        $hex = ltrim(trim($hex), '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        if (!preg_match('/^[0-9a-f]{6}$/i', $hex)) {
            $hex = 'ea580c';
        }

        return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
    }
}
