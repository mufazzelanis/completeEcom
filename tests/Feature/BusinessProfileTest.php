<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Support\BusinessProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** One form for the shop's details: what's saved must reach every place that shows them. */
class BusinessProfileTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Setting::set('two_factor_enabled', '0');
        Setting::bust();
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    private function save(array $over = [])
    {
        return $this->actingAs($this->admin)->post(route('admin.business.update'), $over + [
            'name' => 'New Shop', 'phone' => '01700000000', 'email' => 'hello@newshop.com', 'address' => 'Dhanmondi, Dhaka',
        ]);
    }

    private function given(array $settings): void
    {
        foreach ($settings as $key => $value) {
            Setting::set($key, $value);
        }
        Setting::bust();
    }

    public function test_one_save_updates_every_copy(): void
    {
        $this->given(['company_phone' => '1', 'contact_phone' => '2', 'support_email' => 'a@a.com', 'contact_email' => 'b@b.com', 'contact_address' => 'Old']);

        $this->save()->assertRedirect();

        foreach (['company_phone', 'contact_phone'] as $key) {
            $this->assertSame('01700000000', setting($key));
        }
        foreach (['company_email', 'support_email', 'contact_email'] as $key) {
            $this->assertSame('hello@newshop.com', setting($key));
        }
        foreach (['company_address', 'contact_address'] as $key) {
            $this->assertSame('Dhanmondi, Dhaka', setting($key));
        }
        foreach (['site_name', 'og_site_name', 'mail_from_name'] as $key) {
            $this->assertSame('New Shop', setting($key));
        }
    }

    public function test_the_storefront_shows_the_new_details(): void
    {
        $this->save();

        $this->get('/contact')->assertOk()->assertSee('hello@newshop.com')->assertSee('01700000000')->assertSee('Dhanmondi, Dhaka');
    }

    public function test_an_invoice_uses_the_new_details(): void
    {
        $this->given(['company_name' => 'New Shop']);
        $this->save(['name' => 'New Shop']);

        $store = \App\Support\InvoicePdf::store();

        $this->assertSame('New Shop', $store['name']);
        $this->assertSame('01700000000', $store['phone']);
        $this->assertSame('hello@newshop.com', $store['email']);
        $this->assertSame('Dhanmondi, Dhaka', $store['address']);
    }

    public function test_an_old_company_name_cannot_outlive_a_rename(): void
    {
        $this->given(['site_name' => 'Old Shop', 'company_name' => 'Old Shop']);

        $this->save();

        $this->assertSame('New Shop', \App\Support\InvoicePdf::store()['name']);
    }

    public function test_a_registered_company_name_is_kept_for_invoices(): void
    {
        $this->save(['legal_name' => 'New Shop Trading Ltd']);

        $this->assertSame('New Shop Trading Ltd', \App\Support\InvoicePdf::store()['name']);
        $this->assertSame('New Shop', setting('site_name'));
    }

    public function test_an_empty_top_bar_stays_hidden_and_a_used_one_is_updated(): void
    {
        $this->given(['topbar_phone' => '', 'topbar_email' => 'old@shop.com']);

        $this->save();

        $this->assertSame('', (string) setting('topbar_phone'));
        $this->assertSame('hello@newshop.com', setting('topbar_email'));
    }

    public function test_the_address_mail_is_sent_from_is_never_touched(): void
    {
        $this->given(['mail_from_address' => 'smtp@host.com']);

        $this->save();

        $this->assertSame('smtp@host.com', setting('mail_from_address'));
    }

    public function test_a_rename_reaches_seo_and_copyright_text_only_as_a_whole_word(): void
    {
        $this->given(['site_name' => 'Ani', 'seo_meta_title' => 'Ani – Best Shop', 'copyright_text' => '© {year} Ani', 'site_title' => 'Animal Store']);

        $this->save();

        $this->assertSame('New Shop – Best Shop', setting('seo_meta_title'));
        $this->assertSame('© {year} New Shop', setting('copyright_text'));
        $this->assertSame('Animal Store', setting('site_title'));
    }

    public function test_a_whatsapp_number_becomes_a_chat_link(): void
    {
        $this->save(['whatsapp' => '01711-223344']);
        $this->assertSame('https://wa.me/8801711223344', setting('whatsapp_link'));

        $this->save(['whatsapp' => 'https://wa.me/15551234567']);
        $this->assertSame('https://wa.me/15551234567', setting('whatsapp_link'));
    }

    public function test_clashing_copies_are_pointed_out_until_saved(): void
    {
        $this->given(['company_phone' => '111', 'contact_phone' => '222']);

        $this->assertArrayHasKey('phone', BusinessProfile::differences());
        $this->actingAs($this->admin)->get(route('admin.business.index'))->assertOk()->assertSee('111')->assertSee('222');

        $this->save();
        $this->assertSame([], BusinessProfile::differences());
    }

    public function test_one_logo_upload_serves_the_website_and_invoices(): void
    {
        Storage::fake('public');

        $this->save(['logo' => UploadedFile::fake()->image('logo.png', 300, 100)]);

        $this->assertNotEmpty(setting('site_logo'));
        $this->assertSame(setting('site_logo'), setting('invoice_logo'));
        Storage::disk('public')->assertExists(setting('site_logo'));
    }

    public function test_bad_input_is_refused_and_nothing_is_saved(): void
    {
        $this->given(['site_name' => 'Keep Me']);

        $this->save(['name' => ''])->assertSessionHasErrors('name');
        $this->save(['email' => 'not-an-email'])->assertSessionHasErrors('email');
        $this->save(['logo' => UploadedFile::fake()->create('shell.php', 10)])->assertSessionHasErrors('logo');

        $this->assertSame('Keep Me', setting('site_name'));
    }

    public function test_only_admins_reach_it(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $this->get(route('admin.business.index'))->assertRedirect();
        $this->actingAs($customer)->get(route('admin.business.index'))->assertForbidden();
        $this->actingAs($customer)->post(route('admin.business.update'), ['name' => 'Hacked'])->assertForbidden();
    }

    public function test_the_invoice_builder_opens_with_the_shops_details(): void
    {
        $this->save();

        $this->actingAs($this->admin)->get(route('admin.custom-invoices.create'))->assertOk()
            ->assertSee('hello@newshop.com')->assertSee('01700000000')->assertSee('pv-band', false);
    }
}
