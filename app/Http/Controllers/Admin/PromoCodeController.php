<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PromoCode;
use App\Models\PromoCodeBatch;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

class PromoCodeController extends Controller
{
    public function index()
    {
        $batches = PromoCodeBatch::latest()->paginate(15);
        return view('admin.promo-codes.index', compact('batches'));
    }

    public function create()
    {
        return view('admin.promo-codes.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'             => 'required|string|max:255',
            'prefix'           => 'nullable|string|max:10|alpha_num',
            'discount_type'    => 'required|in:percentage,fixed',
            'discount_value'   => 'required|numeric|min:0.01',
            'min_order_amount' => 'nullable|numeric|min:0',
            'expires_at'       => 'nullable|date|after:today',
            'generate_count'   => 'required|integer|min:1|max:10000',
            'is_active'        => 'boolean',
        ]);

        $count = (int) $data['generate_count'];
        unset($data['generate_count']);
        $data['is_active'] = $request->boolean('is_active', true);

        // A single code with a prefix is used exactly as typed, not prefix+random (see
        // PromoCodeBatch::generate()) — the one case where a collision is actually likely
        // (two campaigns both trying "BF26"), so it's checked here with a friendly message
        // instead of letting the unique constraint on promo_codes.code throw a raw 500.
        if ($count === 1 && !empty($data['prefix']) && PromoCode::where('code', strtoupper($data['prefix']))->exists()) {
            return back()->withInput()->withErrors(['prefix' => 'This code is already in use — pick a different one.']);
        }

        $batch = PromoCodeBatch::create($data);
        $batch->generate($count);

        AuditLogger::log('promo_batch.created', "Promo batch \"{$batch->name}\" created with {$count} codes", $batch);

        return redirect()->route('admin.promo-codes.index')->with('success', "{$count} promo codes generated.");
    }

    public function show(PromoCodeBatch $promoCode)
    {
        $promoCode->load(['codes' => fn($q) => $q->with('user', 'order')->latest()->take(200)]);
        return view('admin.promo-codes.show', ['batch' => $promoCode]);
    }

    public function destroy(PromoCodeBatch $promoCode)
    {
        AuditLogger::log('promo_batch.deleted', "Promo batch \"{$promoCode->name}\" deleted");
        $promoCode->delete();
        return back()->with('success', 'Promo code batch deleted.');
    }

    public function download(PromoCodeBatch $promoCode)
    {
        $codes = $promoCode->codes()->whereNull('used_at')->pluck('code');

        $csv = "Code\n" . $codes->implode("\n");

        return response($csv, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"promo-{$promoCode->name}.csv\"",
        ]);
    }

    public function toggle(Request $request, PromoCodeBatch $promoCode)
    {
        $promoCode->update(['is_active' => ! $promoCode->is_active]);
        $message = 'Batch ' . ($promoCode->is_active ? 'activated' : 'deactivated') . '.';

        if ($request->expectsJson()) {
            return response()->json(['is_active' => $promoCode->is_active, 'message' => $message]);
        }

        return back()->with('success', $message);
    }
}
