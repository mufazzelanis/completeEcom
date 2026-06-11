<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;

class HomeController extends Controller
{
    public function index()
    {
        $featuredProducts = Product::with('category')->active()->featured()->latest()->take(8)->get();
        $newArrivals = Product::with('category')->active()->latest()->take(8)->get();
        $categories = Category::where('is_active', true)->withCount('products')->take(6)->get();

        return view('home', compact('featuredProducts', 'newArrivals', 'categories'));
    }
}
