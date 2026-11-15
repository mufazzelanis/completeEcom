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
            ->assertSee('পেজটি খুঁজে পাওয়া যাচ্ছে না')
            ->assertSee('name="search"', false)
            ->assertSee(url('/shop'), false)
            ->assertSee('আপনি কি এটা খুঁজছিলেন') // "did you mean" from the words in the address
            ->assertSee('CeraVe Hydrating Cleanser')
            ->assertSee('Face Care');                   // popular categories

        // 404s inside known sections use it too
        $this->get('/pages/does-not-exist')->assertNotFound()->assertSee('পেজটি খুঁজে পাওয়া যাচ্ছে না');
    }

    public function test_gone_pages_use_the_same_design(): void
    {
        $this->get('/wp-json/anything')->assertStatus(410)->assertSee('এই পেজটি আর নেই')->assertSee('noindex, follow', false);
    }
}
