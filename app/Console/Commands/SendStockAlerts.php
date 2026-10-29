<?php

namespace App\Console\Commands;

use App\Http\Controllers\Admin\CheckoutLeadController;
use App\Models\Product;
use App\Models\StockAlert;
use App\Services\Notifications\Channels\SmsChannel;
use Illuminate\Console\Command;

/**
 * Texts everyone waiting for a product that's in stock again ("Notify me when it's back").
 * Runs from the scheduler, so it catches a restock however it happened (product edit, stock
 * adjustment, purchase, import). Without an SMS gateway nothing is sent — Admin → Stock Alerts
 * then shows "Back in stock" next to those numbers, with WhatsApp / Call buttons.
 */
class SendStockAlerts extends Command
{
    protected $signature = 'stock-alerts:send {--dry-run : List who would be texted, send nothing}';

    protected $description = 'SMS shoppers whose "notify me" product is back in stock';

    public function handle(SmsChannel $sms): int
    {
        if (!CheckoutLeadController::smsReady() && !$this->option('dry-run')) {
            $this->info('No SMS gateway configured — restocks are shown in Admin → Stock Alerts instead.');

            return self::SUCCESS;
        }

        $productIds = StockAlert::where('status', 'waiting')->distinct()->pluck('product_id');
        $sent = 0;
        foreach (Product::active()->whereIn('id', $productIds)->get() as $product) {
            if ($product->available_stock < 1) {
                continue;
            }
            $text = "Good news! {$product->name} is back in stock at " . setting('site_name', config('app.name')) . '. Order now: ' . route('products.show', $product);
            foreach (StockAlert::where('product_id', $product->id)->where('status', 'waiting')->limit(200)->get() as $alert) {
                if ($this->option('dry-run')) {
                    $this->line("{$alert->phone}: {$text}");
                    continue;
                }
                $sms->send($alert->user_id, 'stock_alert', $alert->phone, $text);
                $alert->update(['status' => 'notified', 'notified_at' => now()]);
                $sent++;
            }
        }
        $this->info("{$sent} back-in-stock SMS sent.");

        return self::SUCCESS;
    }
}
