<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @method bool isSuperAdmin()
 * @method bool isAdmin()
 * @method bool isLogistica()
 * @method bool isAlmacen()
 * @method bool hasRole(string $role)
 * @method bool hasAnyRole(array $roles)
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'rol',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // ========== MÉTODOS PARA VERIFICAR ROLES ==========
    public function isSuperAdmin(): bool
    {
        return $this->rol === 'super_admin';
    }

    public function isAdmin(): bool
    {
        return $this->rol === 'admin';
    }

    public function isLogistica(): bool
    {
        return $this->rol === 'logistica';
    }

    public function isAlmacen(): bool
    {
        return $this->rol === 'almacen';
    }
    
    public function hasRole(string $role): bool
    {
        return $this->rol === $role;
    }
    
    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->rol, $roles);
    }
}