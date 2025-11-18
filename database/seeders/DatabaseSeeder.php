<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Rol;
use App\Models\User;
use App\Models\Permiso;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Roles base
        $adminRole = Rol::create([
            'nombre'      => 'Administrador',
            'descripcion' => 'Acceso total al sistema',
            'estado'      => true,
        ]);

        $userRole = Rol::create([
            'nombre'      => 'Usuario',
            'descripcion' => 'Usuario estándar del sistema',
            'estado'      => true,
        ]);

        // 2. Usuario administrador
        $adminUser = User::create([
            'name'                => 'Super Administrador',
            'usuario'             => 'admin',
            'email'               => 'admin@example.com',
            'password'            => Hash::make('admin123'), // cámbialo en producción
            'rol_id'              => $adminRole->id,
            'personal_id'         => null,
            'estado'              => true,
            'password_changed_at' => null,
        ]);

        // 3. Permisos de ejemplo (ajústalos según tus módulos)
        $permisos = [
            [
                'rol_id'      => $adminRole->id,
                'nombre'      => 'Ver usuarios',
                'clave'       => 'users.index',
                'descripcion' => 'Listar usuarios del sistema',
                'estado'      => true,
            ],
            [
                'rol_id'      => $adminRole->id,
                'nombre'      => 'Crear usuarios',
                'clave'       => 'users.store',
                'descripcion' => 'Crear nuevos usuarios',
                'estado'      => true,
            ],
            [
                'rol_id'      => $adminRole->id,
                'nombre'      => 'Editar usuarios',
                'clave'       => 'users.update',
                'descripcion' => 'Editar usuarios existentes',
                'estado'      => true,
            ],
            [
                'rol_id'      => $adminRole->id,
                'nombre'      => 'Eliminar usuarios',
                'clave'       => 'users.destroy',
                'descripcion' => 'Eliminar usuarios',
                'estado'      => true,
            ],
        ];

        foreach ($permisos as $permiso) {
            Permiso::create($permiso);
        }
    }
}
