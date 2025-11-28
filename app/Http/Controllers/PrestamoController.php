<?php

namespace App\Http\Controllers;

use App\Models\Prestamo;
use App\Models\Item;
use App\Models\Inventario;
use App\Models\MovimientoInventario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PrestamoController extends Controller
{
    /**
     * Listar préstamos con filtros
     */
    public function index(Request $request)
    {
        $query = Prestamo::with([
            'item',
            'custodio',
            'autorizadoPor',
            'almacen',
            'laboratorio'
        ]);

        // Filtros
        if ($request->has('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->has('custodio_id')) {
            $query->where('custodio_id', $request->custodio_id);
        }

        if ($request->has('laboratorio_id')) {
            $query->where('laboratorio_id', $request->laboratorio_id);
        }

        if ($request->has('fecha_desde')) {
            $query->whereDate('fecha_prestamo', '>=', $request->fecha_desde);
        }

        if ($request->has('fecha_hasta')) {
            $query->whereDate('fecha_prestamo', '<=', $request->fecha_hasta);
        }

        // Ordenamiento
        $sortBy = $request->get('sort_by', 'fecha_prestamo');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $prestamos = $query->paginate($request->get('per_page', 15));

        return response()->json($prestamos);
    }

    /**
     * Crear nuevo préstamo
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'item_id' => [
                'required',
                'exists:items,id',
                function ($attribute, $value, $fail) {
                    $item = Item::find($value);
                    if ($item && $item->es_consumible) {
                        $fail('Solo se pueden prestar equipos retornables (no consumibles).');
                    }
                },
            ],
            'custodio_id' => 'required|exists:personals,id',
            'almacen_id' => 'required|exists:almacenes,id',
            'laboratorio_id' => 'nullable|exists:laboratorios,id',
            'fecha_prestamo' => 'required|date',
            'fecha_vencimiento' => 'required|date|after:fecha_prestamo',
            'estado_equipo_prestamo' => 'required|in:bueno,regular,malo',
            'observaciones_prestamo' => 'nullable|string',
        ], [
            'item_id.required' => 'Debe seleccionar un equipo.',
            'custodio_id.required' => 'Debe seleccionar un custodio.',
            'fecha_vencimiento.after' => 'La fecha de vencimiento debe ser posterior a la fecha de préstamo.',
        ]);

        DB::beginTransaction();
        try {
            // Verificar disponibilidad en inventario
            $inventario = Inventario::where('almacen_id', $validated['almacen_id'])
                ->where('item_id', $validated['item_id'])
                ->first();

            if (!$inventario || $inventario->cantidad_disponible < 1) {
                return response()->json([
                    'message' => 'No hay stock disponible del equipo en el almacén seleccionado.'
                ], 422);
            }

            // Generar código
            $validated['codigo'] = Prestamo::generarCodigo();
            $validated['autorizado_por'] = auth()->id();
            $validated['estado'] = 'activo';

            // Crear préstamo
            $prestamo = Prestamo::create($validated);

            // Registrar salida en inventario
            MovimientoInventario::registrarSalida([
                'item_id' => $validated['item_id'],
                'cantidad' => 1,
                'unidad_medida' => 'Unidad',
                'almacen_id' => $validated['almacen_id'],
                'responsable_id' => auth()->id(),
                'motivo' => "Préstamo {$prestamo->codigo} a {$prestamo->custodio->nombre_completo}",
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Préstamo creado exitosamente.',
                'data' => $prestamo->load(['item', 'custodio', 'almacen', 'laboratorio'])
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al crear préstamo: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * Ver detalle de préstamo
     */
    public function show($id)
    {
        $prestamo = Prestamo::with([
            'item',
            'custodio',
            'autorizadoPor',
            'recibidoPor',
            'almacen',
            'laboratorio'
        ])->findOrFail($id);

        // Agregar días vencidos si aplica
        if ($prestamo->estado === 'vencido') {
            $prestamo->dias_vencidos = $prestamo->dias_vencidos;
        }

        return response()->json($prestamo);
    }

    /**
     * Actualizar préstamo (solo activos)
     */
    public function update(Request $request, $id)
    {
        $prestamo = Prestamo::findOrFail($id);

        if ($prestamo->estado !== 'activo') {
            return response()->json([
                'message' => 'Solo se pueden editar préstamos activos.'
            ], 422);
        }

        $validated = $request->validate([
            'fecha_vencimiento' => 'required|date|after:fecha_prestamo',
            'observaciones_prestamo' => 'nullable|string',
        ]);

        $prestamo->update($validated);

        return response()->json([
            'message' => 'Préstamo actualizado exitosamente.',
            'data' => $prestamo
        ]);
    }

    /**
     * Eliminar préstamo (soft delete)
     */
    public function destroy($id)
    {
        $prestamo = Prestamo::findOrFail($id);

        if ($prestamo->estado !== 'activo') {
            return response()->json([
                'message' => 'Solo se pueden eliminar préstamos activos.'
            ], 422);
        }

        $prestamo->delete();

        return response()->json([
            'message' => 'Préstamo eliminado exitosamente.'
        ]);
    }

    /**
     * Préstamos activos
     */
    public function activos(Request $request)
    {
        $prestamos = Prestamo::with(['item', 'custodio', 'laboratorio'])
            ->activos()
            ->orderBy('fecha_vencimiento', 'asc')
            ->paginate($request->get('per_page', 15));

        return response()->json($prestamos);
    }

    /**
     * Préstamos vencidos
     */
    public function vencidos(Request $request)
    {
        $prestamos = Prestamo::with(['item', 'custodio', 'laboratorio'])
            ->vencidos()
            ->orderBy('fecha_vencimiento', 'asc')
            ->get()
            ->map(function ($prestamo) {
                $prestamo->dias_vencidos = $prestamo->dias_vencidos;
                return $prestamo;
            });

        return response()->json($prestamos);
    }

    /**
     * Devoluciones programadas para hoy
     */
    public function devolucionesHoy()
    {
        $prestamos = Prestamo::with(['item', 'custodio', 'laboratorio'])
            ->vencenHoy()
            ->get();

        return response()->json($prestamos);
    }

    /**
     * Historial completo
     */
    public function historial(Request $request)
    {
        $prestamos = Prestamo::with(['item', 'custodio', 'recibidoPor'])
            ->devueltos()
            ->orderBy('fecha_devolucion_real', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json($prestamos);
    }

    /**
     * Devolver préstamo
     */
    public function devolver(Request $request, $id)
    {
        $prestamo = Prestamo::findOrFail($id);

        if (!in_array($prestamo->estado, ['activo', 'vencido'])) {
            return response()->json([
                'message' => 'El préstamo ya fue devuelto o cancelado.'
            ], 422);
        }

        $validated = $request->validate([
            'estado_equipo_devolucion' => 'required|in:bueno,regular,malo',
            'observaciones_devolucion' => 'nullable|string|max:500',
        ]);

        try {
            $prestamo->devolver(
                auth()->id(),
                $validated['estado_equipo_devolucion'],
                $validated['observaciones_devolucion'] ?? null
            );

            return response()->json([
                'message' => 'Préstamo devuelto exitosamente.',
                'data' => $prestamo->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al devolver préstamo: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * Cancelar préstamo
     */
    public function cancelar(Request $request, $id)
    {
        $prestamo = Prestamo::findOrFail($id);

        if ($prestamo->estado !== 'activo') {
            return response()->json([
                'message' => 'Solo se pueden cancelar préstamos activos.'
            ], 422);
        }

        $validated = $request->validate([
            'motivo' => 'required|string|max:500',
        ]);

        try {
            $prestamo->cancelar($validated['motivo']);

            return response()->json([
                'message' => 'Préstamo cancelado exitosamente.',
                'data' => $prestamo->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al cancelar préstamo: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * Enviar recordatorio manual
     */
    public function enviarRecordatorio($id)
    {
        $prestamo = Prestamo::findOrFail($id);

        if (!in_array($prestamo->estado, ['activo', 'vencido'])) {
            return response()->json([
                'message' => 'Solo se pueden enviar recordatorios a préstamos activos o vencidos.'
            ], 422);
        }

        $prestamo->enviarRecordatorio();

        return response()->json([
            'message' => 'Recordatorio enviado exitosamente.'
        ]);
    }

    /**
     * Verificar vencimientos (para cron job)
     */
    public function verificarVencimientos()
    {
        $cantidad = Prestamo::verificarVencimientos();

        return response()->json([
            'message' => "Verificación completada. {$cantidad} préstamos actualizados a vencidos."
        ]);
    }
}
