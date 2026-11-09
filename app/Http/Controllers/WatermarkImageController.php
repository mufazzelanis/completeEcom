<?php

namespace App\Http\Controllers;

use App\Support\Watermark;
use Illuminate\Support\Facades\Storage;

/**
 * The three public faces of a product photo (see App\Support\Watermark). None of these routes run
 * the session middleware: images must never create visitor sessions or carry a Set-Cookie, which
 * would also stop Cloudflare from caching them.
 */
class WatermarkImageController extends Controller
{
    private const YEAR = 'public, max-age=31536000, immutable';

    /** A marked copy not on disk yet. Once written, the web server serves the file without PHP. */
    public function show(int $version, int $width, string $path)
    {
        abort_unless(in_array($width, Watermark::WIDTHS, true) && !str_contains($path, '..'), 404);
        abort_unless(preg_match('/^(.+)\.(webp|jpg)$/', $path, $m) === 1, 404);
        [, $source, $ext] = $m;
        abort_unless(Watermark::covers($source), 404);

        $cfg = Watermark::configFor($version);
        if (!$cfg || isset(Watermark::excluded()[$source])) {
            // a page cached from before a change, or a product switched off: send it to what's current
            return redirect(Watermark::applies($source, $width) ? Watermark::url($source, $width, $ext) : image_thumb($source, $width), 302);
        }

        abort_unless(Watermark::write($source, $width, $ext, $cfg), 404);

        return response()->file(Storage::disk('public')->path(Watermark::file($version, $width, $source, $ext)), [
            'Content-Type'  => $ext === 'jpg' ? 'image/jpeg' : 'image/webp',
            'Cache-Control' => self::YEAR,
        ]);
    }

    /**
     * /storage/products/… (public/.htaccess sends it here). Old links, emails and images Google
     * already knows keep working, but land on the marked copy. A 302, as the target's version
     * changes with the logo and browsers would remember a 301 forever.
     */
    public function original(string $path)
    {
        abort_if(str_contains($path, '..'), 404);
        $source = 'products/' . $path;
        $disk = Storage::disk('public');
        abort_unless($disk->exists($source), 404);

        if (Watermark::hidesOriginal($source)) {
            return redirect(Watermark::url($source, 1200, 'jpg'), 302);
        }

        return response()->file($disk->path($source), ['Cache-Control' => 'public, max-age=2592000']);
    }

    /** The untouched photo for Google Merchant, at the signed address in the feed. */
    public function clean(string $sig, string $path)
    {
        abort_unless(hash_equals(Watermark::signature($path), $sig) && !str_contains($path, '..') && Watermark::covers($path), 404);
        $disk = Storage::disk('public');
        abort_unless($disk->exists($path), 404);

        return response()->file($disk->path($path), ['Cache-Control' => self::YEAR]);
    }
}
