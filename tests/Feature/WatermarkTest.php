<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Support\Watermark;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The store's logo on product photos: customers get the marked copy, Google Merchant the clean one,
 * and nothing about it may cost a page view any speed or break an existing image address.
 */
class WatermarkTest extends TestCase
{
    use RefreshDatabase;

    private const PHOTO = 'products/photo.jpg';

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Watermark::flush();
        Setting::set('two_factor_enabled', '0');
        Setting::bust();

        Storage::disk('public')->put(self::PHOTO, $this->jpeg());
        $category = Category::forceCreate(['name' => 'Demo', 'slug' => 'demo']);
        $this->product = Product::forceCreate([
            'category_id' => $category->id, 'name' => 'Demo', 'slug' => 'demo', 'price' => 100, 'stock' => 5,
            'is_active' => true, 'approval_status' => 'approved', 'image' => self::PHOTO,
        ]);
    }

    protected function tearDown(): void
    {
        Watermark::flush();
        parent::tearDown();
    }

    private function jpeg(int $shade = 245): string
    {
        $im = imagecreatetruecolor(1000, 1000);
        imagefill($im, 0, 0, imagecolorallocate($im, $shade, $shade, $shade));
        ob_start();
        imagejpeg($im, null, 92);

        return ob_get_clean();
    }

    private function logoPng(bool $transparent = true): string
    {
        $im = imagecreatetruecolor(300, 100);
        imagealphablending($im, false);
        imagesavealpha($im, true);
        imagefilledrectangle($im, 0, 0, 300, 100, imagecolorallocatealpha($im, 255, 255, 255, $transparent ? 127 : 0));
        imagefilledrectangle($im, 40, 30, 260, 70, imagecolorallocatealpha($im, 10, 120, 60, 0));
        ob_start();
        imagepng($im);

        return ob_get_clean();
    }

    private function saveLook(array $over = []): array
    {
        Storage::disk('public')->put('settings/watermark/logo.png', $this->logoPng());
        Watermark::saveLook($over + ['logo' => 'settings/watermark/logo.png'] + Watermark::DEFAULTS);

        return Watermark::desired();
    }

    private function goLive(array $over = []): array
    {
        $cfg = $this->saveLook($over);
        $this->artisan('watermark:build', ['--seconds' => 30])->assertSuccessful();
        Watermark::flush();

        return $cfg;
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_with_the_watermark_off_nothing_changes(): void
    {
        $this->assertStringContainsString('/storage/thumbs/800/', image_thumb(self::PHOTO, 800));
        $this->get('/storage/products/photo.jpg')->assertOk();
    }

    public function test_a_saved_look_stays_off_the_site_until_its_files_are_ready(): void
    {
        $cfg = $this->saveLook();

        $this->assertNull(Watermark::live());
        $this->assertStringContainsString('/storage/thumbs/800/', image_thumb(self::PHOTO, 800));

        $this->artisan('watermark:build', ['--seconds' => 30])->assertSuccessful();
        Watermark::flush();

        foreach (Watermark::WIDTHS as $width) {
            Storage::disk('public')->assertExists(Watermark::file($cfg['v'], $width, self::PHOTO, 'webp'));
        }
        $this->assertSame($cfg['v'], Watermark::live()['v']);
        $this->assertStringContainsString("/storage/wm/{$cfg['v']}/800/", image_thumb(self::PHOTO, 800));
    }

    public function test_card_thumbnails_stay_clean(): void
    {
        $this->goLive();

        foreach ([96, 200, 300] as $width) {
            $this->assertStringContainsString("/storage/thumbs/{$width}/", image_thumb(self::PHOTO, $width));
        }
    }

    public function test_other_images_are_never_marked(): void
    {
        $this->goLive();

        $this->assertStringContainsString('/storage/thumbs/800/', image_thumb('banners/hero.jpg', 800));
    }

    public function test_the_mark_is_really_in_the_image_and_faint(): void
    {
        $cfg = $this->goLive();
        $im = imagecreatefromstring(Storage::disk('public')->get(Watermark::file($cfg['v'], 800, self::PHOTO, 'webp')));

        $lum = fn (int $x, int $y) => (imagecolorat($im, $x, $y) >> 16) & 0xFF;
        $background = $lum(20, 20);
        $underLogo = $lum(400, 400);

        $this->assertLessThan($background, $underLogo, 'the logo darkens the photo where it sits');
        $this->assertLessThan(30, $background - $underLogo, 'and only faintly (default 8%)');
    }

    public function test_an_image_response_starts_no_session_and_is_cacheable(): void
    {
        $cfg = $this->saveLook();
        Watermark::publish();

        $res = $this->get('/storage/wm/' . $cfg['v'] . '/800/' . self::PHOTO . '.webp')->assertOk();

        $this->assertSame([], $res->headers->getCookies());
        $this->assertStringContainsString('immutable', $res->headers->get('Cache-Control'));
        $this->assertSame('image/webp', $res->headers->get('Content-Type'));
    }

    public function test_a_plain_thumbnail_starts_no_session_either(): void
    {
        $res = $this->get('/storage/thumbs/1200/' . self::PHOTO . '.webp')->assertOk();

        $this->assertSame([], $res->headers->getCookies());
        $this->assertStringContainsString('immutable', $res->headers->get('Cache-Control'));
    }

    public function test_changing_the_logo_makes_new_addresses_and_old_ones_still_work(): void
    {
        $old = $this->goLive();
        $new = $this->goLive(['opacity' => 12]);

        $this->assertGreaterThan($old['v'], $new['v']);
        Storage::disk('public')->assertMissing("wm/{$old['v']}/800/" . self::PHOTO . '.webp');
        $this->get("/storage/wm/{$old['v']}/800/" . self::PHOTO . '.webp')
            ->assertRedirect(Watermark::url(self::PHOTO, 800));
    }

    public function test_saving_the_same_look_does_not_rebuild_everything(): void
    {
        $first = $this->goLive();
        Watermark::saveLook(['logo' => $first['logo']] + Watermark::DEFAULTS);

        $this->assertSame($first['v'], Watermark::desired()['v']);
    }

    public function test_the_original_address_lands_on_the_marked_copy(): void
    {
        $this->goLive();

        $res = $this->get('/storage/products/photo.jpg');

        $res->assertStatus(302);
        $this->assertStringContainsString('/storage/wm/', $res->headers->get('Location'));
        $this->assertStringEndsWith('.jpg', $res->headers->get('Location'));
    }

    public function test_a_gif_photo_is_resized_and_marked_like_any_other(): void
    {
        $im = imagecreatetruecolor(1000, 1000);
        imagefill($im, 0, 0, imagecolorallocate($im, 240, 240, 240));
        ob_start();
        imagegif($im);
        Storage::disk('public')->put('products/old.gif', ob_get_clean());
        $this->product->update(['image' => 'products/old.gif']);
        $cfg = $this->goLive();

        Storage::disk('public')->assertExists(Watermark::file($cfg['v'], 800, 'products/old.gif', 'webp'));
        $this->assertStringContainsString("/storage/wm/{$cfg['v']}/800/", image_thumb('products/old.gif', 800));

        $card = image_thumb('products/old.gif', 300);
        $this->assertStringEndsWith('thumbs/300/products/old.gif.webp', $card);
        $this->get('/storage/thumbs/300/products/old.gif.webp')->assertOk();
        $this->assertSame(300, getimagesize(Storage::disk('public')->path('thumbs/300/products/old.gif.webp'))[0]);

        $res = $this->get('/storage/products/old.gif');
        $res->assertStatus(302);
        $this->assertStringContainsString('/storage/wm/', $res->headers->get('Location'));
    }

    public function test_originals_can_be_left_public(): void
    {
        $this->goLive();
        Setting::set('watermark_protect', '0', 'watermark');

        $this->get('/storage/products/photo.jpg')->assertOk();
    }

    public function test_a_large_unmarked_thumbnail_cannot_be_requested(): void
    {
        $this->goLive();

        $this->get('/storage/thumbs/800/' . self::PHOTO . '.webp')->assertRedirect(Watermark::url(self::PHOTO, 800));
        Storage::disk('public')->assertMissing('thumbs/800/' . self::PHOTO . '.webp');
    }

    public function test_one_product_can_opt_out(): void
    {
        $this->goLive();

        $this->product->update(['watermark_off' => true]);
        Watermark::flush();

        $this->assertStringContainsString('/storage/thumbs/800/', image_thumb(self::PHOTO, 800));
        $this->get('/storage/products/photo.jpg')->assertOk();
    }

    public function test_sellers_products_can_be_left_alone(): void
    {
        $this->goLive();
        $vendorId = \Illuminate\Support\Facades\DB::table('vendors')->insertGetId(['user_id' => $this->admin()->id, 'business_name' => 'Seller', 'slug' => 'seller']);
        $this->product->update(['seller_id' => $vendorId]);
        Watermark::flush();
        $this->assertStringContainsString('/storage/wm/', image_thumb(self::PHOTO, 800));

        Setting::set('watermark_skip_sellers', '1', 'watermark');
        Watermark::forgetExclusions();

        $this->assertStringContainsString('/storage/thumbs/800/', image_thumb(self::PHOTO, 800));
    }

    public function test_google_merchant_gets_the_clean_photo_at_a_signed_address(): void
    {
        $this->goLive();

        $url = Watermark::cleanUrl(self::PHOTO);
        $this->assertStringContainsString('/media/', $url);
        $this->assertSame($this->jpeg(), $this->get($url)->assertOk()->streamedContent());

        $this->get('/media/' . str_repeat('a', 24) . '/' . self::PHOTO)->assertNotFound();
        $this->get('/media/' . Watermark::signature(self::PHOTO) . '/products/other.jpg')->assertNotFound();
    }

    public function test_the_feed_never_lists_a_marked_image(): void
    {
        $this->goLive();

        $xml = $this->get('/google-feed.xml')->assertOk()->getContent();

        $this->assertStringNotContainsString('/storage/wm/', $xml);
        $this->assertStringContainsString('/media/' . Watermark::signature(self::PHOTO) . '/' . self::PHOTO, $xml);
    }

    public function test_the_product_page_shows_the_marked_photo_and_no_clean_address(): void
    {
        $cfg = $this->goLive();

        $html = $this->get('/products/demo')->assertOk()->getContent();

        $this->assertStringContainsString("/storage/wm/{$cfg['v']}/1200/" . self::PHOTO . '.webp', $html);
        $this->assertStringContainsString("/storage/wm/{$cfg['v']}/1200/" . self::PHOTO . '.jpg', $html, 'og:image and JSON-LD: a real JPEG');
        $this->assertStringNotContainsString('/storage/' . self::PHOTO, $html);
    }

    public function test_turning_it_off_is_instant(): void
    {
        $this->goLive();

        Watermark::disable();

        $this->assertStringContainsString('/storage/thumbs/800/', image_thumb(self::PHOTO, 800));
        $this->get('/storage/products/photo.jpg')->assertOk();
    }

    public function test_path_tricks_are_refused(): void
    {
        $cfg = $this->saveLook();
        Watermark::publish();

        $this->get("/storage/wm/{$cfg['v']}/800/products/../../.env.webp")->assertNotFound();
        $this->get("/storage/wm/{$cfg['v']}/801/" . self::PHOTO . '.webp')->assertNotFound();
        $this->get("/storage/wm/{$cfg['v']}/800/banners/hero.jpg.webp")->assertNotFound();
        $this->get('/storage/products/../../.env')->assertNotFound();
    }

    public function test_only_admins_reach_the_settings(): void
    {
        $this->get('/admin/settings/watermark')->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create(['role' => 'customer']))->get('/admin/settings/watermark')->assertForbidden();
        $this->actingAs($this->admin())->get('/admin/settings/watermark')->assertOk()->assertSee('Logo on product photos');
    }

    public function test_a_logo_needs_a_transparent_background(): void
    {
        $admin = $this->admin();
        $solid = UploadedFile::fake()->createWithContent('solid.png', $this->logoPng(false));
        $clear = UploadedFile::fake()->createWithContent('clear.png', $this->logoPng());

        $this->actingAs($admin)->postJson('/admin/settings/watermark/logo', ['logo' => $solid])->assertStatus(422);
        $path = $this->actingAs($admin)->postJson('/admin/settings/watermark/logo', ['logo' => $clear])->assertOk()->json('path');

        Storage::disk('public')->assertExists($path);
        $this->assertTrue(Watermark::isLogoPath($path));
    }

    public function test_a_non_image_cannot_be_uploaded_as_a_logo(): void
    {
        $shell = UploadedFile::fake()->createWithContent('logo.png', '<?php echo 1;');

        $this->actingAs($this->admin())->postJson('/admin/settings/watermark/logo', ['logo' => $shell])->assertStatus(422);
    }

    public function test_the_preview_is_generated_without_touching_the_site(): void
    {
        $this->saveLook();

        $res = $this->actingAs($this->admin())->get('/admin/settings/watermark/preview?' . http_build_query([
            'logo' => 'settings/watermark/logo.png', 'image' => self::PHOTO, 'position' => 'tiled', 'size' => 30, 'opacity' => 10, 'tone' => 'auto',
        ]))->assertOk();

        $this->assertSame('image/webp', $res->headers->get('Content-Type'));
        $this->assertSame([], Storage::disk('public')->directories('wm'));
    }

    public function test_the_admin_form_saves_a_look_and_turns_it_off(): void
    {
        $admin = $this->admin();
        Storage::disk('public')->put('settings/watermark/a.png', $this->logoPng());
        $form = ['enabled' => 1, 'logo' => 'settings/watermark/a.png', 'position' => 'center', 'size' => 35, 'opacity' => 8, 'tone' => 'auto', 'protect' => 1, 'skip_sellers' => 0];

        $this->actingAs($admin)->post('/admin/settings/watermark', $form)->assertSessionHasNoErrors();
        Watermark::flush();
        $this->assertSame('settings/watermark/a.png', Watermark::desired()['logo']);

        $this->actingAs($admin)->post('/admin/settings/watermark', ['enabled' => 0] + $form)->assertSessionHasNoErrors();
        Watermark::flush();
        $this->assertNull(Watermark::desired());
    }
}
