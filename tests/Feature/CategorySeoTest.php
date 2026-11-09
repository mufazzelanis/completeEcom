<?php

namespace Tests\Feature;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategorySeoTest extends TestCase
{
    use RefreshDatabase;

    private function category(string $name, string $slug, ?int $parent = null): Category
    {
        return Category::forceCreate(['name' => $name, 'slug' => $slug, 'parent_id' => $parent, 'is_active' => true]);
    }

    public function test_same_named_categories_get_different_titles_and_descriptions(): void
    {
        $womens = $this->category('Womens Care', 'womens-care');
        $mens = $this->category('Mens Care', 'mens-care');
        $one = $this->category('Eye Care', 'eye-care', $womens->id);
        $two = $this->category('Eye Care', 'eye-care-2', $mens->id);

        $this->get(route('shop.category', $one))->assertOk()
            ->assertSee('<title>Eye Care (Womens Care) Price in Bangladesh', false)
            ->assertSee('Shop Eye Care (Womens Care) online', false);
        $this->get(route('shop.category', $two))->assertOk()
            ->assertSee('<title>Eye Care (Mens Care) Price in Bangladesh', false);
    }

    public function test_a_unique_category_keeps_its_short_title(): void
    {
        $parent = $this->category('Baby', 'baby');
        $child = $this->category('Lotion', 'lotion', $parent->id);

        $this->get(route('shop.category', $child))->assertOk()->assertSee('<title>Lotion Price in Bangladesh', false);
    }
}
