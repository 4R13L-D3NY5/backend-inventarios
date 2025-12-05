<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'usuario',
        'email',
        'password',
        'rol_id',
        'personal_id',
        'estado',
        'password_changed_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at'   => 'datetime',
        'password_changed_at' => 'datetime',
        'estado'              => 'boolean',
    ];

    public function rol()
    {
        return $this->belongsTo(Rol::class, 'rol_id');
    }

    public function personal()
    {
        return $this->belongsTo(Personal::class, 'personal_id');
    }

    public function permisos()
    {
        // Accede a los permisos a través del rol
        return $this->rol ? $this->rol->permisos : collect();
    }

    /**
     * Verificar si el usuario tiene un permiso específico
     */
    public function hasPermission($clave)
    {
        if (!$this->rol) {
            return false;
        }

        // Super Admin siempre tiene acceso (asumiendo ID 1 o nombre 'Super Admin')
        if ($this->rol->id === 1 || $this->rol->nombre === 'Super Admin') {
            return true;
        }

        return $this->rol->permisos->contains('clave', $clave);
    }

    /**
     * Verificar si el usuario tiene un rol específico
     */
    public function hasRole($roleName)
    {
        return $this->rol && $this->rol->nombre === $roleName;
    }
}
