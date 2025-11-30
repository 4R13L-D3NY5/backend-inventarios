<?php

namespace App\Http\Controllers;

use App\Models\Inventario;
use App\Models\Item;
use App\Models\OrdenCompra;
use App\Models\MovimientoInventario;
use App\Models\Prestamo;
use App\Models\HistorialPrecio;
use App\Models\Solicitud;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Dashboard Ejecutivo
     * Retorna KPIs y métricas principales del sistema
     */
    public function index()
    {
        // ========================================
        // PANEL 1: INVENTARIO
        // ========================================
        
        // Valor total del inventario
        $inventarios = Inventario::where('cantidad_actual', '>', 0)->get();
        $valorTotal = $inventarios->sum(function($inv) {
            $ultimoPrecio = HistorialPrecio::where('item_id', $inv->item_id)
                                          ->orderBy('fecha_vigencia', 'desc')
                                          ->first();
            return $inv->cantidad_actual * ($ultimoPrecio ? $ultimoPrecio->precio : 0);
        });

        // Items bajo stock
        $itemsBajoStock = Inventario::whereColumn('cantidad_actual', '<', DB::raw('stock_minimo * 1.2'))
                                    ->count();

        // Tasa de rotación (movimientos últimos 30 días / stock promedio)
        $movimientosUltimos30 = MovimientoInventario::where('fecha_movimiento', '>=', now()->subDays(30))
                                                    ->whereIn('tipo', ['entrada', 'salida'])
                                                    ->sum('cantidad');
        $stockPromedio = $inventarios->avg('cantidad_actual') * $inventarios->count();
        $tasaRotacion = $stockPromedio > 0 ? round($movimientosUltimos30 / $stockPromedio, 2) : 0;

        // Valor inmovilizado (items sin movimiento > 90 días)
        $itemsSinMovimiento = Item::whereDoesntHave('movimientosInventario', function($q) {
            $q->where('fecha_movimiento', '>=', now()->subDays(90));
        })->pluck('id');
        
        $valorInmovilizado = Inventario::whereIn('item_id', $itemsSinMovimiento)
                                       ->get()
                                       ->sum(function($inv) {
                                           $ultimoPrecio = HistorialPrecio::where('item_id', $inv->item_id)
                                                                         ->orderBy('fecha_vigencia', 'desc')
                                                                         ->first();
                                           return $inv->cantidad_actual * ($ultimoPrecio ? $ultimoPrecio->precio : 0);
                                       });

        // ========================================
        // PANEL 2: COMPRAS
        // ========================================
        
        // Órdenes pendientes
        $ordenesPendientes = OrdenCompra::whereIn('estado', ['confirmada', 'recibida_parcial'])->get();
        $ordenesPendientesCantidad = $ordenesPendientes->count();
        $ordenesPendientesValor = $ordenesPendientes->sum('total');

        // Tiempo promedio de procesamiento
        $ordenesCompletadas = OrdenCompra::where('estado', 'recibida_completa')
                                        ->whereNotNull('fecha_entrega_real')
                                        ->get();
        $tiempoPromedio = $ordenesCompletadas->count() > 0 
            ? $ordenesCompletadas->avg(function($orden) {
                return $orden->fecha_emision->diffInDays($orden->fecha_entrega_real);
            })
            : 0;

        // Tasa de cumplimiento de proveedores (entregas a tiempo)
        $entregasATiempo = $ordenesCompletadas->filter(function($orden) {
            return $orden->fecha_entrega_real <= $orden->fecha_entrega_estimada;
        })->count();
        $tasaCumplimiento = $ordenesCompletadas->count() > 0 
            ? round(($entregasATiempo / $ordenesCompletadas->count()) * 100, 1)
            : 0;

        // Gasto mensual (mes actual)
        $gastoMensual = OrdenCompra::whereMonth('fecha_emision', now()->month)
                                   ->whereYear('fecha_emision', now()->year)
                                   ->sum('total');

        // ========================================
        // PANEL 3: CONSUMO
        // ========================================
        
        // Consumo del mes actual
        $consumoMesActual = MovimientoInventario::where('tipo', 'salida')
                                                ->whereMonth('fecha_movimiento', now()->month)
                                                ->whereYear('fecha_movimiento', now()->year)
                                                ->count();

        // Consumo mes anterior
        $consumoMesAnterior = MovimientoInventario::where('tipo', 'salida')
                                                  ->whereMonth('fecha_movimiento', now()->subMonth()->month)
                                                  ->whereYear('fecha_movimiento', now()->subMonth()->year)
                                                  ->count();

        // Variación porcentual
        $variacionConsumo = $consumoMesAnterior > 0 
            ? round((($consumoMesActual - $consumoMesAnterior) / $consumoMesAnterior) * 100, 1)
            : 0;

        // Laboratorio con mayor consumo (últimos 30 días)
        $consumoPorLab = MovimientoInventario::with('solicitud.laboratorio')
                                             ->where('tipo', 'salida')
                                             ->where('fecha_movimiento', '>=', now()->subDays(30))
                                             ->whereNotNull('solicitud_id')
                                             ->get()
                                             ->groupBy('solicitud.laboratorio.nombre')
                                             ->map(function($movs) {
                                                 return $movs->count();
                                             })
                                             ->sortDesc();
        
        $labMayorConsumo = $consumoPorLab->keys()->first() ?: 'N/A';

        // Tendencia últimos 6 meses
        $tendencia = [];
        for ($i = 5; $i >= 0; $i--) {
            $mes = now()->subMonths($i);
            $cantidad = MovimientoInventario::where('tipo', 'salida')
                                           ->whereMonth('fecha_movimiento', $mes->month)
                                           ->whereYear('fecha_movimiento', $mes->year)
                                           ->count();
            $tendencia[] = [
                'mes' => $mes->format('Y-m'),
                'cantidad' => $cantidad
            ];
        }

        // ========================================
        // PANEL 4: PRÉSTAMOS
        // ========================================
        
        $prestamosActivos = Prestamo::where('estado', 'activo')->count();
        $prestamosVencidos = Prestamo::where('estado', 'vencido')->count();
        
        // Tasa de devolución a tiempo
        $devueltos = Prestamo::where('estado', 'devuelto')
                            ->whereNotNull('fecha_devolucion_real')
                            ->get();
        $devueltosATiempoPrestamos = $devueltos->filter(function($p) {
            return $p->fecha_devolucion_real <= $p->fecha_devolucion_estimada;
        })->count();
        $tasaDevolucionTiempo = $devueltos->count() > 0 
            ? round(($devueltosATiempoPrestamos / $devueltos->count()) * 100, 1)
            : 0;

        // Equipos en préstamo
        $equiposEnPrestamo = Prestamo::where('estado', 'activo')
                                    ->distinct('item_id')
                                    ->count('item_id');

        // ========================================
        // PANEL 5: ALERTAS
        // ========================================
        
        $alertas = [];

        // Items críticos (stock < 20% del mínimo)
        $itemsCriticos = Inventario::whereColumn('cantidad_actual', '<', DB::raw('stock_minimo * 0.2'))
                                   ->count();
        if ($itemsCriticos > 0) {
            $alertas[] = [
                'tipo' => 'stock_critico',
                'mensaje' => "$itemsCriticos items en stock crítico (< 20% mínimo)",
                'prioridad' => 'alta',
                'cantidad' => $itemsCriticos
            ];
        }

        // Órdenes vencidas
        $ordenesVencidas = OrdenCompra::whereIn('estado', ['confirmada', 'recibida_parcial'])
                                      ->whereNotNull('fecha_entrega_estimada')
                                      ->where('fecha_entrega_estimada', '<', now())
                                      ->count();
        if ($ordenesVencidas > 0) {
            $alertas[] = [
                'tipo' => 'ordenes_vencidas',
                'mensaje' => "$ordenesVencidas órdenes de compra vencidas",
                'prioridad' => 'alta',
                'cantidad' => $ordenesVencidas
            ];
        }

        // Préstamos por vencer hoy
        $prestamosPorVencerHoy = Prestamo::where('estado', 'activo')
                                        ->whereDate('fecha_devolucion_estimada', now()->toDateString())
                                        ->count();
        if ($prestamosPorVencerHoy > 0) {
            $alertas[] = [
                'tipo' => 'prestamos_vencen_hoy',
                'mensaje' => "$prestamosPorVencerHoy préstamos vencen hoy",
                'prioridad' => 'media',
                'cantidad' => $prestamosPorVencerHoy
            ];
        }

        // Items sin movimiento (> 90 días)
        $itemsSinMovimientoCount = $itemsSinMovimiento->count();
        if ($itemsSinMovimientoCount > 0) {
            $alertas[] = [
                'tipo' => 'items_sin_movimiento',
                'mensaje' => "$itemsSinMovimientoCount items sin movimiento (>90 días)",
                'prioridad' => 'baja',
                'cantidad' => $itemsSinMovimientoCount
            ];
        }

        // ========================================
        // RESPUESTA CONSOLIDADA
        // ========================================
        
        return response()->json([
            'inventario' => [
                'valor_total' => round($valorTotal, 2),
                'items_bajo_stock' => $itemsBajoStock,
                'tasa_rotacion' => $tasaRotacion,
                'valor_inmovilizado' => round($valorInmovilizado, 2)
            ],
            'compras' => [
                'ordenes_pendientes' => [
                    'cantidad' => $ordenesPendientesCantidad,
                    'valor' => round($ordenesPendientesValor, 2)
                ],
                'tiempo_promedio_procesamiento' => round($tiempoPromedio, 1),
                'tasa_cumplimiento_proveedores' => $tasaCumplimiento,
                'gasto_mensual' => round($gastoMensual, 2)
            ],
            'consumo' => [
                'mes_actual' => $consumoMesActual,
                'mes_anterior' => $consumoMesAnterior,
                'variacion_porcentual' => $variacionConsumo,
                'laboratorio_mayor_consumo' => $labMayorConsumo,
                'tendencia_6_meses' => $tendencia
            ],
            'prestamos' => [
                'activos' => $prestamosActivos,
                'vencidos' => $prestamosVencidos,
                'tasa_devolucion_tiempo' => $tasaDevolucionTiempo,
                'equipos_en_prestamo' => $equiposEnPrestamo
            ],
            'alertas' => $alertas,
            'resumen' => [
                'total_alertas' => count($alertas),
                'alertas_alta_prioridad' => collect($alertas)->where('prioridad', 'alta')->count()
            ]
        ]);
    }
}
