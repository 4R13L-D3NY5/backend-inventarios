<?php

namespace App\Http\Controllers;

use App\Models\OrdenCompra;
use App\Models\OrdenCompraItem;
use App\Models\Proveedor;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OrdenCompraController extends Controller
{
    /**
     * Listar órdenes de compra con filtros
     */
    public function index(Request $request)
    {
        $query = OrdenCompra::with(['proveedor', 'usuarioCreador', 'usuarioAprobador', 'items.item']);

        // Filtros
        if ($request->has('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->has('proveedor_id')) {
            $query->where('proveedor_id', $request->proveedor_id);
        }

        if ($request->has('fecha_desde')) {
            $query->where('fecha_emision', '>=', $request->fecha_desde);
        }

        if ($request->has('fecha_hasta')) {
            $query->where('fecha_emision', '<=', $request->fecha_hasta);
        }

        if ($request->has('activo')) {
            $query->where('activo', $request->boolean('activo'));
        }

        $ordenes = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json($ordenes);
    }

    /**
     * Crear nueva orden de compra
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'solicitud_id' => 'nullable|exists:solicitudes,id',
            'proveedor_id' => 'required|exists:proveedores,id',
            'fecha_emision' => 'required|date',
            'fecha_entrega_estimada' => 'nullable|date|after_or_equal:fecha_emision',
            'condiciones_pago' => 'nullable|string',
            'observaciones' => 'nullable|string',
            'moneda' => 'nullable|string|max:10',
            'impuestos' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:items,id',
            'items.*.cantidad_solicitada' => 'required|numeric|min:0.01',
            'items.*.unidad_medida' => 'required|string|max:50',
            'items.*.precio_unitario' => 'required|numeric|min:0.01',
            'items.*.observaciones' => 'nullable|string',
        ]);

        // Validar que el proveedor esté activo
        $proveedor = Proveedor::find($validated['proveedor_id']);
        if (!$proveedor->activo) {
            return response()->json([
                'message' => 'El proveedor seleccionado no está activo'
            ], 422);
        }

        // Validar que todos los ítems estén activos
        foreach ($validated['items'] as $itemData) {
            $item = Item::find($itemData['item_id']);
            if (!$item->activo) {
                return response()->json([
                    'message' => "El ítem {$item->nombre} no está activo"
                ], 422);
            }
        }

        DB::beginTransaction();
        try {
            // Crear la orden
            $orden = OrdenCompra::create([
                'numero_orden' => OrdenCompra::generarNumeroOrden(),
                'solicitud_id' => $validated['solicitud_id'] ?? null,
                'proveedor_id' => $validated['proveedor_id'],
                'fecha_emision' => $validated['fecha_emision'],
                'fecha_entrega_estimada' => $validated['fecha_entrega_estimada'] ?? null,
                'moneda' => $validated['moneda'] ?? 'BOB',
                'impuestos' => $validated['impuestos'] ?? 0,
                'condiciones_pago' => $validated['condiciones_pago'] ?? null,
                'observaciones' => $validated['observaciones'] ?? null,
                'usuario_creador_id' => auth()->id(),
                'estado' => 'borrador',
            ]);

            // Crear los ítems
            foreach ($validated['items'] as $itemData) {
                OrdenCompraItem::create([
                    'orden_compra_id' => $orden->id,
                    'item_id' => $itemData['item_id'],
                    'cantidad_solicitada' => $itemData['cantidad_solicitada'],
                    'unidad_medida' => $itemData['unidad_medida'],
                    'precio_unitario' => $itemData['precio_unitario'],
                    'observaciones' => $itemData['observaciones'] ?? null,
                ]);
            }

            // Calcular totales
            $orden->calcularTotales();

            DB::commit();

            return response()->json([
                'message' => 'Orden de compra creada exitosamente',
                'data' => $orden->load(['items.item', 'proveedor'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al crear la orden de compra',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar orden de compra específica
     */
    public function show($id)
    {
        $orden = OrdenCompra::with([
            'proveedor',
            'solicitud',
            'usuarioCreador.personal',
            'usuarioAprobador.personal',
            'items.item.categoria',
            'items.item.subcategoria',
            'movimientosInventario.almacen'
        ])->findOrFail($id);

        return response()->json($orden);
    }

    /**
     * Actualizar orden de compra (solo en estado borrador)
     */
    public function update(Request $request, $id)
    {
        $orden = OrdenCompra::findOrFail($id);

        // Solo se puede editar si está en borrador
        if ($orden->estado !== 'borrador') {
            return response()->json([
                'message' => 'Solo se pueden editar órdenes en estado borrador'
            ], 422);
        }

        $validated = $request->validate([
            'proveedor_id' => 'sometimes|exists:proveedores,id',
            'fecha_emision' => 'sometimes|date',
            'fecha_entrega_estimada' => 'nullable|date',
            'condiciones_pago' => 'nullable|string',
            'observaciones' => 'nullable|string',
            'moneda' => 'nullable|string|max:10',
            'impuestos' => 'nullable|numeric|min:0',
            'items' => 'sometimes|array|min:1',
            'items.*.item_id' => 'required|exists:items,id',
            'items.*.cantidad_solicitada' => 'required|numeric|min:0.01',
            'items.*.unidad_medida' => 'required|string|max:50',
            'items.*.precio_unitario' => 'required|numeric|min:0.01',
            'items.*.observaciones' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Actualizar orden
            $orden->update($validated);

            // Si se enviaron ítems, reemplazarlos
            if (isset($validated['items'])) {
                $orden->items()->delete();

                foreach ($validated['items'] as $itemData) {
                    OrdenCompraItem::create([
                        'orden_compra_id' => $orden->id,
                        'item_id' => $itemData['item_id'],
                        'cantidad_solicitada' => $itemData['cantidad_solicitada'],
                        'unidad_medida' => $itemData['unidad_medida'],
                        'precio_unitario' => $itemData['precio_unitario'],
                        'observaciones' => $itemData['observaciones'] ?? null,
                    ]);
                }

                $orden->calcularTotales();
            }

            DB::commit();

            return response()->json([
                'message' => 'Orden de compra actualizada exitosamente',
                'data' => $orden->load(['items.item', 'proveedor'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al actualizar la orden de compra',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar orden de compra (solo en estado borrador)
     */
    public function destroy($id)
    {
        $orden = OrdenCompra::findOrFail($id);

        if ($orden->estado !== 'borrador') {
            return response()->json([
                'message' => 'Solo se pueden eliminar órdenes en estado borrador'
            ], 422);
        }

        $orden->delete();

        return response()->json([
            'message' => 'Orden de compra eliminada exitosamente'
        ]);
    }

    /**
     * Aprobar orden de compra
     */
    public function aprobar($id)
    {
        $orden = OrdenCompra::findOrFail($id);

        if ($orden->aprobar(auth()->user())) {
            return response()->json([
                'message' => 'Orden de compra aprobada exitosamente',
                'data' => $orden->load(['items.item', 'proveedor', 'usuarioAprobador'])
            ]);
        }

        return response()->json([
            'message' => 'No se puede aprobar la orden. Verifique el estado y que tenga ítems.'
        ], 422);
    }

    /**
     * Confirmar orden de compra (proveedor acepta)
     */
    public function confirmar($id)
    {
        $orden = OrdenCompra::findOrFail($id);

        if ($orden->confirmar()) {
            return response()->json([
                'message' => 'Orden de compra confirmada exitosamente',
                'data' => $orden
            ]);
        }

        return response()->json([
            'message' => 'No se puede confirmar la orden. Debe estar en estado "enviada".'
        ], 422);
    }

    /**
     * Registrar recepción de ítems
     */
    public function registrarRecepcion(Request $request, $id)
    {
        $orden = OrdenCompra::findOrFail($id);

        $validated = $request->validate([
            'almacen_id' => 'required|exists:almacenes,id',
            'items' => 'required|array|min:1',
            'items.*.orden_compra_item_id' => 'required|exists:orden_compra_items,id',
            'items.*.cantidad' => 'required|numeric|min:0.01',
        ]);

        if ($orden->registrarRecepcion($validated['items'], $validated['almacen_id'], auth()->id())) {
            return response()->json([
                'message' => 'Recepción registrada exitosamente',
                'data' => $orden->fresh(['items.item', 'movimientosInventario'])
            ]);
        }

        return response()->json([
            'message' => 'No se puede registrar la recepción. Verifique el estado de la orden.'
        ], 422);
    }

    /**
     * Cancelar orden de compra
     */
    public function cancelar(Request $request, $id)
    {
        $orden = OrdenCompra::findOrFail($id);

        $validated = $request->validate([
            'motivo' => 'required|string|max:500'
        ]);

        if ($orden->cancelar($validated['motivo'])) {
            return response()->json([
                'message' => 'Orden de compra cancelada exitosamente',
                'data' => $orden
            ]);
        }

        return response()->json([
            'message' => 'No se puede cancelar la orden. Verifique el estado.'
        ], 422);
    }

    /**
     * Listar órdenes por estado
     */
    public function porEstado($estado)
    {
        $estadosValidos = ['borrador', 'enviada', 'confirmada', 'recibida_parcial', 'recibida_completa', 'cancelada'];

        if (!in_array($estado, $estadosValidos)) {
            return response()->json([
                'message' => 'Estado no válido'
            ], 422);
        }

        $ordenes = OrdenCompra::with(['proveedor', 'items.item'])
                              ->porEstado($estado)
                              ->orderBy('created_at', 'desc')
                              ->paginate(15);

        return response()->json($ordenes);
    }

    /**
     * Órdenes pendientes de recepción
     */
    public function pendientesRecepcion()
    {
        $ordenes = OrdenCompra::with(['proveedor', 'items.item'])
                              ->pendientesRecepcion()
                              ->orderBy('fecha_entrega_estimada')
                              ->get();

        return response()->json($ordenes);
    }

    /**
     * Historial de órdenes completadas/canceladas
     */
    public function historial()
    {
        $ordenes = OrdenCompra::with(['proveedor', 'items.item'])
                              ->whereIn('estado', ['recibida_completa', 'cancelada'])
                              ->orderBy('updated_at', 'desc')
                              ->paginate(20);

        return response()->json($ordenes);
    }
}
