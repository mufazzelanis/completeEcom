<?php

namespace App\Http\Controllers;

use App\Models\LoginActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SecurityController extends Controller
{
    public function index()
    {
        $activities = collect();

        if (Schema::hasTable('login_activities')) {
            $activities = LoginActivity::where('user_id', auth()->id())
                ->latest('created_at')
                ->take(20)
                ->get();
        }

        return view('account.security.index', compact('activities'));
    }
}
