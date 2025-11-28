<?php

namespace App\Http\Controllers;

use App\Models\MovimientoInventario;
use App\Models\Inventario;
use Illuminate\Http\Request;

class MovimientoInventarioController extends Controller
{
    /**
     * Listar movimientos con filtros
     */
    public function index(Request $request)
    {
        $query = MovimientoInventario::with([
            'item',
            'almacenOrigen',
            'almacenDestino',
            'responsable'
        ]);

        // Filtros
        if ($request->has('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        if ($request->has('almacen_id')) {
            $almacenId = $request->almacen_id;
            $query->where(function($q) use ($almacenId) {
                $q->where('almacen_origen_id', $almacenId)
                  ->orWhere('almacen_destino_id', $almacenId);
            });
        }

        if ($request->has('item_id')) {
            $query->where('item_id', $request->item_id);
        }

        if ($request->has('fecha_desde')) {
            $query->whereDate('fecha_movimiento', '>=', $request->fecha_desde);
        }

        if ($request->has('fecha_hasta')) {
            $query->whereDate('fecha_movimiento', '<=', $request->fecha_hasta);
        }

        // Ordenamiento
        $sortBy = $request->get('sort_by', 'fecha_movimiento');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $movimientos = $query->paginate($request->get('per_page', 15));

        return response()->json($movimientos);
    }

    /**
     * Ver detalle de movimiento
     */
    public function show($id)
    {
        $movimiento = MovimientoInventario::with([
            'item.categoria',
            'almacenOrigen',
            'almacenDestino',
            'responsable',
            'solicitud',
        ])->findOrFail($id);

        return response()->json($movimiento);
    }

    /**
     * Registrar entrada de inventario
     */
    public function registrarEntrada(Request $request)
    {
        $validated = $request->validate([
            'item_id' => 'required|exists:items,id',
            'cantidad' => 'required|numeric|min:0.01',
            'unidad_medida' => 'required|string|max:20',
            'almacen_id' => 'required|exists:almacenes,id',
            'motivo' => 'required|string|max:500',
            'observaciones' => 'nullable|string',
            'fecha_movimiento' => 'nullable|date',
            'stock_minimo' => 'nullable|numeric|min:0',
        ], [
            'item_id.required' => 'Debe seleccionar un ítem.',
            'cantidad.required' => 'La cantidad es obligatoria.',
            'cantidad.min' => 'La cantidad debe ser mayor a 0.',
            'almacen_id.required' => 'Debe seleccionar un almacén.',
            'motivo.required' => 'El motivo es obligatorio.',
        ]);

        $validated['responsable_id'] = auth()->id();

        try {
            $movimiento = MovimientoInventario::registrarEntrada($validated);

            return response()->json([
                'message' => 'Entrada registrada exitosamente.',
                'data' => $movimiento->load(['item', 'almacenDestino', 'responsable'])
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al registrar entrada: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * Registrar salida de inventario
     */
    public function registrarSalida(Request $request)
    {
        $validated = $request->validate([
            'item_id' => 'required|exists:items,id',
            'cantidad' => 'required|numeric|min:0.01',
            'unidad_medida' => 'required|string|max:20',
            'almacen_id' => 'required|exists:almacenes,id',
            'solicitud_id' => 'nullable|exists:solicitudes,id',
            'motivo' => 'required|string|max:500',
            'observaciones' => 'nullable|string',
            'fecha_movimiento' => 'nullable|date',
        ]);

        $validated['responsable_id'] = auth()->id();

        try {
            $movimiento = MovimientoInventario::registrarSalida($validated);

            return response()->json([
                'message' => 'Salida registrada exitosamente.',
                'data' => $movimiento->load(['item', 'almacenOrigen', 'responsable'])
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al registrar salida: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * Registrar traspaso entre almacenes
     */
    public function registrarTraspaso(Request $request)
    {
        $validated = $request->validate([
            'item_id' => 'required|exists:items,id',
            'cantidad' => 'required|numeric|min:0.01',
            'unidad_medida' => 'required|string|max:20',
            'almacen_origen_id' => 'required|exists:almacenes,id',
            'almacen_destino_id' => 'required|exists:almacenes,id|different:almacen_origen_id',
            'motivo' => 'required|string|max:500',
            'observaciones' => 'nullable|string',
            'fecha_movimiento' => 'nullable|date',
            'stock_minimo' => 'nullable|numeric|min:0',
        ], [
            'almacen_destino_id.different' => 'El almacén destino debe ser diferente al origen.',
        ]);

        $validated['responsable_id'] = auth()->id();

        try {
            $movimiento = MovimientoInventario::registrarTraspaso($validated);

            return response()->json([
                'message' => 'Traspaso registrado exitosamente.',
                'data' => $movimiento->load(['item', 'almacenOrigen', 'almacenDestino', 'responsable'])
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al registrar traspaso: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * Registrar ajuste de inventario
     */
    public function registrarAjuste(Request $request)
    {
        $validated = $request->validate([
            'item_id' => 'required|exists:items,id',
            'almacen_id' => 'required|exists:almacenes,id',
            'cantidad_nueva' => 'required|numeric|min:0',
            'unidad_medida' => 'required|string|max:20',
            'motivo' => 'required|string|max:500',
            'observaciones' => 'nullable|string',
            'fecha_movimiento' => 'nullable|date',
        ]);

        $validated['responsable_id'] = auth()->id();

        try {
            $movimiento = MovimientoInventario::registrarAjuste($validated);

            return response()->json([
                'message' => 'Ajuste registrado exitosamente.',
                'data' => $movimiento->load(['item', 'responsable'])
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al registrar ajuste: ' . $e->getMessage()
            ], 422);
        }
    }
}
