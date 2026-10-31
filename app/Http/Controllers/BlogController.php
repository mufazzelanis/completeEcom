<?php

namespace App\Http\Controllers;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    public function index(Request $request)
    {
        $query = BlogPost::with(['category', 'author'])->published();

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn($q) => $q->where('title', 'like', "%$s%")->orWhere('excerpt', 'like', "%$s%"));
        }
        if ($request->filled('tag')) {
            $query->whereHas('tags', fn($q) => $q->where('slug', $request->tag));
        }

        $posts      = $query->orderByDesc('published_at')->paginate(9)->withQueryString();
        $categories = BlogCategory::where('is_active', true)
            ->withCount(['posts' => fn($q) => $q->published()])
            ->orderBy('name')->get();
        $featured   = BlogPost::with('category')->published()->featured()->latest('published_at')->take(3)->get();

        return view('blog.index', compact('posts', 'categories', 'featured'));
    }

    public function show(BlogPost $blogPost)
    {
        abort_if($blogPost->status !== 'published' || $blogPost->published_at?->isFuture(), 404);

        $blogPost->increment('views');
        $blogPost->load(['category', 'author', 'tags']);

        $related = BlogPost::with('category')
            ->published()
            ->where('id', '!=', $blogPost->id)
            ->where('blog_category_id', $blogPost->blog_category_id)
            ->latest('published_at')
            ->take(3)
            ->get();

        $shopProducts = $this->productsFor($blogPost);

        return view('blog.show', compact('blogPost', 'related', 'shopProducts'));
    }

    /**
     * "Shop the products in this article": products the post links to (in its own order), then
     * products whose names share the title's words — every article ends with links into the
     * catalogue, which passes ranking from the post to those products and turns readers into buyers.
     */
    private function productsFor(BlogPost $post, int $max = 4)
    {
        preg_match_all('#/products/([a-z0-9][a-z0-9-]*)#i', (string) $post->content, $m);
        $slugs = array_values(array_unique(array_map('strtolower', $m[1])));
        $with = ['brand', 'reviews', 'activeFlashSaleProduct'];

        $linked = $slugs
            ? \App\Models\Product::active()->with($with)->whereIn('slug', $slugs)->get()->sortBy(fn ($p) => array_search($p->slug, $slugs))->values()
            : collect();
        if ($linked->count() >= $max) {
            return $linked->take($max);
        }

        // title words worth matching on (no filler words, no short ones)
        $stop = ['best', 'guide', 'how', 'what', 'why', 'which', 'with', 'your', 'for', 'and', 'the', 'price', 'bangladesh', 'top', 'review', 'reviews', 'tips', 'benefits', 'use', 'uses', 'buy', 'online', 'original', 'from', 'that', 'this', 'into', 'about'];
        $words = collect(preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($post->title)))
            ->filter(fn ($w) => mb_strlen($w) >= 4 && !in_array($w, $stop, true))->unique()->take(6)->values();
        if ($words->isEmpty()) {
            return $linked;
        }

        $matched = \App\Models\Product::active()->with($with)
            ->whereNotIn('id', $linked->pluck('id'))
            ->where(fn ($q) => $words->each(fn ($w) => $q->orWhere('name', 'like', "%{$w}%")))
            ->where('stock', '>', 0)
            ->limit(40)->get()
            // most title words in the name first, then best sellers' order (views as a tiebreak)
            ->sortByDesc(fn ($p) => $words->filter(fn ($w) => str_contains(mb_strtolower($p->name), $w))->count() * 100000 + (int) $p->views)
            ->values();

        return $linked->concat($matched)->take($max);
    }

    public function category(BlogCategory $blogCategory)
    {
        abort_if(!$blogCategory->is_active, 404);

        $posts = BlogPost::with(['category', 'author'])
            ->published()
            ->where('blog_category_id', $blogCategory->id)
            ->orderByDesc('published_at')
            ->paginate(9)
            ->withQueryString();

        $categories = BlogCategory::where('is_active', true)
            ->withCount(['posts' => fn($q) => $q->published()])
            ->orderBy('name')->get();

        return view('blog.category', compact('posts', 'blogCategory', 'categories'));
    }
}
