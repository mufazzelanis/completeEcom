<?php

namespace Database\Seeders;

use App\Models\HomeSection;
use Illuminate\Database\Seeder;

class HomeSectionSeeder extends Seeder
{
    public function run(): void
    {
        // view_all_query is left null so the "View All" link is generated automatically
        // from source_type + category_id (see HomeSection::getViewAllUrl()).
        $sections = [
            ['title' => 'Featured Products', 'subtitle' => 'Handpicked just for you', 'source_type' => 'featured', 'product_limit' => 8, 'theme' => 'light', 'sort_order' => 1],
            ['title' => 'Top Selling', 'subtitle' => 'Most popular products', 'source_type' => 'top_selling', 'product_limit' => 8, 'theme' => 'light', 'sort_order' => 2],
            ['title' => 'Deals & Offers', 'subtitle' => null, 'source_type' => 'on_sale', 'product_limit' => 8, 'theme' => 'sale', 'sort_order' => 3],
            ['title' => 'New Arrivals', 'subtitle' => 'Fresh finds every day', 'source_type' => 'new_arrivals', 'product_limit' => 8, 'theme' => 'light', 'sort_order' => 4],
        ];

        foreach ($sections as $section) {
            HomeSection::updateOrCreate(['title' => $section['title']], $section + ['is_active' => true]);
        }
    }
}
