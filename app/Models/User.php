<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'role', 'phone', 'avatar', 'is_active',
        'date_of_birth', 'gender', 'bio',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'date_of_birth' => 'date',
        ];
    }

    // ── Role helpers ──────────────────────────────────────────────────────────

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isVendor(): bool
    {
        return $this->role === 'vendor';
    }

    public function canAccessVendorPanel(): bool
    {
        return $this->isVendor() && $this->vendor?->status === 'approved';
    }

    public function canAccessAdmin(): bool
    {
        // Fast path for built-in system roles
        if ($this->role === 'admin' || $this->role === 'manager' || $this->role === 'staff') {
            return true;
        }
        if ($this->role === 'customer') {
            return false;
        }

        // Custom roles: check the roles table
        return DB::table('roles')->where('name', $this->role)->where('can_access_admin', true)->exists();
    }

    public function hasRole(string|array $role): bool
    {
        return is_array($role) ? in_array($this->role, $role) : $this->role === $role;
    }

    public static function roleLabel(string $role): string
    {
        $label = DB::table('roles')->where('name', $role)->value('display_name');

        return $label ?? ucfirst($role);
    }

    public static function roleBadgeClass(string $role): string
    {
        return match ($role) {
            'admin' => 'bg-red-100 text-red-700',
            'manager' => 'bg-purple-100 text-purple-700',
            'staff' => 'bg-blue-100 text-blue-700',
            'customer' => 'bg-gray-100 text-gray-600',
            default => 'bg-indigo-100 text-indigo-700',
        };
    }

    // ── Permission helpers ────────────────────────────────────────────────────

    public function hasPermission(string $permission): bool
    {
        if ($this->role === 'admin') {
            return true;
        }

        // Per-user deny overrides everything
        if (DB::table('user_permissions')
            ->join('permissions', 'permissions.id', '=', 'user_permissions.permission_id')
            ->where('user_permissions.user_id', $this->id)
            ->where('user_permissions.type', 'deny')
            ->where('permissions.name', $permission)
            ->exists()) {
            return false;
        }

        // Per-user explicit grant
        if (DB::table('user_permissions')
            ->join('permissions', 'permissions.id', '=', 'user_permissions.permission_id')
            ->where('user_permissions.user_id', $this->id)
            ->where('user_permissions.type', 'grant')
            ->where('permissions.name', $permission)
            ->exists()) {
            return true;
        }

        // Role-level permission
        return DB::table('role_permissions')
            ->join('permissions', 'permissions.id', '=', 'role_permissions.permission_id')
            ->where('role_permissions.role', $this->role)
            ->where('permissions.name', $permission)
            ->exists();
    }

    public function effectivePermissions(): array
    {
        if ($this->role === 'admin') {
            return Permission::pluck('name')->toArray();
        }

        $rolePerms = DB::table('role_permissions')
            ->join('permissions', 'permissions.id', '=', 'role_permissions.permission_id')
            ->where('role', $this->role)
            ->pluck('permissions.name')
            ->toArray();

        $grants = DB::table('user_permissions')
            ->join('permissions', 'permissions.id', '=', 'user_permissions.permission_id')
            ->where('user_id', $this->id)->where('type', 'grant')
            ->pluck('permissions.name')->toArray();

        $denies = DB::table('user_permissions')
            ->join('permissions', 'permissions.id', '=', 'user_permissions.permission_id')
            ->where('user_id', $this->id)->where('type', 'deny')
            ->pluck('permissions.name')->toArray();

        return array_values(array_diff(array_unique(array_merge($rolePerms, $grants)), $denies));
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function cart()
    {
        return $this->hasMany(Cart::class);
    }

    public function addresses()
    {
        return $this->hasMany(Address::class);
    }

    public function wishlists()
    {
        return $this->hasMany(Wishlist::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function returns()
    {
        return $this->hasMany(ProductReturn::class);
    }

    public function userPermissions()
    {
        return $this->hasMany(UserPermission::class);
    }

    public function supportTickets()
    {
        return $this->hasMany(SupportTicket::class);
    }

    public function loginActivities()
    {
        return $this->hasMany(LoginActivity::class)->latest('created_at');
    }

    public function notifications()
    {
        return $this->hasMany(UserNotification::class)->latest();
    }

    public function unreadNotificationsCount(): int
    {
        return $this->notifications()->where('is_read', false)->count();
    }

    public function referralCode()
    {
        return $this->hasOne(ReferralCode::class);
    }

    public function vendor()
    {
        return $this->hasOne(Vendor::class);
    }

    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar) {
            return Storage::url($this->avatar);
        }
        $initial = strtoupper(substr($this->name, 0, 1));

        return "https://ui-avatars.com/api/?name={$initial}&background=6366f1&color=ffffff&size=128";
    }
}
