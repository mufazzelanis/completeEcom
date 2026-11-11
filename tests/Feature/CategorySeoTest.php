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

    public function test_a_parent_with_the_same_name_is_skipped_for_the_next_one_up(): void
    {
        $root = $this->category("Personal Care", "personal-care");
        $hair = $this->category("Hair Care", "hair-care", $root->id);
        $inner = $this->category("Hair Care", "hair-care-2", $hair->id);

        $this->get(route("shop.category", $inner))->assertOk()
            ->assertSee("<title>Hair Care (Personal Care) Price in Bangladesh", false)
            ->assertDontSee("Hair Care (Hair Care)", false);
    }

    public function test_a_sub_category_named_like_its_top_category_is_worded_apart(): void
    {
        $top = $this->category('Hair care', 'hair-care');
        $sub = $this->category('hair care', 'hair-care-2', $top->id);

        $this->get(route('shop.category', $sub))->assertOk()->assertSee('<title>Hair Care Products Price in Bangladesh', false);
        $this->get(route('shop.category', $top))->assertOk()->assertSee('<title>Hair Care Price in Bangladesh', false);
    }

    public function test_a_long_category_title_drops_buy_online_to_stay_readable_in_google(): void
    {
        $food = $this->category('Health Foods Item', 'foods-item');
        $long = $this->category('Food Supplements', 'food-supplements', $food->id);
        $this->category('Food Supplements', 'food-supplements-2');
        $short = $this->category('Baby', 'baby');

        $this->get(route('shop.category', $long))->assertOk()->assertSee('<title>Food Supplements (Health Foods Item) Price in Bangladesh |', false)->assertDontSee('Buy Online |', false);
        $this->get(route('shop.category', $short))->assertOk()->assertSee('<title>Baby Price in Bangladesh – Buy Online |', false);
    }

    public function test_a_unique_category_keeps_its_short_title(): void
    {
        $parent = $this->category('Baby', 'baby');
        $child = $this->category('Lotion', 'lotion', $parent->id);

        $this->get(route('shop.category', $child))->assertOk()->assertSee('<title>Lotion Price in Bangladesh', false);
    }
}
