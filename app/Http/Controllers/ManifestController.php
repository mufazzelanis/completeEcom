<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

/**
 * Web app manifests for the customer storefront and the admin panel — what actually lets a
 * phone browser offer "Add to Home Screen" and, once added, launch the site full-screen with
 * its own icon/name/theme color instead of opening inside browser chrome. Built from live
 * branding settings (not a static file) so a site name/logo/color change in Settings →
 * Branding is reflected the next time someone installs it, with no redeploy needed.
 *
 * Two separate manifests, two separate "apps": a shop owner and their customers both use this
 * same domain, and each should be able to install just their own side as its own home-screen
 * icon labeled for what it actually is, rather than one generic "site" icon covering both.
 */
class ManifestController extends Controller
{
    public function customer(): JsonResponse
    {
        return $this->respond([
            'name'             => setting('site_name', 'ShopVista'),
            'short_name'       => setting('site_name', 'ShopVista'),
            'description'      => setting('site_tagline', 'Your one-stop shop for everything you need.'),
            'start_url'        => url('/'),
            'scope'            => url('/'),
        ]);
    }

    public function admin(): JsonResponse
    {
        $siteName = setting('site_name', 'ShopVista');

        return $this->respond([
            'name'        => $siteName . ' Admin',
            'short_name'  => $siteName . ' Admin',
            'description' => 'Store management dashboard for ' . $siteName,
            'start_url'   => url('/admin'),
            'scope'       => url('/admin'),
        ]);
    }

    private function respond(array $overrides): JsonResponse
    {
        $iconUrl = setting_file_url('site_logo') ?: setting_file_url('favicon');
        // The declared MIME type has to match the actual uploaded file (admins commonly
        // upload .webp/.jpg logos, not just .png) — a mismatched type here is exactly the
        // kind of thing that makes a browser silently reject the icon rather than degrade
        // gracefully to whatever fallback letter-icon it'd otherwise show.
        $iconType = match (strtolower(pathinfo((string) $iconUrl, PATHINFO_EXTENSION))) {
            'webp'  => 'image/webp',
            'jpg', 'jpeg' => 'image/jpeg',
            'gif'   => 'image/gif',
            'svg'   => 'image/svg+xml',
            default => 'image/png',
        };

        $manifest = array_merge([
            'display'          => 'standalone',
            'orientation'      => 'portrait-primary',
            'background_color' => '#ffffff',
            'theme_color'      => setting('primary_color', '#ea580c'),
            // Real icon files come from wherever the admin uploaded a logo/favicon, at
            // whatever resolution that happens to be — declaring both common sizes against
            // the same file is a pragmatic stand-in for maintaining dedicated 192/512 exports;
            // browsers scale a bitmap icon fine for install purposes either way.
            'icons' => $iconUrl ? [
                ['src' => $iconUrl, 'sizes' => '192x192', 'type' => $iconType],
                ['src' => $iconUrl, 'sizes' => '512x512', 'type' => $iconType],
            ] : [],
        ], $overrides);

        return response()->json($manifest)->header('Content-Type', 'application/manifest+json');
    }
}
