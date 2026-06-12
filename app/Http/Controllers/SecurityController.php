<?php

namespace App\Http\Controllers;

use App\Models\LoginActivity;
use Illuminate\Http\Request;

class SecurityController extends Controller
{
    public function index()
    {
        $activities = LoginActivity::where('user_id', auth()->id())
            ->latest('created_at')
            ->take(20)
            ->get();
        return view('account.security.index', compact('activities'));
    }
}
