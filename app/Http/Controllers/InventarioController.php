<?php

namespace App\Http\Controllers;

use App\Models\Inventario;
use App\Models\Item;
use App\Models\Almacen;
use App\Models\MovimientoInventario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventarioController extends Controller
{
    /**
     * Vista general de inventario
     */
    public function index(Request $request)
    {
        $query = Inventario::with(['almacen', 'item.categoria']);

        // Filtros
        if ($request->has('almacen_id')) {
            $query->where('almacen_id', $request->almacen_id);
        }

        if ($request->has('categoria_id')) {
            $query->whereHas('item', function($q) use ($request) {
                $q->where('categoria_id', $request->categoria_id);
            });
        }

        if ($request->has('bajo_stock')) {
            if ($request->bajo_stock == '1' || $request->bajo_stock === 'true') {
                $query->bajoStock();
            }
        }

        $inventarios = $query->paginate($request->get('per_page', 15));

        return response()->json($inventarios);
    }

    /**
     * Stock detallado por ubicación (tabla cruzada)
     */
    public function stockDetallado(Request $request)
    {
        $almacenes = Almacen::activos()->get();
        
        $query = Item::with(['categoria', 'subcategoria']);

        if ($request->has('categoria_id')) {
            $query->where('categoria_id', $request->categoria_id);
        }

        $items = $query->get();

        $resultado = $items->map(function ($item) use ($almacenes) {
            $stockPorAlmacen = [];
            $totalGeneral = 0;

            foreach ($almacenes as $almacen) {
                $inventario = Inventario::where('item_id', $item->id)
                    ->where('almacen_id', $almacen->id)
                    ->first();
                
                $cantidad = $inventario ? $inventario->cantidad_actual : 0;
                $stockPorAlmacen[$almacen->codigo] = $cantidad;
                $totalGeneral += $cantidad;
            }

            return [
                'item_id' => $item->id,
                'codigo' => $item->codigo,
                'nombre' => $item->nombre,
                'categoria' => $item->categoria->nombre ?? null,
                'unidad_medida' => $item->unidad_medida,
                'stock_por_almacen' => $stockPorAlmacen,
                'total' => $totalGeneral,
            ];
        });

        return response()->json([
            'almacenes' => $almacenes->pluck('nombre', 'codigo'),
            'items' => $resultado
        ]);
    }

    /**
     * Stock por categoría
     */
    public function stockPorCategoria(Request $request)
    {
        $categorias = DB::table('items')
            ->join('categorias', 'items.categoria_id', '=', 'categorias.id')
            ->join('inventarios', 'items.id', '=', 'inventarios.item_id')
            ->leftJoin('historial_precios', function($join) {
                $join->on('items.id', '=', 'historial_precios.item_id')
                     ->whereRaw('historial_precios.id = (
                         SELECT id FROM historial_precios hp 
                         WHERE hp.item_id = items.id 
                         ORDER BY fecha_vigencia DESC 
                         LIMIT 1
                     )');
            })
            ->select(
                'categorias.id',
                'categorias.nombre as categoria',
                DB::raw('COUNT(DISTINCT items.id) as total_items'),
                DB::raw('SUM(inventarios.cantidad_actual) as cantidad_total'),
                DB::raw('SUM(inventarios.cantidad_actual * COALESCE(historial_precios.precio, 0)) as valor_total')
            )
            ->groupBy('categorias.id', 'categorias.nombre')
            ->get();

        return response()->json($categorias);
    }

    /**
     * Ítems bajo stock
     */
    public function itemsBajoStock(Request $request)
    {
        $query = Inventario::with(['item.categoria', 'almacen'])
            ->bajoStock();

        if ($request->has('almacen_id')) {
            $query->where('almacen_id', $request->almacen_id);
        }

        if ($request->has('nivel_criticidad')) {
            // bajo: stock < minimo
            // critico: stock < minimo * 0.5
            // muy_critico: stock = 0
            $nivel = $request->nivel_criticidad;
            
            if ($nivel === 'critico') {
                $query->whereRaw('cantidad_actual < (stock_minimo * 0.5)');
            } elseif ($nivel === 'muy_critico') {
                $query->where('cantidad_actual', 0);
            }
        }

        $items = $query->get()->map(function ($inventario) {
            $porcentaje = $inventario->stock_minimo > 0 
                ? ($inventario->cantidad_actual / $inventario->stock_minimo) * 100 
                : 0;

            return [
                'item' => $inventario->item->nombre,
                'codigo' => $inventario->item->codigo,
                'categoria' => $inventario->item->categoria->nombre ?? null,
                'almacen' => $inventario->almacen->nombre,
                'cantidad_actual' => $inventario->cantidad_actual,
                'stock_minimo' => $inventario->stock_minimo,
                'porcentaje_stock' => round($porcentaje, 2),
                'unidad_medida' => $inventario->item->unidad_medida,
            ];
        });

        return response()->json([
            'total' => $items->count(),
            'items' => $items
        ]);
    }

    /**
     * Valoración del inventario
     */
    public function valoracionInventario(Request $request)
    {
        $query = Inventario::with(['item.historialPrecios']);

        if ($request->has('almacen_id')) {
            $query->where('almacen_id', $request->almacen_id);
        }

        $inventarios = $query->get();
        $valorTotal = 0;
        $detalle = [];

        foreach ($inventarios as $inventario) {
            $precioUnitario = $inventario->item->historialPrecios()
                ->orderBy('fecha_vigencia', 'desc')
                ->first()?->precio ?? 0;

            $valorItem = $inventario->cantidad_actual * $precioUnitario;
            $valorTotal += $valorItem;

            if ($valorItem > 0) {
                $detalle[] = [
                    'item' => $inventario->item->nombre,
                    'cantidad' => $inventario->cantidad_actual,
                    'precio_unitario' => $precioUnitario,
                    'valor_total' => $valorItem,
                ];
            }
        }

        return response()->json([
            'valor_total' => round($valorTotal, 2),
            'moneda' => 'BOB',
            'total_items' => count($detalle),
            'detalle' => $detalle
        ]);
    }

    /**
     * Kardex de un ítem específico
     */
    public function kardex($itemId, Request $request)
    {
        $item = Item::findOrFail($itemId);

        $query = MovimientoInventario::with(['almacenOrigen', 'almacenDestino', 'responsable'])
            ->where('item_id', $itemId);

        if ($request->has('almacen_id')) {
            $almacenId = $request->almacen_id;
            $query->where(function($q) use ($almacenId) {
                $q->where('almacen_origen_id', $almacenId)
                  ->orWhere('almacen_destino_id', $almacenId);
            });
        }

        if ($request->has('fecha_desde')) {
            $query->whereDate('fecha_movimiento', '>=', $request->fecha_desde);
        }

        if ($request->has('fecha_hasta')) {
            $query->whereDate('fecha_movimiento', '<=', $request->fecha_hasta);
        }

        $movimientos = $query->orderBy('fecha_movimiento', 'asc')
            ->get()
            ->map(function ($mov) {
                return [
                    'fecha' => $mov->fecha_movimiento,
                    'tipo' => $mov->tipo,
                    'cantidad' => $mov->cantidad,
                    'origen' => $mov->almacenOrigen?->nombre,
                    'destino' => $mov->almacenDestino?->nombre,
                    'responsable' => $mov->responsable->name,
                    'motivo' => $mov->motivo,
                ];
            });

        return response()->json([
            'item' => $item,
            'movimientos' => $movimientos
        ]);
    }
}
