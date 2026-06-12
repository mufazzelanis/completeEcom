<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogTag;
use Illuminate\Http\Request;
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

        $posts      = $query->latest()->paginate(15)->withQueryString();
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
            'title'   => 'required|string|max:255',
            'content' => 'required|string',
            'status'  => 'required|in:draft,published,scheduled',
            'image'   => 'nullable|image|max:4096',
        ]);

        $data = $request->only(['title', 'excerpt', 'content', 'status', 'meta_title', 'meta_description', 'meta_keywords']);
        $data['slug']             = $this->uniqueSlug(Str::slug($request->title));
        $data['user_id']          = auth()->id();
        $data['blog_category_id'] = $request->filled('blog_category_id') ? $request->blog_category_id : null;
        $data['is_featured']      = $request->boolean('is_featured');
        $data['published_at']     = $request->status === 'draft' ? null
            : ($request->filled('published_at') ? $request->published_at : now());

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('blog/posts', 'public');
        }

        $post = BlogPost::create($data);
        $this->syncTags($post, $request->input('tags', ''));

        return redirect()->route('admin.blog.posts.index')->with('success', 'Post created.');
    }

    public function edit(BlogPost $blogPost)
    {
        $blogPost->load('tags');
        $categories = BlogCategory::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        return view('admin.blog.posts.edit', compact('blogPost', 'categories'));
    }

    public function update(Request $request, BlogPost $blogPost)
    {
        $request->validate([
            'title'   => 'required|string|max:255',
            'content' => 'required|string',
            'status'  => 'required|in:draft,published,scheduled',
            'image'   => 'nullable|image|max:4096',
        ]);

        $data = $request->only(['title', 'excerpt', 'content', 'status', 'meta_title', 'meta_description', 'meta_keywords']);
        $data['blog_category_id'] = $request->filled('blog_category_id') ? $request->blog_category_id : null;
        $data['is_featured']      = $request->boolean('is_featured');
        $data['published_at']     = $request->status === 'draft' ? $blogPost->published_at
            : ($request->filled('published_at') ? $request->published_at : ($blogPost->published_at ?? now()));

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('blog/posts', 'public');
        }

        $blogPost->update($data);
        $this->syncTags($blogPost, $request->input('tags', ''));

        return redirect()->route('admin.blog.posts.edit', $blogPost)->with('success', 'Post updated.');
    }

    public function destroy(BlogPost $blogPost)
    {
        $blogPost->delete();
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

    private function uniqueSlug(string $slug, ?int $exceptId = null): string
    {
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
