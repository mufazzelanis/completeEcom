<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Admin user
        User::create([
            'name'     => 'Admin',
            'email'    => 'admin@shopvista.com',
            'password' => Hash::make('password'),
            'role'     => 'admin',
        ]);

        // Customer user
        User::create([
            'name'     => 'John Doe',
            'email'    => 'customer@shopvista.com',
            'password' => Hash::make('password'),
            'role'     => 'customer',
        ]);

        // Categories
        $categories = [
            ['name' => 'Electronics',   'description' => 'Gadgets and tech'],
            ['name' => 'Fashion',        'description' => 'Clothing and accessories'],
            ['name' => 'Home & Living',  'description' => 'Furniture and decor'],
            ['name' => 'Sports',         'description' => 'Sports and fitness gear'],
            ['name' => 'Books',          'description' => 'Books and stationery'],
            ['name' => 'Beauty',         'description' => 'Beauty and skincare'],
        ];

        foreach ($categories as $cat) {
            Category::create([
                'name'        => $cat['name'],
                'slug'        => Str::slug($cat['name']),
                'description' => $cat['description'],
                'is_active'   => true,
            ]);
        }

        // Products
        $products = [
            ['name' => 'iPhone 15 Pro', 'category' => 'Electronics', 'price' => 129900, 'stock' => 50, 'featured' => true],
            ['name' => 'Samsung Galaxy S24', 'category' => 'Electronics', 'price' => 99900, 'stock' => 40],
            ['name' => 'Sony WH-1000XM5 Headphones', 'category' => 'Electronics', 'price' => 35000, 'stock' => 30, 'featured' => true],
            ['name' => 'MacBook Pro M3', 'category' => 'Electronics', 'price' => 199900, 'stock' => 20],
            ['name' => 'Men\'s Casual T-Shirt', 'category' => 'Fashion', 'price' => 850, 'sale_price' => 699, 'stock' => 100],
            ['name' => 'Women\'s Summer Dress', 'category' => 'Fashion', 'price' => 1500, 'sale_price' => 1199, 'stock' => 80, 'featured' => true],
            ['name' => 'Running Shoes Nike', 'category' => 'Fashion', 'price' => 8500, 'stock' => 60],
            ['name' => 'Wooden Bookshelf', 'category' => 'Home & Living', 'price' => 12000, 'stock' => 15],
            ['name' => 'Premium Yoga Mat', 'category' => 'Sports', 'price' => 2500, 'sale_price' => 1999, 'stock' => 75, 'featured' => true],
            ['name' => 'JavaScript: The Good Parts', 'category' => 'Books', 'price' => 800, 'stock' => 50],
            ['name' => 'Face Moisturizer SPF50', 'category' => 'Beauty', 'price' => 1200, 'stock' => 120, 'featured' => true],
            ['name' => 'Wireless Earbuds Pro', 'category' => 'Electronics', 'price' => 4500, 'sale_price' => 3999, 'stock' => 90],
        ];

        foreach ($products as $p) {
            $category = Category::where('name', $p['category'])->first();
            Product::create([
                'category_id'       => $category->id,
                'name'              => $p['name'],
                'slug'              => Str::slug($p['name']),
                'short_description' => 'High quality ' . strtolower($p['name']),
                'description'       => 'This is a premium quality product. ' . $p['name'] . ' offers excellent value for money with top-notch features and durability.',
                'price'             => $p['price'],
                'sale_price'        => $p['sale_price'] ?? null,
                'stock'             => $p['stock'],
                'sku'               => strtoupper(Str::random(8)),
                'is_active'         => true,
                'is_featured'       => $p['featured'] ?? false,
            ]);
        }

        // Permissions + role defaults
        $this->call(PermissionSeeder::class);

        // Stock adjustment reason presets
        $this->call(StockReasonSeeder::class);

        // Coupons
        Coupon::create([
            'code'             => 'SAVE10',
            'type'             => 'percentage',
            'value'            => 10,
            'min_order_amount' => 500,
            'is_active'        => true,
        ]);

        Coupon::create([
            'code'             => 'FLAT100',
            'type'             => 'fixed',
            'value'            => 100,
            'min_order_amount' => 1000,
            'is_active'        => true,
        ]);
    }
}
