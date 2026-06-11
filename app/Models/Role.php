<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Role extends Model
{
    protected $fillable = ['name', 'display_name', 'description', 'can_access_admin', 'is_system'];

    protected $casts = ['can_access_admin' => 'boolean', 'is_system' => 'boolean'];

    public function permissionCount(): int
    {
        return DB::table('role_permissions')->where('role', $this->name)->count();
    }

    public function userCount(): int
    {
        return User::where('role', $this->name)->count();
    }
}
