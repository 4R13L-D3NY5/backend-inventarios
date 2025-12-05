<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Rol;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Buscar el rol Super Admin
        $rol = Rol::where('nombre', 'Super Admin')->first();

        if (!$rol) {
            $this->command->error('Rol "Super Admin" no encontrado. Ejecuta primero el PermissionSeeder.');
            return;
        }

        // Verificar si ya existe un usuario admin
        $existingUser = User::where('usuario', 'admin')->first();

        if ($existingUser) {
            $this->command->warn('El usuario "admin" ya existe.');
            return;
        }

        // Crear usuario administrador
        $user = User::create([
            'usuario' => 'admin',
            'email' => 'admin@unitepc.edu.bo',
            'password' => Hash::make('admin123'),
            'rol_id' => $rol->id,
            'personal_id' => null,
            'estado' => true,
            'password_changed_at' => now(), // Ya cambió contraseña
        ]);

        $this->command->info('✅ Usuario administrador creado exitosamente!');
        $this->command->info('   Usuario: admin');
        $this->command->info('   Contraseña: admin123');
        $this->command->info('   Email: admin@unitepc.edu.bo');
        $this->command->info('   Rol: ' . $rol->nombre);
    }
}
