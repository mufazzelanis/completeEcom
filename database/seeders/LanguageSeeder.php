<?php

namespace Database\Seeders;

use App\Models\Language;
use Illuminate\Database\Seeder;

class LanguageSeeder extends Seeder
{
    public function run(): void
    {
        $languages = [
            ['code' => 'en', 'name' => 'English', 'native_name' => 'English', 'flag_emoji' => '🇬🇧', 'direction' => 'ltr', 'is_default' => true,  'sort_order' => 1],
            ['code' => 'bn', 'name' => 'Bengali',  'native_name' => 'বাংলা',    'flag_emoji' => '🇧🇩', 'direction' => 'ltr', 'is_default' => false, 'sort_order' => 2],
            ['code' => 'ar', 'name' => 'Arabic',   'native_name' => 'العربية',  'flag_emoji' => '🇸🇦', 'direction' => 'rtl', 'is_default' => false, 'sort_order' => 3],
            ['code' => 'hi', 'name' => 'Hindi',    'native_name' => 'हिन्दी',    'flag_emoji' => '🇮🇳', 'direction' => 'ltr', 'is_default' => false, 'sort_order' => 4],
        ];

        foreach ($languages as $lang) {
            Language::updateOrCreate(['code' => $lang['code']], $lang + ['is_active' => true]);
        }
    }
}
