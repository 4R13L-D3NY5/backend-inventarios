<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Rol;
use App\Models\Permiso;
use Illuminate\Support\Facades\Hash;

class CompleteAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. CREAR ROL SUPER ADMIN
        $superAdmin = Rol::firstOrCreate(
            ['nombre' => 'Super Admin'],
            [
                'descripcion' => 'Administrador con acceso total al sistema',
                'estado' => true
            ]
        );
        $this->command->info('✅ Rol "Super Admin" creado/verificado');

        // 2. CREAR PERMISOS
        $permisos = [
            // Inventario
            ['clave' => 'ver_inventario', 'nombre' => 'Ver Inventario', 'grupo' => 'Inventario'],
            ['clave' => 'crear_items', 'nombre' => 'Crear Items', 'grupo' => 'Inventario'],
            ['clave' => 'editar_items', 'nombre' => 'Editar Items', 'grupo' => 'Inventario'],
            ['clave' => 'eliminar_items', 'nombre' => 'Eliminar Items', 'grupo' => 'Inventario'],
            ['clave' => 'ajustes_stock', 'nombre' => 'Ajustes de Stock', 'grupo' => 'Inventario'],
            ['clave' => 'ver_kardex', 'nombre' => 'Ver Kardex', 'grupo' => 'Inventario'],

            // Compras
            ['clave' => 'ver_ordenes', 'nombre' => 'Ver Órdenes', 'grupo' => 'Compras'],
            ['clave' => 'crear_solicitud', 'nombre' => 'Crear Solicitud', 'grupo' => 'Compras'],
            ['clave' => 'aprobar_ordenes', 'nombre' => 'Aprobar Órdenes', 'grupo' => 'Compras'],
            ['clave' => 'recepcionar_ordenes', 'nombre' => 'Recepcionar', 'grupo' => 'Compras'],

            // Ventas/Salidas
            ['clave' => 'ver_salidas', 'nombre' => 'Ver Salidas', 'grupo' => 'Ventas/Salidas'],
            ['clave' => 'registrar_salida', 'nombre' => 'Registrar Salida', 'grupo' => 'Ventas/Salidas'],

            // Proveedores
            ['clave' => 'ver_proveedores', 'nombre' => 'Ver Proveedores', 'grupo' => 'Proveedores'],
            ['clave' => 'gestionar_proveedores', 'nombre' => 'Gestionar Proveedores', 'grupo' => 'Proveedores'],
            ['clave' => 'calificar_proveedores', 'nombre' => 'Calificar', 'grupo' => 'Proveedores'],

            // Reportes
            ['clave' => 'ver_reportes', 'nombre' => 'Ver Reportes', 'grupo' => 'Reportes'],
            ['clave' => 'exportar_reportes', 'nombre' => 'Exportar', 'grupo' => 'Reportes'],

            // Administración
            ['clave' => 'gestionar_usuarios', 'nombre' => 'Gestionar Usuarios', 'grupo' => 'Administración'],
            ['clave' => 'gestionar_roles', 'nombre' => 'Gestionar Roles', 'grupo' => 'Administración'],
            ['clave' => 'configuracion_global', 'nombre' => 'Configuración Global', 'grupo' => 'Administración'],
        ];

        $permisosCreados = [];
        foreach ($permisos as $permiso) {
            $p = Permiso::updateOrCreate(
                ['clave' => $permiso['clave']],
                [
                    'nombre' => $permiso['nombre'],
                    'grupo' => $permiso['grupo'],
                    'descripcion' => 'Permiso para ' . strtolower($permiso['nombre']),
                    'estado' => true
                ]
            );
            $permisosCreados[] = $p->id;
        }
        $this->command->info('✅ ' . count($permisosCreados) . ' permisos creados/actualizados');

        // 3. ASIGNAR TODOS LOS PERMISOS AL ROL SUPER ADMIN
        $superAdmin->permisos()->sync($permisosCreados);
        $this->command->info('✅ Permisos asignados al rol Super Admin');

        // 4. CREAR USUARIO ADMINISTRADOR
        $existingUser = User::where('usuario', 'admin')->first();

        if ($existingUser) {
            $this->command->warn('⚠️  El usuario "admin" ya existe (ID: ' . $existingUser->id . ')');
            // Actualizar el rol si es necesario
            $existingUser->update(['rol_id' => $superAdmin->id]);
            $this->command->info('✅ Rol actualizado para el usuario existente');
        } else {
            $user = User::create([
                'usuario' => 'admin',
                'email' => 'admin@unitepc.edu.bo',
                'password' => Hash::make('admin123'),
                'rol_id' => $superAdmin->id,
                'personal_id' => null,
                'estado' => true,
                'password_changed_at' => now(),
            ]);

            $this->command->info('');
            $this->command->info('═══════════════════════════════════════');
            $this->command->info('✅ USUARIO ADMINISTRADOR CREADO');
            $this->command->info('═══════════════════════════════════════');
            $this->command->info('   Usuario: admin');
            $this->command->info('   Contraseña: admin123');
            $this->command->info('   Email: admin@unitepc.edu.bo');
            $this->command->info('   Rol: Super Admin');
            $this->command->info('   Permisos: ' . count($permisosCreados) . ' (todos)');
            $this->command->info('═══════════════════════════════════════');
        }
    }
}
