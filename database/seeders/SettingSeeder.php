<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            // General
            ['key' => 'site_name',        'value' => 'ShopVista',              'group' => 'general'],
            ['key' => 'site_title',       'value' => 'ShopVista – Online Store','group' => 'general'],
            ['key' => 'site_tagline',     'value' => 'Your one-stop shop for everything you need.', 'group' => 'general'],
            ['key' => 'website_url',      'value' => config('app.url'),         'group' => 'general'],
            ['key' => 'company_name',     'value' => 'ShopVista Ltd.',          'group' => 'general'],
            ['key' => 'company_email',    'value' => 'info@shopvista.com',      'group' => 'general'],
            ['key' => 'company_phone',    'value' => '+880 1700-000000',        'group' => 'general'],
            ['key' => 'support_email',    'value' => 'support@shopvista.com',   'group' => 'general'],
            ['key' => 'company_address',  'value' => 'Dhaka, Bangladesh',       'group' => 'general'],
            // Localization
            ['key' => 'default_language', 'value' => 'en',                     'group' => 'localization'],
            ['key' => 'timezone',         'value' => 'Asia/Dhaka',             'group' => 'localization'],
            ['key' => 'date_format',      'value' => 'd M Y',                  'group' => 'localization'],
            ['key' => 'time_format',      'value' => 'h:i A',                  'group' => 'localization'],
            ['key' => 'country',          'value' => 'Bangladesh',             'group' => 'localization'],
            // Currency
            ['key' => 'currency_code',    'value' => 'BDT',                    'group' => 'currency'],
            ['key' => 'currency_symbol',  'value' => '৳',                      'group' => 'currency'],
            ['key' => 'currency_name',    'value' => 'Bangladeshi Taka',       'group' => 'currency'],
            ['key' => 'currency_position','value' => 'left',                   'group' => 'currency'],
            ['key' => 'decimal_places',   'value' => '0',                      'group' => 'currency'],
            ['key' => 'thousand_separator','value'=> ',',                      'group' => 'currency'],
            ['key' => 'decimal_separator','value' => '.',                      'group' => 'currency'],
            // Tax
            ['key' => 'tax_enabled',      'value' => '0',                      'group' => 'tax'],
            ['key' => 'tax_rate',         'value' => '0',                      'group' => 'tax'],
            ['key' => 'tax_included',     'value' => 'excluded',               'group' => 'tax'],
            // Shipping
            ['key' => 'free_shipping_enabled', 'value' => '1',                 'group' => 'shipping'],
            ['key' => 'free_shipping_min',     'value' => '999',               'group' => 'shipping'],
            ['key' => 'flat_rate_enabled',     'value' => '1',                 'group' => 'shipping'],
            ['key' => 'flat_rate_amount',      'value' => '60',                'group' => 'shipping'],
            ['key' => 'flat_rate_label',       'value' => 'Standard Delivery', 'group' => 'shipping'],
            ['key' => 'delivery_days',         'value' => '3-7',               'group' => 'shipping'],
            // Orders
            ['key' => 'order_prefix',     'value' => 'ORD-',                   'group' => 'orders'],
            ['key' => 'invoice_prefix',   'value' => 'INV-',                   'group' => 'invoice'],
            // Footer
            ['key' => 'copyright_text',   'value' => '© {year} ShopVista. All rights reserved.', 'group' => 'footer'],
            ['key' => 'footer_description','value'=> 'Your one-stop shop for everything you need.', 'group' => 'footer'],
            ['key' => 'newsletter_enabled','value'=> '1',                      'group' => 'footer'],
            // Header
            ['key' => 'sticky_header',    'value' => '1',                      'group' => 'header'],
            ['key' => 'top_bar_enabled',  'value' => '0',                      'group' => 'header'],
            // Branding
            ['key' => 'primary_color',    'value' => '#6366f1',                'group' => 'branding'],
            ['key' => 'secondary_color',  'value' => '#ec4899',                'group' => 'branding'],
            // SEO
            ['key' => 'seo_meta_title',   'value' => 'ShopVista – Online Store','group' => 'seo'],
            ['key' => 'seo_meta_description','value'=> 'Shop the best products online at ShopVista. Quality products, fast delivery, great prices.', 'group' => 'seo'],
            ['key' => 'sitemap_enabled',  'value' => '1',                      'group' => 'seo'],
            // Notifications
            ['key' => 'email_notifications_enabled',    'value' => '1', 'group' => 'notifications'],
            ['key' => 'sms_notifications_enabled',      'value' => '0', 'group' => 'notifications'],
            ['key' => 'push_notifications_enabled',     'value' => '0', 'group' => 'notifications'],
            ['key' => 'whatsapp_notifications_enabled', 'value' => '0', 'group' => 'notifications'],
            // Maintenance
            ['key' => 'maintenance_mode', 'value' => '0',                      'group' => 'maintenance'],
            // Security
            ['key' => 'login_max_attempts',    'value' => '5',  'group' => 'security'],
            ['key' => 'login_lockout_minutes', 'value' => '15', 'group' => 'security'],
        ];

        foreach ($defaults as $row) {
            Setting::firstOrCreate(['key' => $row['key']], $row);
        }

        $this->command->info('Settings seeded: ' . count($defaults) . ' defaults.');
    }
}
