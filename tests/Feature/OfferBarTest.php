<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Home offer bar: the coupon in the admin's text becomes a tap-to-copy ticket. */
class OfferBarTest extends TestCase
{
    use RefreshDatabase;

    private function offer(string $text): void
    {
        Setting::set('announcement_enabled', '1');
        Setting::set('announcement_text', $text);
    }

    public function test_a_code_in_the_text_becomes_a_copy_ticket(): void
    {
        $this->offer('🎉 Sale! Use code SAVE02 for 02% off all orders.');

        $this->get('/')->assertOk()
            ->assertSee('class="ann"', false)
            ->assertSee('Use code SAVE02 for 02% off all orders.')
            ->assertSee('class="ann-code"', false)
            ->assertSee('Tap to copy')
            ->assertSee("copy('SAVE02')", false);
    }

    public function test_code_written_other_ways_is_found(): void
    {
        $this->offer('Flat 10% off — <b>Code: winter10</b>');
        $this->get('/')->assertSee("copy('WINTER10')", false);
    }

    public function test_plain_offer_has_no_ticket_and_other_pages_have_no_bar(): void
    {
        $this->offer('Free delivery over ৳2000 this week');

        $this->get('/')->assertOk()->assertSee('Free delivery over')->assertDontSee('class="ann-code"', false);
        $this->get('/shop')->assertOk()->assertDontSee('class="ann"', false);
    }
}
