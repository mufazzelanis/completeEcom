<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** The branded "page not found" page: still a real 404 / 410, never indexed, and it leads somewhere. */
class NotFoundPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_unknown_address_shows_the_branded_page_with_a_real_404(): void
    {
        $cat = Category::forceCreate(['name' => 'Face Care', 'slug' => 'face-care', 'is_active' => true]);
        Product::forceCreate(['category_id' => $cat->id, 'name' => 'CeraVe Hydrating Cleanser', 'slug' => 'cerave-hydrating-cleanser',
            'price' => 1500, 'stock' => 5, 'is_active' => true, 'approval_status' => 'approved', 'image' => 'products/x.webp']);

        $this->get('/old-cerave-link-xyz')->assertNotFound()
            ->assertSee('noindex, follow', false)
            ->assertSee('Oops! Page not found.')
            ->assertSee('name="search"', false)
            ->assertSee(url('/shop'), false)
            ->assertSee('Were you looking for one of these?') // from the words in the address
            ->assertSee('CeraVe Hydrating Cleanser')
            ->assertSee('Face Care');                   // popular categories

        // 404s inside known sections use it too
        $this->get('/pages/does-not-exist')->assertNotFound()->assertSee('Oops! Page not found.');
    }

    /** Live: a running flash sale, served from the cache on the second visit, broke the page (cached model came back incomplete). */
    public function test_page_works_from_the_cache_with_a_live_flash_sale(): void
    {
        config(['cache.default' => 'array']);
        \App\Models\FlashSale::forceCreate(['name' => 'Winter Sale', 'slug' => 'winter-sale', 'starts_at' => now()->subDay(), 'ends_at' => now()->addDay(), 'is_active' => true]);

        $this->get('/locas')->assertNotFound()->assertSee('Winter Sale');
        $this->get('/locas')->assertNotFound()->assertSee('Winter Sale')->assertSee('Oops! Page not found.'); // from the cache
    }

    public function test_gone_pages_use_the_same_design(): void
    {
        $this->get('/wp-json/anything')->assertStatus(410)->assertSee('This page has moved on.')->assertSee('noindex, follow', false);
    }
}
