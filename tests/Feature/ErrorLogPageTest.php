<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Admin → Security → Error Log: recent server errors, grouped, without opening the file manager. */
class ErrorLogPageTest extends TestCase
{
    use RefreshDatabase;

    private string $file;

    protected function setUp(): void
    {
        parent::setUp();
        $this->file = storage_path('logs/zz-error-log-test.log');
        $now = now()->format('Y-m-d H:i:s');
        file_put_contents($this->file, implode("\n", [
            "[{$now}] production.ERROR: SQLSTATE[HY000] [1040] Too many connections (Connection: mysql, SQL: select * from `sessions` where `id` = abc123) {\"exception\":\"[object] (Illuminate\\\\Database\\\\QueryException(code: 1040): x at /home/u1/public_html/vendor/laravel/framework/src/Illuminate/Database/Connection.php:825)",
            "[stacktrace]",
            "#0 /home/u1/public_html/app/Http/Middleware/VisitorId.php(24): handle()",
            "\"}",
            "[{$now}] production.ERROR: SQLSTATE[HY000] [1040] Too many connections (Connection: mysql, SQL: select * from `sessions` where `id` = def456) {\"exception\":\"x\"}",
            "[{$now}] production.WARNING: Facebook Conversions API event failed {\"event_name\":\"ViewContent\"}",
            "[{$now}] production.INFO: all fine",
            '',
        ]));
        touch($this->file, time() + 60); // the newest log, so it's read
        Setting::set('two_factor_enabled', '0');
    }

    protected function tearDown(): void
    {
        @unlink($this->file);
        parent::tearDown();
    }

    public function test_admin_sees_grouped_errors_and_can_include_warnings(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('admin.error-log.index'))->assertOk()
            ->assertSee('Too many connections')
            ->assertSee('2×')                                  // the same error, different ids → one group
            ->assertSee('app/Http/Middleware/VisitorId.php:24')
            ->assertDontSee('Facebook Conversions API event failed') // warnings hidden by default
            ->assertDontSee('all fine');

        $this->actingAs($admin)->get(route('admin.error-log.index', ['warnings' => 1]))->assertOk()
            ->assertSee('Facebook Conversions API event failed');
    }

    public function test_customers_cannot_open_it(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'customer']))->get(route('admin.error-log.index'))->assertStatus(403);
    }
}
