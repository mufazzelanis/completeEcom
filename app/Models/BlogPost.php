<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class BlogPost extends Model
{
    protected $fillable = [
        'blog_category_id', 'user_id', 'title', 'slug', 'excerpt', 'content',
        'image', 'status', 'published_at', 'is_featured', 'views',
        'meta_title', 'meta_description', 'meta_keywords',
        // bilingual: title/content are in `lang`; alt_* are the same article in the other language
        'lang', 'alt_title', 'alt_excerpt', 'alt_content', 'alt_meta_title', 'alt_meta_description',
        'ai_generated', 'review_notes',
    ];

    public const LANGS = ['bn' => 'বাংলা', 'en' => 'English'];

    protected $casts = [
        'is_featured'  => 'boolean',
        'ai_generated' => 'boolean',
        'published_at' => 'datetime',
        'views'        => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($m) {
            if (empty($m->slug)) $m->slug = Str::slug($m->title);
        });
        // a post going live (or its words changing while live) → IndexNow engines recrawl it now
        static::saved(function ($m) {
            if ($m->status === 'published' && $m->published_at && !$m->published_at->isFuture()
                && ($m->wasRecentlyCreated || $m->wasChanged(['status', 'published_at', 'title', 'content', 'slug', 'alt_title', 'alt_content']))) {
                foreach ($m->languageUrls() as $url) {
                    \App\Services\IndexNow::submit($url);
                }
            }
        });
    }

    // Sanitized on write, not read — content is rendered unescaped ({!! !!}) in
    // blog/show, so stored HTML must already be safe rather than trusted at
    // render time. See Page::setContentAttribute() for the same pattern.
    public function setContentAttribute($value)
    {
        $this->attributes['content'] = $value !== null ? clean($value, 'rich_content') : $value;
    }

    public function setAltContentAttribute($value)
    {
        $this->attributes['alt_content'] = $value !== null && trim(strip_tags((string) $value)) !== '' ? clean($value, 'rich_content') : null;
    }

    /** The post's main language ('bn' / 'en'). */
    public function primaryLang(): string
    {
        return array_key_exists((string) $this->lang, self::LANGS) ? $this->lang : 'en';
    }

    /** The second language, when the post has a version in it. */
    public function altLang(): ?string
    {
        return $this->hasAlt() ? ($this->primaryLang() === 'bn' ? 'en' : 'bn') : null;
    }

    public function hasAlt(): bool
    {
        return trim((string) $this->alt_title) !== '' && trim(strip_tags((string) $this->alt_content)) !== '';
    }

    /** lang => URL for every language this post is published in (main language first). */
    public function languageUrls(): array
    {
        $urls = [$this->primaryLang() => route('blog.show', $this)];
        if ($alt = $this->altLang()) {
            $urls[$alt] = route('blog.show.lang', [$this, $alt]);
        }

        return $urls;
    }

    /** title / excerpt / content / meta in one language (falls back to the main language). */
    public function inLang(?string $lang): array
    {
        $alt = $lang !== null && $lang === $this->altLang();

        return [
            'lang'             => $alt ? $lang : $this->primaryLang(),
            'title'            => $alt ? $this->alt_title : $this->title,
            'excerpt'          => $alt ? $this->alt_excerpt : $this->excerpt,
            'content'          => (string) ($alt ? $this->alt_content : $this->content),
            'meta_title'       => $alt ? $this->alt_meta_title : $this->meta_title,
            'meta_description' => $alt ? $this->alt_meta_description : $this->meta_description,
        ];
    }

    public function category() { return $this->belongsTo(BlogCategory::class, 'blog_category_id'); }
    public function author()   { return $this->belongsTo(User::class, 'user_id'); }
    public function tags()     { return $this->belongsToMany(BlogTag::class, 'blog_post_tag'); }

    /** Products the author attached to this post, in their chosen order (pivot note = "why we picked it"). */
    public function products()
    {
        return $this->belongsToMany(Product::class, 'blog_post_product')
            ->withPivot(['sort_order', 'note'])
            ->orderBy('blog_post_product.sort_order');
    }

    public function reads()       { return $this->hasMany(BlogRead::class); }
    public function conversions() { return $this->hasMany(BlogConversion::class); }

    public function scopePublished($query)
    {
        return $query->where('status', 'published')->where('published_at', '<=', now());
    }

    public function scopeFeatured($query) { return $query->where('is_featured', true); }

    public function getRouteKeyName() { return 'slug'; }
}
