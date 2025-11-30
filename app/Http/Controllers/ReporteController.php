<?php

namespace App\Http\Controllers;

use App\Models\Inventario;
use App\Models\Item;
use App\Models\OrdenCompra;
use App\Models\MovimientoInventario;
use App\Models\Prestamo;
use App\Models\HistorialPrecio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReporteController extends Controller
{
    /**
     * Reporte: Inventario Valorizado
     * Muestra el valor total del inventario por almacén y categoría
     */
    public function inventarioValorizado(Request $request)
    {
        $validated = $request->validate([
            'almacen_id' => 'nullable|exists:almacenes,id',
            'categoria_id' => 'nullable|exists:categorias,id',
            'fecha_corte' => 'nullable|date',
            'solo_activos' => 'nullable|boolean'
        ]);

        $query = Inventario::with(['item.categoria', 'item.subcategoria', 'almacen'])
                           ->where('cantidad_actual', '>', 0);

        // Aplicar filtros
        if (isset($validated['almacen_id'])) {
            $query->where('almacen_id', $validated['almacen_id']);
        }

        if (isset($validated['categoria_id'])) {
            $query->whereHas('item', function($q) use ($validated) {
                $q->where('categoria_id', $validated['categoria_id']);
            });
        }

        $inventarios = $query->get();

        // Calcular valores
        $detalle = $inventarios->map(function($inv) {
            // Obtener último precio del item
            $ultimoPrecio = HistorialPrecio::where('item_id', $inv->item_id)
                                          ->orderBy('fecha_vigencia', 'desc')
                                          ->first();
            
            $precioUnitario = $ultimoPrecio ? $ultimoPrecio->precio : 0;
            $valorTotal = $inv->cantidad_actual * $precioUnitario;

            return [
                'item' => $inv->item->nombre,
                'codigo' => $inv->item->codigo,
                'categoria' => $inv->item->categoria->nombre,
                'subcategoria' => $inv->item->subcategoria->nombre,
                'almacen' => $inv->almacen->nombre,
                'cantidad' => $inv->cantidad_actual,
                'unidad_medida' => $inv->item->unidad_medida_base,
                'precio_unitario' => $precioUnitario,
                'valor_total' => $valorTotal
            ];
        });

        // Resumen por categoría
        $porCategoria = $detalle->groupBy('categoria')->map(function($items, $categoria) {
            return [
                'categoria' => $categoria,
                'valor' => $items->sum('valor_total'),
                'items' => $items->count()
            ];
        })->values();

        $valorTotal = $detalle->sum('valor_total');

        // Calcular porcentajes
        $porCategoria = $porCategoria->map(function($cat) use ($valorTotal) {
            $cat['porcentaje'] = $valorTotal > 0 ? round(($cat['valor'] / $valorTotal) * 100, 2) : 0;
            return $cat;
        });

        return response()->json([
            'resumen' => [
                'valor_total' => round($valorTotal, 2),
                'items_totales' => $detalle->count(),
                'almacenes' => $inventarios->pluck('almacen_id')->unique()->count()
            ],
            'por_categoria' => $porCategoria,
            'detalle' => $detalle->sortByDesc('valor_total')->values()
        ]);
    }

    /**
     * Reporte: Órdenes de Compra por Estado
     * Resumen de órdenes agrupadas por estado
     */
    public function ordenesCompraPorEstado(Request $request)
    {
        $validated = $request->validate([
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date',
            'proveedor_id' => 'nullable|exists:proveedores,id'
        ]);

        $query = OrdenCompra::with(['proveedor', 'items']);

        // Aplicar filtros
        if (isset($validated['fecha_inicio'])) {
            $query->where('fecha_emision', '>=', $validated['fecha_inicio']);
        }

        if (isset($validated['fecha_fin'])) {
            $query->where('fecha_emision', '<=', $validated['fecha_fin']);
        }

        if (isset($validated['proveedor_id'])) {
            $query->where('proveedor_id', $validated['proveedor_id']);
        }

        $ordenes = $query->get();

        // Agrupar por estado
        $porEstado = $ordenes->groupBy('estado')->map(function($ordenesEstado, $estado) {
            return [
                'estado' => $estado,
                'cantidad' => $ordenesEstado->count(),
                'valor' => $ordenesEstado->sum('total'),
                'ordenes' => $ordenesEstado->map(function($orden) {
                    return [
                        'numero_orden' => $orden->numero_orden,
                        'proveedor' => $orden->proveedor->nombre,
                        'fecha_emision' => $orden->fecha_emision->format('Y-m-d'),
                        'total' => $orden->total
                    ];
                })
            ];
        })->values();

        // Órdenes vencidas (confirmadas con fecha_entrega_estimada pasada)
        $vencidas = $ordenes->filter(function($orden) {
            return in_array($orden->estado, ['confirmada', 'recibida_parcial']) 
                   && $orden->fecha_entrega_estimada 
                   && $orden->fecha_entrega_estimada->isPast();
        })->map(function($orden) {
            return [
                'numero_orden' => $orden->numero_orden,
                'proveedor' => $orden->proveedor->nombre,
                'dias_vencida' => now()->diffInDays($orden->fecha_entrega_estimada),
                'valor' => $orden->total
            ];
        })->values();

        // Calcular tiempo promedio de procesamiento
        $ordenesCompletadas = $ordenes->filter(function($orden) {
            return $orden->estado === 'recibida_completa' && $orden->fecha_entrega_real;
        });

        $tiempoPromedio = $ordenesCompletadas->count() > 0 
            ? $ordenesCompletadas->avg(function($orden) {
                return $orden->fecha_emision->diffInDays($orden->fecha_entrega_real);
            })
            : 0;

        return response()->json([
            'resumen' => [
                'total_ordenes' => $ordenes->count(),
                'valor_total' => round($ordenes->sum('total'), 2),
                'tiempo_promedio_dias' => round($tiempoPromedio, 1)
            ],
            'por_estado' => $porEstado,
            'vencidas' => $vencidas
        ]);
    }

    /**
     * Reporte: Consumo por Laboratorio
     * Consumo de materiales por laboratorio en un período
     */
    public function consumoPorLaboratorio(Request $request)
    {
        $validated = $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date',
            'laboratorio_id' => 'nullable|exists:laboratorios,id'
        ]);

        // Obtener salidas de inventario vinculadas a solicitudes
        $query = MovimientoInventario::with(['item', 'solicitud.laboratorio'])
                                     ->where('tipo', 'salida')
                                     ->whereBetween('fecha_movimiento', [
                                         $validated['fecha_inicio'],
                                         $validated['fecha_fin']
                                     ])
                                     ->whereNotNull('solicitud_id');

        $movimientos = $query->get();

        // Agrupar por laboratorio
        $porLaboratorio = $movimientos->groupBy('solicitud.laboratorio.nombre')->map(function($movs, $laboratorio) {
            $itemsAgrupados = $movs->groupBy('item_id')->map(function($itemMovs) {
                $item = $itemMovs->first()->item;
                $cantidadTotal = $itemMovs->sum('cantidad');
                
                // Calcular valor (usando último precio)
                $ultimoPrecio = HistorialPrecio::where('item_id', $item->id)
                                              ->orderBy('fecha_vigencia', 'desc')
                                              ->first();
                
                $valor = $cantidadTotal * ($ultimoPrecio ? $ultimoPrecio->precio : 0);

                return [
                    'item' => $item->nombre,
                    'cantidad' => $cantidadTotal,
                    'unidad' => $item->unidad_medida_base,
                    'valor' => round($valor, 2)
                ];
            })->sortByDesc('valor')->take(10)->values();

            return [
                'laboratorio' => $laboratorio ?: 'Sin laboratorio',
                'items_consumidos' => $movs->pluck('item_id')->unique()->count(),
                'valor_total' => round($itemsAgrupados->sum('valor'), 2),
                'top_items' => $itemsAgrupados
            ];
        })->values();

        return response()->json([
            'periodo' => $validated['fecha_inicio'] . ' a ' . $validated['fecha_fin'],
            'por_laboratorio' => $porLaboratorio
        ]);
    }

    /**
     * Reporte: Estado de Préstamos
     * Resumen de préstamos activos, vencidos y devueltos
     */
    public function estadoPrestamos(Request $request)
    {
        $validated = $request->validate([
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date'
        ]);

        $query = Prestamo::with(['item', 'almacen']);

        if (isset($validated['fecha_inicio'])) {
            $query->where('fecha_prestamo', '>=', $validated['fecha_inicio']);
        }

        if (isset($validated['fecha_fin'])) {
            $query->where('fecha_prestamo', '<=', $validated['fecha_fin']);
        }

        $prestamos = $query->get();

        // Clasificar préstamos
        $activos = $prestamos->where('estado', 'activo');
        $vencidos = $prestamos->where('estado', 'vencido');
        $devueltos = $prestamos->where('estado', 'devuelto');

        // Calcular tasa de devolución a tiempo
        $devueltosATiempo = $devueltos->filter(function($prestamo) {
            return $prestamo->fecha_devolucion_real <= $prestamo->fecha_devolucion_estimada;
        })->count();

        $tasaDevolucionTiempo = $devueltos->count() > 0 
            ? round(($devueltosATiempo / $devueltos->count()) * 100, 1)
            : 0;

        // Préstamos vencidos detalle
        $vencidosDetalle = $vencidos->map(function($prestamo) {
            return [
                'id' => $prestamo->id,
                'equipo' => $prestamo->item->nombre,
                'custodio' => $prestamo->custodio_nombre,
                'dias_vencido' => now()->diffInDays($prestamo->fecha_devolucion_estimada),
                'fecha_prestamo' => $prestamo->fecha_prestamo->format('Y-m-d'),
                'fecha_devolucion_estimada' => $prestamo->fecha_devolucion_estimada->format('Y-m-d')
            ];
        })->values();

        // Equipos más prestados
        $equiposMasPrestados = $prestamos->groupBy('item_id')->map(function($prests) {
            $item = $prests->first()->item;
            $diasPromedio = $prests->filter(function($p) {
                return $p->fecha_devolucion_real;
            })->avg(function($p) {
                return $p->fecha_prestamo->diffInDays($p->fecha_devolucion_real);
            });

            return [
                'equipo' => $item->nombre,
                'prestamos_totales' => $prests->count(),
                'dias_promedio' => round($diasPromedio ?: 0, 1)
            ];
        })->sortByDesc('prestamos_totales')->take(10)->values();

        return response()->json([
            'resumen' => [
                'activos' => $activos->count(),
                'vencidos' => $vencidos->count(),
                'devueltos_periodo' => $devueltos->count(),
                'tasa_devolucion_tiempo' => $tasaDevolucionTiempo
            ],
            'vencidos' => $vencidosDetalle,
            'equipos_mas_prestados' => $equiposMasPrestados
        ]);
    }

    /**
     * Reporte: Inversión en Inventario
     * Análisis de inversión total en inventario
     */
    public function inversionInventario(Request $request)
    {
        $validated = $request->validate([
            'almacen_id' => 'nullable|exists:almacenes,id',
            'categoria_id' => 'nullable|exists:categorias,id'
        ]);

        $query = Inventario::with(['item.categoria', 'almacen'])
                           ->where('cantidad_actual', '>', 0);

        if (isset($validated['almacen_id'])) {
            $query->where('almacen_id', $validated['almacen_id']);
        }

        if (isset($validated['categoria_id'])) {
            $query->whereHas('item', function($q) use ($validated) {
                $q->where('categoria_id', $validated['categoria_id']);
            });
        }

        $inventarios = $query->get();

        // Calcular inversión
        $inversion = $inventarios->map(function($inv) {
            $ultimoPrecio = HistorialPrecio::where('item_id', $inv->item_id)
                                          ->orderBy('fecha_vigencia', 'desc')
                                          ->first();
            
            $precio = $ultimoPrecio ? $ultimoPrecio->precio : 0;
            $valor = $inv->cantidad_actual * $precio;

            return [
                'item' => $inv->item->nombre,
                'categoria' => $inv->item->categoria->nombre,
                'almacen' => $inv->almacen->nombre,
                'cantidad' => $inv->cantidad_actual,
                'precio_unitario' => $precio,
                'valor_total' => $valor
            ];
        });

        // Agrupar por categoría
        $porCategoria = $inversion->groupBy('categoria')->map(function($items, $categoria) {
            return [
                'categoria' => $categoria,
                'inversion' => round($items->sum('valor_total'), 2),
                'items' => $items->count()
            ];
        })->sortByDesc('inversion')->values();

        // Items de alto valor (top 20%)
        $totalItems = $inversion->count();
        $topCount = max(1, (int)($totalItems * 0.2));
        $itemsAltoValor = $inversion->sortByDesc('valor_total')->take($topCount)->values();

        return response()->json([
            'resumen' => [
                'inversion_total' => round($inversion->sum('valor_total'), 2),
                'items_totales' => $totalItems,
                'categorias' => $porCategoria->count()
            ],
            'por_categoria' => $porCategoria,
            'items_alto_valor' => $itemsAltoValor
        ]);
    }
}
