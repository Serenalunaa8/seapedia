<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = ['username', 'email', 'password', 'phone'];

    protected $hidden = ['password', 'remember_token'];

    /**
     * Semua role yang dimiliki user ini (bisa lebih dari satu untuk non-admin).
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles');
    }

    /**
     * Cek apakah user punya role tertentu (di antara SEMUA role miliknya).
     * Dipakai saat assign role, BUKAN untuk keputusan otorisasi per-request.
     */
    public function hasRole(string $roleName): bool
    {
        return $this->roles()->where('name', $roleName)->exists();
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }
}