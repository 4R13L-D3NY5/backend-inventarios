<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Solicitud;
use App\Models\SolicitudItem;
use App\Models\Personal;
use App\Models\Laboratorio;
use App\Models\Ubicacion;
use App\Models\Item;
use App\Models\User;

class SolicitudesSeeder extends Seeder
{
    public function run(): void
    {
        // Obtener datos necesarios
        $personals = Personal::all();
        $laboratorios = Laboratorio::all();
        $ubicaciones = Ubicacion::all();
        $items = Item::all();
        $users = User::all();

        if ($personals->isEmpty() || $laboratorios->isEmpty() || $items->isEmpty()) {
            $this->command->warn('⚠️  No hay datos de Personal, Laboratorios o Items. Ejecuta primero DatabaseSeeder.');
            return;
        }

        // Crear solicitudes con diferentes estados y prioridades
        $solicitudes = [
            // 1. Solicitud PENDIENTE SUB-ALMACÉN - Alta prioridad
            [
                'codigo' => 'SOL-0001',
                'tipo' => 'reposicion',
                'prioridad' => 'alta',
                'solicitante_id' => $personals->random()->id,
                'laboratorio_id' => $laboratorios->first()->id,
                'ubicacion_id' => $ubicaciones->random()->id,
                'observaciones' => 'Reposición urgente de reactivos químicos para prácticas de laboratorio',
                'fecha_solicitud' => now()->subDays(1),
                'estado_general' => 'pendiente_subalmacen',
                'estado_subalmacen' => 'pendiente',
                'items' => [
                    ['item_id' => 1, 'cantidad_solicitada' => 5, 'unidad_medida' => 'L', 'observaciones' => 'Ácido sulfúrico para titulaciones'],
                    ['item_id' => 2, 'cantidad_solicitada' => 3, 'unidad_medida' => 'L', 'observaciones' => null],
                ]
            ],

            // 2. Solicitud PENDIENTE SUB-ALMACÉN - Media prioridad
            [
                'codigo' => 'SOL-0002',
                'tipo' => 'compra_nueva',
                'prioridad' => 'media',
                'solicitante_id' => $personals->random()->id,
                'laboratorio_id' => $laboratorios->skip(1)->first()->id,
                'ubicacion_id' => $ubicaciones->random()->id,
                'observaciones' => 'Solicitud de material de vidrio para nuevas prácticas',
                'fecha_solicitud' => now()->subDays(2),
                'estado_general' => 'pendiente_subalmacen',
                'estado_subalmacen' => 'pendiente',
                'items' => [
                    ['item_id' => 8, 'cantidad_solicitada' => 20, 'unidad_medida' => 'UN', 'observaciones' => 'Vasos de precipitado 250ml'],
                    ['item_id' => 9, 'cantidad_solicitada' => 15, 'unidad_medida' => 'UN', 'observaciones' => 'Matraces aforados'],
                ]
            ],

            // 3. Solicitud PENDIENTE SUB-ALMACÉN - Baja prioridad
            [
                'codigo' => 'SOL-0003',
                'tipo' => 'reposicion',
                'prioridad' => 'baja',
                'solicitante_id' => $personals->random()->id,
                'laboratorio_id' => $laboratorios->first()->id,
                'ubicacion_id' => $ubicaciones->random()->id,
                'observaciones' => 'Reposición de insumos generales',
                'fecha_solicitud' => now()->subDays(3),
                'estado_general' => 'pendiente_subalmacen',
                'estado_subalmacen' => 'pendiente',
                'items' => [
                    ['item_id' => 10, 'cantidad_solicitada' => 10, 'unidad_medida' => 'caja', 'observaciones' => 'Guantes de látex talla M'],
                ]
            ],

            // 4. Solicitud PENDIENTE ALMACÉN - Alta prioridad
            [
                'codigo' => 'SOL-0004',
                'tipo' => 'compra_nueva',
                'prioridad' => 'alta',
                'solicitante_id' => $personals->random()->id,
                'laboratorio_id' => $laboratorios->skip(2)->first()->id,
                'ubicacion_id' => $ubicaciones->random()->id,
                'observaciones' => 'Equipos de laboratorio para ampliación de capacidad',
                'fecha_solicitud' => now()->subDays(5),
                'estado_general' => 'pendiente_almacen',
                'estado_subalmacen' => 'aprobado',
                'estado_almacen' => 'pendiente',
                'aprobado_subalmacen_por' => $users->first()->id,
                'aprobado_subalmacen_fecha' => now()->subDays(4),
                'items' => [
                    ['item_id' => 5, 'cantidad_solicitada' => 2, 'unidad_medida' => 'UN', 'observaciones' => 'Microscopios binoculares'],
                    ['item_id' => 6, 'cantidad_solicitada' => 5, 'unidad_medida' => 'UN', 'observaciones' => 'Pipetas automáticas 10ml'],
                ]
            ],

            // 5. Solicitud PENDIENTE ALMACÉN - Media prioridad
            [
                'codigo' => 'SOL-0005',
                'tipo' => 'reposicion',
                'prioridad' => 'media',
                'solicitante_id' => $personals->random()->id,
                'laboratorio_id' => $laboratorios->first()->id,
                'ubicacion_id' => $ubicaciones->random()->id,
                'observaciones' => 'Reposición de reactivos básicos',
                'fecha_solicitud' => now()->subDays(6),
                'estado_general' => 'pendiente_almacen',
                'estado_subalmacen' => 'aprobado',
                'estado_almacen' => 'pendiente',
                'aprobado_subalmacen_por' => $users->first()->id,
                'aprobado_subalmacen_fecha' => now()->subDays(5),
                'items' => [
                    ['item_id' => 3, 'cantidad_solicitada' => 5, 'unidad_medida' => 'kg', 'observaciones' => 'Hidróxido de sodio'],
                    ['item_id' => 4, 'cantidad_solicitada' => 10, 'unidad_medida' => 'L', 'observaciones' => 'Etanol 96%'],
                ]
            ],

            // 6. Solicitud EN ADQUISICIONES - Alta prioridad
            [
                'codigo' => 'SOL-0006',
                'tipo' => 'compra_nueva',
                'prioridad' => 'alta',
                'solicitante_id' => $personals->random()->id,
                'laboratorio_id' => $laboratorios->last()->id,
                'ubicacion_id' => $ubicaciones->random()->id,
                'observaciones' => 'Equipos de cómputo para laboratorio',
                'fecha_solicitud' => now()->subDays(10),
                'estado_general' => 'en_adquisiciones',
                'estado_subalmacen' => 'aprobado',
                'estado_almacen' => 'aprobado',
                'estado_adquisicion' => 'pendiente',
                'aprobado_subalmacen_por' => $users->first()->id,
                'aprobado_subalmacen_fecha' => now()->subDays(9),
                'aprobado_almacen_por' => $users->first()->id,
                'aprobado_almacen_fecha' => now()->subDays(8),
                'items' => [
                    ['item_id' => 11, 'cantidad_solicitada' => 3, 'unidad_medida' => 'UN', 'observaciones' => 'Laptops Dell Latitude'],
                ]
            ],

            // 7. Solicitud EN ADQUISICIONES - Media prioridad
            [
                'codigo' => 'SOL-0007',
                'tipo' => 'compra_nueva',
                'prioridad' => 'media',
                'solicitante_id' => $personals->random()->id,
                'laboratorio_id' => $laboratorios->first()->id,
                'ubicacion_id' => $ubicaciones->random()->id,
                'observaciones' => 'Balanza analítica de precisión',
                'fecha_solicitud' => now()->subDays(12),
                'estado_general' => 'en_adquisiciones',
                'estado_subalmacen' => 'aprobado',
                'estado_almacen' => 'aprobado',
                'estado_adquisicion' => 'pendiente',
                'aprobado_subalmacen_por' => $users->first()->id,
                'aprobado_subalmacen_fecha' => now()->subDays(11),
                'aprobado_almacen_por' => $users->first()->id,
                'aprobado_almacen_fecha' => now()->subDays(10),
                'items' => [
                    ['item_id' => 7, 'cantidad_solicitada' => 1, 'unidad_medida' => 'UN', 'observaciones' => 'Balanza analítica Ohaus'],
                ]
            ],

            // 8. Solicitud COMPLETADA - Alta prioridad
            [
                'codigo' => 'SOL-0008',
                'tipo' => 'reposicion',
                'prioridad' => 'alta',
                'solicitante_id' => $personals->random()->id,
                'laboratorio_id' => $laboratorios->first()->id,
                'ubicacion_id' => $ubicaciones->random()->id,
                'observaciones' => 'Reposición urgente de reactivos - COMPLETADA',
                'fecha_solicitud' => now()->subDays(20),
                'estado_general' => 'completada',
                'estado_subalmacen' => 'aprobado',
                'estado_almacen' => 'aprobado',
                'estado_adquisicion' => 'aprobado',
                'aprobado_subalmacen_por' => $users->first()->id,
                'aprobado_subalmacen_fecha' => now()->subDays(19),
                'aprobado_almacen_por' => $users->first()->id,
                'aprobado_almacen_fecha' => now()->subDays(18),
                'aprobado_adquisicion_por' => $users->first()->id,
                'aprobado_adquisicion_fecha' => now()->subDays(15),
                'fecha_completada' => now()->subDays(15),
                'items' => [
                    ['item_id' => 1, 'cantidad_solicitada' => 10, 'unidad_medida' => 'L', 'observaciones' => 'Ácido sulfúrico'],
                    ['item_id' => 2, 'cantidad_solicitada' => 8, 'unidad_medida' => 'L', 'observaciones' => 'Ácido clorhídrico'],
                ]
            ],

            // 9. Solicitud COMPLETADA - Media prioridad
            [
                'codigo' => 'SOL-0009',
                'tipo' => 'compra_nueva',
                'prioridad' => 'media',
                'solicitante_id' => $personals->random()->id,
                'laboratorio_id' => $laboratorios->skip(1)->first()->id,
                'ubicacion_id' => $ubicaciones->random()->id,
                'observaciones' => 'Material de vidrio - COMPLETADA',
                'fecha_solicitud' => now()->subDays(25),
                'estado_general' => 'completada',
                'estado_subalmacen' => 'aprobado',
                'estado_almacen' => 'aprobado',
                'estado_adquisicion' => 'aprobado',
                'aprobado_subalmacen_por' => $users->first()->id,
                'aprobado_subalmacen_fecha' => now()->subDays(24),
                'aprobado_almacen_por' => $users->first()->id,
                'aprobado_almacen_fecha' => now()->subDays(23),
                'aprobado_adquisicion_por' => $users->first()->id,
                'aprobado_adquisicion_fecha' => now()->subDays(20),
                'fecha_completada' => now()->subDays(20),
                'items' => [
                    ['item_id' => 8, 'cantidad_solicitada' => 30, 'unidad_medida' => 'UN', 'observaciones' => 'Vasos de precipitado'],
                    ['item_id' => 9, 'cantidad_solicitada' => 25, 'unidad_medida' => 'UN', 'observaciones' => 'Matraces aforados'],
                ]
            ],

            // 10. Solicitud COMPLETADA - Baja prioridad
            [
                'codigo' => 'SOL-0010',
                'tipo' => 'reposicion',
                'prioridad' => 'baja',
                'solicitante_id' => $personals->random()->id,
                'laboratorio_id' => $laboratorios->first()->id,
                'ubicacion_id' => $ubicaciones->random()->id,
                'observaciones' => 'Insumos generales - COMPLETADA',
                'fecha_solicitud' => now()->subDays(30),
                'estado_general' => 'completada',
                'estado_subalmacen' => 'aprobado',
                'estado_almacen' => 'aprobado',
                'estado_adquisicion' => 'aprobado',
                'aprobado_subalmacen_por' => $users->first()->id,
                'aprobado_subalmacen_fecha' => now()->subDays(29),
                'aprobado_almacen_por' => $users->first()->id,
                'aprobado_almacen_fecha' => now()->subDays(28),
                'aprobado_adquisicion_por' => $users->first()->id,
                'aprobado_adquisicion_fecha' => now()->subDays(25),
                'fecha_completada' => now()->subDays(25),
                'items' => [
                    ['item_id' => 10, 'cantidad_solicitada' => 20, 'unidad_medida' => 'caja', 'observaciones' => 'Guantes de látex'],
                ]
            ],

            // 11. Solicitud DENEGADA - Alta prioridad
            [
                'codigo' => 'SOL-0011',
                'tipo' => 'compra_nueva',
                'prioridad' => 'alta',
                'solicitante_id' => $personals->random()->id,
                'laboratorio_id' => $laboratorios->first()->id,
                'ubicacion_id' => $ubicaciones->random()->id,
                'observaciones' => 'Solicitud de equipos costosos - DENEGADA',
                'fecha_solicitud' => now()->subDays(15),
                'estado_general' => 'denegada',
                'estado_subalmacen' => 'aprobado',
                'estado_almacen' => 'denegado',
                'aprobado_subalmacen_por' => $users->first()->id,
                'aprobado_subalmacen_fecha' => now()->subDays(14),
                'motivo_denegacion' => 'Presupuesto insuficiente para este periodo. Se recomienda incluir en el siguiente ciclo presupuestario.',
                'denegado_por' => $users->first()->id,
                'denegado_fecha' => now()->subDays(13),
                'items' => [
                    ['item_id' => 5, 'cantidad_solicitada' => 5, 'unidad_medida' => 'UN', 'observaciones' => 'Microscopios binoculares'],
                ]
            ],

            // 12. Solicitud DENEGADA - Media prioridad
            [
                'codigo' => 'SOL-0012',
                'tipo' => 'reposicion',
                'prioridad' => 'media',
                'solicitante_id' => $personals->random()->id,
                'laboratorio_id' => $laboratorios->skip(1)->first()->id,
                'ubicacion_id' => $ubicaciones->random()->id,
                'observaciones' => 'Reactivos no autorizados - DENEGADA',
                'fecha_solicitud' => now()->subDays(8),
                'estado_general' => 'denegada',
                'estado_subalmacen' => 'denegado',
                'motivo_denegacion' => 'Los reactivos solicitados no están en el catálogo autorizado. Favor solicitar alternativas aprobadas.',
                'denegado_por' => $users->first()->id,
                'denegado_fecha' => now()->subDays(7),
                'items' => [
                    ['item_id' => 1, 'cantidad_solicitada' => 50, 'unidad_medida' => 'L', 'observaciones' => 'Cantidad excesiva'],
                ]
            ],

            // 13-15. Más solicitudes pendientes de diferentes tipos
            [
                'codigo' => 'SOL-0013',
                'tipo' => 'reposicion',
                'prioridad' => 'alta',
                'solicitante_id' => $personals->random()->id,
                'laboratorio_id' => $laboratorios->random()->id,
                'ubicacion_id' => $ubicaciones->random()->id,
                'observaciones' => 'Reposición de material consumible',
                'fecha_solicitud' => now()->subHours(12),
                'estado_general' => 'pendiente_subalmacen',
                'estado_subalmacen' => 'pendiente',
                'items' => [
                    ['item_id' => 4, 'cantidad_solicitada' => 5, 'unidad_medida' => 'L', 'observaciones' => 'Etanol para limpieza'],
                    ['item_id' => 10, 'cantidad_solicitada' => 5, 'unidad_medida' => 'caja', 'observaciones' => 'Guantes'],
                ]
            ],

            [
                'codigo' => 'SOL-0014',
                'tipo' => 'compra_nueva',
                'prioridad' => 'media',
                'solicitante_id' => $personals->random()->id,
                'laboratorio_id' => $laboratorios->random()->id,
                'ubicacion_id' => $ubicaciones->random()->id,
                'observaciones' => 'Ampliación de inventario de material de vidrio',
                'fecha_solicitud' => now()->subHours(6),
                'estado_general' => 'pendiente_subalmacen',
                'estado_subalmacen' => 'pendiente',
                'items' => [
                    ['item_id' => 8, 'cantidad_solicitada' => 15, 'unidad_medida' => 'UN', 'observaciones' => null],
                ]
            ],

            [
                'codigo' => 'SOL-0015',
                'tipo' => 'reposicion',
                'prioridad' => 'baja',
                'solicitante_id' => $personals->random()->id,
                'laboratorio_id' => $laboratorios->random()->id,
                'ubicacion_id' => $ubicaciones->random()->id,
                'observaciones' => 'Solicitud de rutina mensual',
                'fecha_solicitud' => now()->subDays(4),
                'estado_general' => 'pendiente_almacen',
                'estado_subalmacen' => 'aprobado',
                'estado_almacen' => 'pendiente',
                'aprobado_subalmacen_por' => $users->first()->id,
                'aprobado_subalmacen_fecha' => now()->subDays(3),
                'items' => [
                    ['item_id' => 3, 'cantidad_solicitada' => 2, 'unidad_medida' => 'kg', 'observaciones' => 'Hidróxido de sodio'],
                ]
            ],
        ];

        // Crear las solicitudes y sus ítems
        foreach ($solicitudes as $solicitudData) {
            $items = $solicitudData['items'];
            unset($solicitudData['items']);

            $solicitud = Solicitud::create($solicitudData);

            foreach ($items as $itemData) {
                SolicitudItem::create([
                    'solicitud_id' => $solicitud->id,
                    ...$itemData
                ]);
            }
        }

        $this->command->info('✅ Seeder de Solicitudes completado: 15 solicitudes creadas con diferentes estados');
    }
}
