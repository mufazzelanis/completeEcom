<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Vendor extends Model
{
    protected $fillable = [
        'user_id', 'business_name', 'slug', 'logo', 'banner', 'description',
        'phone', 'email', 'website', 'commission_rate', 'status', 'payout_method',
        'payout_details', 'rejection_reason', 'correction_notes', 'approved_at', 'approved_by',
        'document_type', 'nid_number', 'nid_front_image', 'nid_back_image', 'birth_certificate_image',
        'pending_changes', 'profile_status', 'profile_rejection_reason',
    ];

    protected $casts = [
        'payout_details' => 'array',
        'pending_changes' => 'array',
        'commission_rate' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    /**
     * Fields a seller can propose changes to via their own profile page —
     * every one of these needs admin approval before it takes effect (unlike
     * document corrections, which use the separate needs_correction flow).
     */
    public const EDITABLE_PROFILE_FIELDS = [
        'business_name', 'phone', 'email', 'website', 'description',
        'logo', 'payout_method', 'payout_details',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($vendor) {
            if (empty($vendor->slug)) {
                $vendor->slug = Str::slug($vendor->business_name).'-'.Str::random(5);
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'seller_id');
    }

    public function transactions()
    {
        return $this->hasMany(VendorTransaction::class);
    }

    public function payouts()
    {
        return $this->hasMany(VendorPayout::class);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }

    public function statusBadge(): string
    {
        return match ($this->status) {
            'approved' => 'bg-green-100 text-green-700',
            'pending' => 'bg-yellow-100 text-yellow-700',
            'rejected' => 'bg-red-100 text-red-700',
            'suspended' => 'bg-gray-200 text-gray-600',
            'needs_correction' => 'bg-orange-100 text-orange-700',
            default => 'bg-gray-100 text-gray-600',
        };
    }

    public function profileStatusBadge(): string
    {
        return match ($this->profile_status) {
            'pending' => 'bg-yellow-100 text-yellow-700',
            'rejected' => 'bg-red-100 text-red-700',
            default => 'bg-gray-100 text-gray-600',
        };
    }

    /**
     * Applies a seller's approved profile-change proposal onto the live columns
     * and clears the pending state — called from the admin approve action.
     */
    public function applyPendingChanges(): void
    {
        $changes = $this->pending_changes ?? [];

        $this->update(array_merge($changes, [
            'pending_changes' => null,
            'profile_status' => 'none',
            'profile_rejection_reason' => null,
        ]));
    }
}
