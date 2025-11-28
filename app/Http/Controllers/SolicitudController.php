<?php

namespace App\Http\Controllers;

use App\Models\Solicitud;
use App\Models\SolicitudItem;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class SolicitudController extends Controller
{
    /**
     * Listar todas las solicitudes con filtros.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Solicitud::with([
            'solicitante',
            'laboratorio',
            'ubicacion',
            'items.item',
            'aprobadorSubalmacen',
            'aprobadorAlmacen',
            'aprobadorAdquisicion'
        ]);

        // Filtro por estado general
        if ($request->has('estado_general')) {
            $query->where('estado_general', $request->estado_general);
        }

        // Filtro por prioridad
        if ($request->has('prioridad')) {
            $query->porPrioridad($request->prioridad);
        }

        // Filtro por tipo
        if ($request->has('tipo')) {
            $query->porTipo($request->tipo);
        }

        // Filtro por laboratorio
        if ($request->has('laboratorio_id')) {
            $query->porLaboratorio($request->laboratorio_id);
        }

        // Filtro por solicitante
        if ($request->has('solicitante_id')) {
            $query->where('solicitante_id', $request->solicitante_id);
        }

        // Filtro por rango de fechas
        if ($request->has('fecha_desde')) {
            $query->where('fecha_solicitud', '>=', $request->fecha_desde);
        }
        if ($request->has('fecha_hasta')) {
            $query->where('fecha_solicitud', '<=', $request->fecha_hasta);
        }

        // Ordenamiento
        $sortBy = $request->get('sort_by', 'fecha_solicitud');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginación
        $perPage = $request->get('per_page', 15);
        $solicitudes = $query->paginate($perPage);

        return response()->json($solicitudes);
    }

    /**
     * Crear una nueva solicitud.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tipo' => 'required|in:reposicion,compra_nueva',
            'prioridad' => 'required|in:baja,media,alta',
            'solicitante_id' => 'required|exists:personals,id',
            'laboratorio_id' => 'required|exists:laboratorios,id',
            'ubicacion_id' => 'nullable|exists:ubicaciones,id',
            'observaciones' => 'nullable|string',
            'fecha_solicitud' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:items,id',
            'items.*.cantidad_solicitada' => 'required|numeric|min:0.01',
            'items.*.unidad_medida' => 'required|string|max:20',
            'items.*.observaciones' => 'nullable|string',
        ], [
            'tipo.required' => 'El tipo de solicitud es obligatorio.',
            'tipo.in' => 'El tipo debe ser reposición o compra nueva.',
            'prioridad.required' => 'La prioridad es obligatoria.',
            'solicitante_id.required' => 'El solicitante es obligatorio.',
            'laboratorio_id.required' => 'El laboratorio es obligatorio.',
            'items.required' => 'Debe incluir al menos un ítem.',
            'items.min' => 'Debe incluir al menos un ítem.',
        ]);

        DB::beginTransaction();
        try {
            // Generar código único
            $codigo = Solicitud::generarCodigo();

            // Crear la solicitud
            $items = $validated['items'];
            unset($validated['items']);
            
            $solicitud = Solicitud::create([
                ...$validated,
                'codigo' => $codigo,
                'estado_general' => 'pendiente_subalmacen',
                'estado_subalmacen' => 'pendiente',
            ]);

            // Crear los ítems de la solicitud
            foreach ($items as $itemData) {
                SolicitudItem::create([
                    'solicitud_id' => $solicitud->id,
                    'item_id' => $itemData['item_id'],
                    'cantidad_solicitada' => $itemData['cantidad_solicitada'],
                    'unidad_medida' => $itemData['unidad_medida'],
                    'observaciones' => $itemData['observaciones'] ?? null,
                ]);
            }

            DB::commit();

            $solicitud->load([
                'solicitante',
                'laboratorio',
                'ubicacion',
                'items.item'
            ]);

            return response()->json([
                'message' => 'Solicitud creada exitosamente.',
                'data' => $solicitud
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al crear la solicitud.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar una solicitud específica.
     */
    public function show(int $id): JsonResponse
    {
        $solicitud = Solicitud::with([
            'solicitante',
            'laboratorio',
            'ubicacion',
            'items.item',
            'aprobadorSubalmacen',
            'aprobadorAlmacen',
            'aprobadorAdquisicion',
            'denegadoPor'
        ])->find($id);

        if (!$solicitud) {
            return response()->json([
                'message' => 'Solicitud no encontrada.'
            ], 404);
        }

        return response()->json([
            'data' => $solicitud
        ]);
    }

    /**
     * Actualizar una solicitud (solo si está pendiente).
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $solicitud = Solicitud::find($id);

        if (!$solicitud) {
            return response()->json([
                'message' => 'Solicitud no encontrada.'
            ], 404);
        }

        // Solo se puede editar si está en estado pendiente_subalmacen
        if ($solicitud->estado_general !== 'pendiente_subalmacen') {
            return response()->json([
                'message' => 'Solo se pueden editar solicitudes pendientes de sub-almacén.'
            ], 403);
        }

        $validated = $request->validate([
            'tipo' => 'required|in:reposicion,compra_nueva',
            'prioridad' => 'required|in:baja,media,alta',
            'observaciones' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:items,id',
            'items.*.cantidad_solicitada' => 'required|numeric|min:0.01',
            'items.*.unidad_medida' => 'required|string|max:20',
            'items.*.observaciones' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Actualizar la solicitud
            $items = $validated['items'];
            unset($validated['items']);
            
            $solicitud->update($validated);

            // Eliminar ítems anteriores y crear los nuevos
            $solicitud->items()->delete();
            
            foreach ($items as $itemData) {
                SolicitudItem::create([
                    'solicitud_id' => $solicitud->id,
                    'item_id' => $itemData['item_id'],
                    'cantidad_solicitada' => $itemData['cantidad_solicitada'],
                    'unidad_medida' => $itemData['unidad_medida'],
                    'observaciones' => $itemData['observaciones'] ?? null,
                ]);
            }

            DB::commit();

            $solicitud->load([
                'solicitante',
                'laboratorio',
                'ubicacion',
                'items.item'
            ]);

            return response()->json([
                'message' => 'Solicitud actualizada exitosamente.',
                'data' => $solicitud
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al actualizar la solicitud.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar una solicitud (soft delete).
     */
    public function destroy(int $id): JsonResponse
    {
        $solicitud = Solicitud::find($id);

        if (!$solicitud) {
            return response()->json([
                'message' => 'Solicitud no encontrada.'
            ], 404);
        }

        // Solo se puede eliminar si está pendiente
        if ($solicitud->estado_general !== 'pendiente_subalmacen') {
            return response()->json([
                'message' => 'Solo se pueden eliminar solicitudes pendientes.'
            ], 403);
        }

        $solicitud->delete();

        return response()->json([
            'message' => 'Solicitud eliminada exitosamente.'
        ]);
    }

    /**
     * Aprobar solicitud en nivel sub-almacén.
     */
    public function aprobarSubalmacen(int $id): JsonResponse
    {
        $solicitud = Solicitud::find($id);

        if (!$solicitud) {
            return response()->json([
                'message' => 'Solicitud no encontrada.'
            ], 404);
        }

        if ($solicitud->estado_general !== 'pendiente_subalmacen') {
            return response()->json([
                'message' => 'La solicitud no está pendiente de aprobación de sub-almacén.'
            ], 400);
        }

        $solicitud->aprobarSubalmacen(Auth::id());
        $solicitud->load(['solicitante', 'laboratorio', 'items.item']);

        return response()->json([
            'message' => 'Solicitud aprobada en sub-almacén exitosamente.',
            'data' => $solicitud
        ]);
    }

    /**
     * Aprobar solicitud en nivel almacén.
     */
    public function aprobarAlmacen(int $id): JsonResponse
    {
        $solicitud = Solicitud::find($id);

        if (!$solicitud) {
            return response()->json([
                'message' => 'Solicitud no encontrada.'
            ], 404);
        }

        if ($solicitud->estado_general !== 'pendiente_almacen') {
            return response()->json([
                'message' => 'La solicitud no está pendiente de aprobación de almacén.'
            ], 400);
        }

        $solicitud->aprobarAlmacen(Auth::id());
        $solicitud->load(['solicitante', 'laboratorio', 'items.item']);

        return response()->json([
            'message' => 'Solicitud aprobada en almacén exitosamente.',
            'data' => $solicitud
        ]);
    }

    /**
     * Aprobar solicitud en nivel adquisición.
     */
    public function aprobarAdquisicion(int $id): JsonResponse
    {
        $solicitud = Solicitud::find($id);

        if (!$solicitud) {
            return response()->json([
                'message' => 'Solicitud no encontrada.'
            ], 404);
        }

        if ($solicitud->estado_general !== 'en_adquisiciones') {
            return response()->json([
                'message' => 'La solicitud no está en adquisiciones.'
            ], 400);
        }

        $solicitud->aprobarAdquisicion(Auth::id());
        $solicitud->load(['solicitante', 'laboratorio', 'items.item']);

        return response()->json([
            'message' => 'Solicitud completada exitosamente.',
            'data' => $solicitud
        ]);
    }

    /**
     * Denegar una solicitud.
     */
    public function denegar(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'motivo_denegacion' => 'required|string|max:500',
        ], [
            'motivo_denegacion.required' => 'Debe proporcionar un motivo de denegación.',
        ]);

        $solicitud = Solicitud::find($id);

        if (!$solicitud) {
            return response()->json([
                'message' => 'Solicitud no encontrada.'
            ], 404);
        }

        // No se puede denegar si ya está completada o denegada
        if (in_array($solicitud->estado_general, ['completada', 'denegada'])) {
            return response()->json([
                'message' => 'No se puede denegar una solicitud completada o ya denegada.'
            ], 400);
        }

        $solicitud->denegar(Auth::id(), $validated['motivo_denegacion']);
        $solicitud->load(['solicitante', 'laboratorio', 'items.item', 'denegadoPor']);

        return response()->json([
            'message' => 'Solicitud denegada exitosamente.',
            'data' => $solicitud
        ]);
    }

    /**
     * Obtener solicitudes pendientes del usuario actual.
     */
    public function pendientes(Request $request): JsonResponse
    {
        // Este método debería filtrar según el rol del usuario
        // Por ahora retorna todas las pendientes
        $query = Solicitud::with([
            'solicitante',
            'laboratorio',
            'items.item'
        ]);

        // Filtrar según el estado que corresponda al usuario
        $estado = $request->get('estado', 'pendiente_subalmacen');
        $query->where('estado_general', $estado);

        $solicitudes = $query->orderBy('prioridad', 'desc')
            ->orderBy('fecha_solicitud', 'asc')
            ->paginate(15);

        return response()->json($solicitudes);
    }

    /**
     * Obtener historial de solicitudes completadas.
     */
    public function historial(Request $request): JsonResponse
    {
        $solicitudes = Solicitud::with([
            'solicitante',
            'laboratorio',
            'items.item'
        ])
        ->completadas()
        ->orderBy('fecha_completada', 'desc')
        ->paginate(15);

        return response()->json($solicitudes);
    }
}
