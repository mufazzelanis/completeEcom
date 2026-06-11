<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->enum('type', ['cod', 'mobile_banking', 'bank_transfer', 'card']);
            $table->string('description')->nullable();
            $table->string('logo')->nullable();
            $table->string('account_name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('branch')->nullable();
            $table->string('routing_number')->nullable();
            $table->text('instructions')->nullable();
            $table->enum('charge_type', ['none', 'fixed', 'percent'])->default('none');
            $table->decimal('charge_value', 8, 2)->default(0);
            $table->decimal('min_amount', 10, 2)->nullable();
            $table->decimal('max_amount', 10, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Seed default payment methods (all rows must have identical keys for bulk insert)
        $base = ['account_name' => null, 'account_number' => null, 'bank_name' => null,
                 'branch' => null, 'routing_number' => null, 'logo' => null,
                 'min_amount' => null, 'max_amount' => null,
                 'created_at' => now(), 'updated_at' => now()];

        DB::table('payment_methods')->insert([
            $base + [
                'name'         => 'Cash on Delivery',
                'slug'         => 'cod',
                'type'         => 'cod',
                'description'  => 'Pay when your order is delivered',
                'instructions' => 'Our delivery agent will collect payment at your doorstep.',
                'charge_type'  => 'none', 'charge_value' => 0,
                'is_active'    => true,   'sort_order'   => 1,
            ],
            $base + [
                'name'           => 'bKash',
                'slug'           => 'bkash',
                'type'           => 'mobile_banking',
                'description'    => 'Pay via bKash mobile banking',
                'account_name'   => 'ShopVista',
                'account_number' => '01XXXXXXXXX',
                'instructions'   => "Send to our bKash number and enter your TXN ID.\n1. bKash → Send Money\n2. Number: 01XXXXXXXXX\n3. Exact amount\n4. Reference: your order number",
                'charge_type'    => 'percent', 'charge_value' => 1.5,
                'is_active'      => true,      'sort_order'   => 2,
            ],
            $base + [
                'name'           => 'Nagad',
                'slug'           => 'nagad',
                'type'           => 'mobile_banking',
                'description'    => 'Pay via Nagad mobile banking',
                'account_name'   => 'ShopVista',
                'account_number' => '01XXXXXXXXX',
                'instructions'   => "Send to our Nagad number and enter your TXN ID.\n1. Nagad → Send Money\n2. Number: 01XXXXXXXXX\n3. Exact amount",
                'charge_type'    => 'none', 'charge_value' => 0,
                'is_active'      => true,   'sort_order'   => 3,
            ],
            $base + [
                'name'           => 'Rocket',
                'slug'           => 'rocket',
                'type'           => 'mobile_banking',
                'description'    => 'Pay via Rocket (DBBL) mobile banking',
                'account_name'   => 'ShopVista',
                'account_number' => '01XXXXXXXXX',
                'instructions'   => "Send to our Rocket number and enter your TXN ID.",
                'charge_type'    => 'none', 'charge_value' => 0,
                'is_active'      => false,  'sort_order'   => 4,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
