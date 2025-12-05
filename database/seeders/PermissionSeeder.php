<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permiso;
use App\Models\Rol;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
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

        foreach ($permisos as $permiso) {
            Permiso::updateOrCreate(
                ['clave' => $permiso['clave']],
                [
                    'nombre' => $permiso['nombre'],
                    'grupo' => $permiso['grupo'],
                    'descripcion' => 'Permiso para ' . strtolower($permiso['nombre'])
                ]
            );
        }

        // Asignar todos los permisos al rol Super Admin (si existe)
        $superAdmin = Rol::where('nombre', 'Super Admin')->first();
        if ($superAdmin) {
            $allPermissions = Permiso::all();
            $superAdmin->permisos()->sync($allPermissions);
        }
    }
}
