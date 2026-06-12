<?php

namespace App\Http\Controllers;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogTag;
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
        $tags       = BlogTag::withCount('posts')->having('posts_count', '>', 0)->orderByDesc('posts_count')->take(20)->get();
        $featured   = BlogPost::with('category')->published()->featured()->latest('published_at')->take(3)->get();

        return view('blog.index', compact('posts', 'categories', 'tags', 'featured'));
    }

    public function show(BlogPost $blogPost)
    {
        abort_if($blogPost->status !== 'published', 404);

        $blogPost->increment('views');
        $blogPost->load(['category', 'author', 'tags']);

        $related = BlogPost::with('category')
            ->published()
            ->where('id', '!=', $blogPost->id)
            ->where('blog_category_id', $blogPost->blog_category_id)
            ->latest('published_at')
            ->take(3)
            ->get();

        return view('blog.show', compact('blogPost', 'related'));
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
        $tags = BlogTag::withCount('posts')->having('posts_count', '>', 0)->orderByDesc('posts_count')->take(20)->get();

        return view('blog.category', compact('posts', 'blogCategory', 'categories', 'tags'));
    }
}
