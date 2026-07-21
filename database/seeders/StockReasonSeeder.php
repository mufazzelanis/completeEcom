<?php

namespace Database\Seeders;

use App\Models\StockReason;
use Illuminate\Database\Seeder;

class StockReasonSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            ['label' => 'Stock count correction', 'type' => 'any',         'sort_order' => 0],
            ['label' => 'New purchase received',  'type' => 'purchase_in', 'sort_order' => 1],
            ['label' => 'Supplier restock',        'type' => 'manual_in',  'sort_order' => 2],
            ['label' => 'Customer returned item',  'type' => 'return_in',  'sort_order' => 3],
            ['label' => 'Damaged in warehouse',    'type' => 'damage_out', 'sort_order' => 4],
            ['label' => 'Expired product',         'type' => 'damage_out', 'sort_order' => 5],
            ['label' => 'Lost / theft',             'type' => 'manual_out', 'sort_order' => 6],
            ['label' => 'Internal use / sample',    'type' => 'manual_out', 'sort_order' => 7],
        ];

        foreach ($defaults as $reason) {
            StockReason::firstOrCreate(
                ['label' => $reason['label']],
                ['type' => $reason['type'], 'sort_order' => $reason['sort_order'], 'is_active' => true]
            );
        }
    }
}
