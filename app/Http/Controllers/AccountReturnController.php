<?php

namespace App\Http\Controllers;

use App\Models\ProductReturn;

class AccountReturnController extends Controller
{
    public function index()
    {
        $returns = ProductReturn::where('user_id', auth()->id())
            ->with('order', 'items')
            ->latest()
            ->paginate(10);

        return view('account.returns.index', compact('returns'));
    }

    public function show(ProductReturn $return)
    {
        if ($return->user_id !== auth()->id()) {
            abort(403);
        }

        $return->load('order', 'items.product');

        return view('account.returns.show', compact('return'));
    }
}
