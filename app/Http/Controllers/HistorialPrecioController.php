<?php

namespace App\Http\Controllers;

use App\Models\HistorialPrecio;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class HistorialPrecioController extends Controller
{
    /**
     * Listar historial de precios.
     */
    public function index(Request $request): JsonResponse
    {
        $query = HistorialPrecio::with(['item', 'proveedor']);

        // Filtro por ítem
        if ($request->has('item_id')) {
            $query->porItem($request->item_id);
        }

        // Filtro por proveedor
        if ($request->has('proveedor_id')) {
            $query->porProveedor($request->proveedor_id);
        }

        // Ordenamiento por fecha de vigencia (más recientes primero)
        $query->vigentes();

        // Paginación
        $perPage = $request->get('per_page', 15);
        $historial = $query->paginate($perPage);

        return response()->json($historial);
    }

    /**
     * Registrar un nuevo precio.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'item_id' => 'required|exists:items,id',
            'proveedor_id' => 'required|exists:proveedores,id',
            'precio' => 'required|numeric|min:0.01',
            'fecha_vigencia' => 'required|date',
            'moneda' => 'nullable|string|max:3',
            'observaciones' => 'nullable|string',
        ], [
            'item_id.required' => 'El ítem es obligatorio.',
            'item_id.exists' => 'El ítem seleccionado no existe.',
            'proveedor_id.required' => 'El proveedor es obligatorio.',
            'proveedor_id.exists' => 'El proveedor seleccionado no existe.',
            'precio.required' => 'El precio es obligatorio.',
            'precio.min' => 'El precio debe ser mayor a 0.',
            'fecha_vigencia.required' => 'La fecha de vigencia es obligatoria.',
        ]);

        $historial = HistorialPrecio::create($validated);
        $historial->load(['item', 'proveedor']);

        return response()->json([
            'message' => 'Precio registrado exitosamente.',
            'data' => $historial
        ], 201);
    }

    /**
     * Mostrar un registro de precio específico.
     */
    public function show(int $id): JsonResponse
    {
        $historial = HistorialPrecio::with(['item', 'proveedor'])->find($id);

        if (!$historial) {
            return response()->json([
                'message' => 'Registro de precio no encontrado.'
            ], 404);
        }

        return response()->json([
            'data' => $historial
        ]);
    }

    /**
     * Actualizar un registro de precio.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $historial = HistorialPrecio::find($id);

        if (!$historial) {
            return response()->json([
                'message' => 'Registro de precio no encontrado.'
            ], 404);
        }

        $validated = $request->validate([
            'item_id' => 'required|exists:items,id',
            'proveedor_id' => 'required|exists:proveedores,id',
            'precio' => 'required|numeric|min:0.01',
            'fecha_vigencia' => 'required|date',
            'moneda' => 'nullable|string|max:3',
            'observaciones' => 'nullable|string',
        ], [
            'item_id.required' => 'El ítem es obligatorio.',
            'item_id.exists' => 'El ítem seleccionado no existe.',
            'proveedor_id.required' => 'El proveedor es obligatorio.',
            'proveedor_id.exists' => 'El proveedor seleccionado no existe.',
            'precio.required' => 'El precio es obligatorio.',
            'precio.min' => 'El precio debe ser mayor a 0.',
            'fecha_vigencia.required' => 'La fecha de vigencia es obligatoria.',
        ]);

        $historial->update($validated);
        $historial->load(['item', 'proveedor']);

        return response()->json([
            'message' => 'Precio actualizado exitosamente.',
            'data' => $historial
        ]);
    }

    /**
     * Eliminar un registro de precio.
     */
    public function destroy(int $id): JsonResponse
    {
        $historial = HistorialPrecio::find($id);

        if (!$historial) {
            return response()->json([
                'message' => 'Registro de precio no encontrado.'
            ], 404);
        }

        $historial->delete();

        return response()->json([
            'message' => 'Precio eliminado exitosamente.'
        ]);
    }

    /**
     * Comparativa de proveedores para un ítem específico.
     */
    public function comparativa(int $itemId): JsonResponse
    {
        $item = Item::find($itemId);

        if (!$item) {
            return response()->json([
                'message' => 'Ítem no encontrado.'
            ], 404);
        }

        // Obtener el precio más reciente de cada proveedor para este ítem
        $precios = DB::table('historial_precios as hp1')
            ->select(
                'hp1.proveedor_id',
                'proveedores.nombre as proveedor_nombre',
                'hp1.precio',
                'hp1.fecha_vigencia',
                'hp1.moneda'
            )
            ->join('proveedores', 'proveedores.id', '=', 'hp1.proveedor_id')
            ->where('hp1.item_id', $itemId)
            ->whereIn('hp1.id', function ($query) use ($itemId) {
                $query->select(DB::raw('MAX(id)'))
                    ->from('historial_precios')
                    ->where('item_id', $itemId)
                    ->groupBy('proveedor_id');
            })
            ->where('proveedores.activo', true)
            ->orderBy('hp1.precio', 'asc')
            ->get();

        if ($precios->isEmpty()) {
            return response()->json([
                'message' => 'No hay precios registrados para este ítem.',
                'data' => []
            ]);
        }

        // Calcular estadísticas
        $precioMinimo = $precios->min('precio');
        $precioPromedio = $precios->avg('precio');
        $proveedorMejor = $precios->first();

        return response()->json([
            'item' => [
                'id' => $item->id,
                'codigo' => $item->codigo,
                'nombre' => $item->nombre,
            ],
            'mejor_opcion' => [
                'proveedor_id' => $proveedorMejor->proveedor_id,
                'proveedor_nombre' => $proveedorMejor->proveedor_nombre,
                'precio' => $proveedorMejor->precio,
                'fecha_vigencia' => $proveedorMejor->fecha_vigencia,
                'moneda' => $proveedorMejor->moneda,
            ],
            'estadisticas' => [
                'precio_minimo' => $precioMinimo,
                'precio_promedio' => round($precioPromedio, 2),
                'ahorro_vs_promedio' => round($precioPromedio - $precioMinimo, 2),
                'proveedores_disponibles' => $precios->count(),
            ],
            'proveedores' => $precios->map(function ($precio) use ($precioMinimo) {
                return [
                    'proveedor_id' => $precio->proveedor_id,
                    'proveedor_nombre' => $precio->proveedor_nombre,
                    'precio' => $precio->precio,
                    'diferencia_vs_mejor' => round($precio->precio - $precioMinimo, 2),
                    'porcentaje_diferencia' => $precioMinimo > 0 
                        ? round((($precio->precio - $precioMinimo) / $precioMinimo) * 100, 2) 
                        : 0,
                    'fecha_vigencia' => $precio->fecha_vigencia,
                    'es_mejor_precio' => $precio->precio == $precioMinimo,
                ];
            }),
        ]);
    }

    /**
     * Tendencia de precios de un ítem en el tiempo.
     */
    public function tendencia(int $itemId): JsonResponse
    {
        $item = Item::find($itemId);

        if (!$item) {
            return response()->json([
                'message' => 'Ítem no encontrado.'
            ], 404);
        }

        $historial = HistorialPrecio::with('proveedor')
            ->where('item_id', $itemId)
            ->orderBy('fecha_vigencia', 'desc')
            ->get()
            ->map(function ($precio) {
                return [
                    'fecha' => $precio->fecha_vigencia->format('Y-m-d'),
                    'precio' => $precio->precio,
                    'proveedor' => $precio->proveedor->nombre,
                    'moneda' => $precio->moneda,
                ];
            });

        return response()->json([
            'item' => [
                'id' => $item->id,
                'codigo' => $item->codigo,
                'nombre' => $item->nombre,
            ],
            'historial' => $historial,
        ]);
    }
}
