<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    protected $fillable = ['name', 'display_name', 'group'];

    public static function allGrouped(): array
    {
        return static::orderBy('group')->orderBy('display_name')
            ->get()
            ->groupBy('group')
            ->toArray();
    }

    public static function forRole(string $role): array
    {
        return \DB::table('role_permissions')
            ->where('role', $role)
            ->pluck('permission_id')
            ->toArray();
    }
}
