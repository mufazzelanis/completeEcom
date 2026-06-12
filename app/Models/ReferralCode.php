<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ReferralCode extends Model
{
    protected $fillable = ['user_id', 'code', 'total_uses', 'total_earned', 'is_active'];

    protected $casts = [
        'total_earned' => 'decimal:2',
        'is_active'    => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function rewards()
    {
        return $this->hasMany(ReferralReward::class);
    }

    public static function generateFor(User $user): self
    {
        $code = strtoupper(Str::random(3) . '-' . $user->id . '-' . Str::random(3));
        return self::create(['user_id' => $user->id, 'code' => $code]);
    }

    public static function forUser(User $user): self
    {
        return self::firstOrCreate(
            ['user_id' => $user->id],
            ['code' => strtoupper(Str::random(3) . $user->id . Str::random(3))]
        );
    }
}
