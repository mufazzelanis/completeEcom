<?php

namespace App\Http\Controllers;

use App\Models\Category;

/**
 * GET /nav/panel/{slug} — one desktop header dropdown (a category's sub-categories), loaded
 * the first time someone hovers that category. These dropdowns used to be in every page's
 * HTML (~130 KB on the homepage), which phones — where they never show — downloaded before
 * the hero banner could start. Every sub-category also stays linked from the footer.
 */
class NavPanelController extends Controller
{
    public function show(string $slug)
    {
        $navCat = Category::active()->whereNull('parent_id')->where('slug', $slug)
            ->with(['children' => fn ($q) => $q->active()->orderBy('sort_order')->withCount('products')])
            ->firstOrFail();

        return response()
            ->view('partials.storefront.nav-panel', compact('navCat'))
            ->header('Cache-Control', 'public, max-age=600')
            ->header('X-Robots-Tag', 'noindex');
    }
}
