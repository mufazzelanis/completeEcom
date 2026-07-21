<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogTag;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BlogPostController extends Controller
{
    public function index(Request $request)
    {
        $query = BlogPost::with(['category', 'author']);

        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('category')) {
            $query->where('blog_category_id', $request->category);
        }

        $posts = $query->latest()->paginate(15)->withQueryString();
        $categories = BlogCategory::orderBy('name')->get(['id', 'name']);

        return view('admin.blog.posts.index', compact('posts', 'categories'));
    }

    public function create()
    {
        $categories = BlogCategory::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        return view('admin.blog.posts.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'            => 'required|string|max:255',
            'slug'             => 'nullable|string|max:255',
            'content'          => 'required|string',
            'status'           => 'required|in:draft,published,scheduled',
            'image'            => 'nullable|image|max:4096',
            'blog_category_id' => 'nullable|exists:blog_categories,id',
            'meta_title'       => 'nullable|string|max:255',
            'meta_keywords'    => 'nullable|string|max:255',
        ]);

        if ($request->filled('slug') && Str::slug($request->slug) === '') {
            return back()->withInput()->withErrors(['slug' => 'This slug must contain at least one letter or number.']);
        }

        $data = $request->only(['title', 'excerpt', 'content', 'status', 'meta_title', 'meta_description', 'meta_keywords']);
        $data['slug']             = $this->uniqueSlug(Str::slug($request->filled('slug') ? $request->slug : $request->title));
        $data['user_id']          = auth()->id();
        $data['blog_category_id'] = $request->filled('blog_category_id') ? $request->blog_category_id : null;
        $data['is_featured']      = $request->boolean('is_featured');
        $data['published_at']     = $request->status === 'draft' ? null
            : ($request->filled('published_at') ? $this->parsePublishedAt($request->published_at) : now());

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('blog/posts', 'public');
        }

        $post = BlogPost::create($data);
        $this->syncTags($post, $request->input('tags') ?? '');

        return redirect()->route('admin.blog.posts.index')->with('success', 'Post created.');
    }

    public function edit(BlogPost $post)
    {
        $post->load('tags');
        $categories = BlogCategory::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        return view('admin.blog.posts.edit', ['blogPost' => $post, 'categories' => $categories]);
    }

    public function update(Request $request, BlogPost $post)
    {
        $request->validate([
            'title'            => 'required|string|max:255',
            'slug'             => 'nullable|string|max:255',
            'content'          => 'required|string',
            'status'           => 'required|in:draft,published,scheduled',
            'image'            => 'nullable|image|max:4096',
            'blog_category_id' => 'nullable|exists:blog_categories,id',
            'meta_title'       => 'nullable|string|max:255',
            'meta_keywords'    => 'nullable|string|max:255',
        ]);

        if ($request->filled('slug') && Str::slug($request->slug) === '') {
            return back()->withInput()->withErrors(['slug' => 'This slug must contain at least one letter or number.']);
        }

        $data = $request->only(['title', 'excerpt', 'content', 'status', 'meta_title', 'meta_description', 'meta_keywords']);
        if ($request->filled('slug') && Str::slug($request->slug) !== $post->slug) {
            $data['slug'] = $this->uniqueSlug(Str::slug($request->slug), $post->id);
        }
        $data['blog_category_id'] = $request->filled('blog_category_id') ? $request->blog_category_id : null;
        $data['is_featured']      = $request->boolean('is_featured');
        $data['published_at']     = $request->status === 'draft' ? $post->published_at
            : ($request->filled('published_at') ? $this->parsePublishedAt($request->published_at) : ($post->published_at ?? now()));

        if ($request->hasFile('image')) {
            if ($post->image) {
                Storage::disk('public')->delete($post->image);
            }
            $data['image'] = $request->file('image')->store('blog/posts', 'public');
        }

        $post->update($data);
        $this->syncTags($post, $request->input('tags') ?? '');

        return redirect()->route('admin.blog.posts.edit', $post)->with('success', 'Post updated.');
    }

    public function destroy(BlogPost $post)
    {
        if ($post->image) {
            Storage::disk('public')->delete($post->image);
        }
        $post->delete();
        return redirect()->route('admin.blog.posts.index')->with('success', 'Post deleted.');
    }

    private function syncTags(BlogPost $post, string $tagInput): void
    {
        $names  = array_unique(array_filter(array_map('trim', explode(',', $tagInput))));
        $tagIds = [];
        foreach ($names as $name) {
            if (!$name) continue;
            $tag      = BlogTag::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name]);
            $tagIds[] = $tag->id;
        }
        $post->tags()->sync($tagIds);
    }

    private function parsePublishedAt(string $raw): Carbon
    {
        // The datetime-local input has no timezone info — it's a wall-clock time
        // in the site's configured timezone, not the app's (UTC) timezone.
        return Carbon::parse($raw, setting('timezone', 'Asia/Dhaka'))->utc();
    }

    private function uniqueSlug(string $slug, ?int $exceptId = null): string
    {
        if ($slug === '') {
            $slug = 'post-' . Str::random(8);
        }

        $original = $slug; $i = 1;
        while (true) {
            $q = BlogPost::where('slug', $slug);
            if ($exceptId) $q->where('id', '!=', $exceptId);
            if (!$q->exists()) break;
            $slug = $original . '-' . $i++;
        }
        return $slug;
    }
}
