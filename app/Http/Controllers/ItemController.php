<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\ItemUnidad;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class ItemController extends Controller
{
    /**
     * Listar todos los ítems.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Item::with(['categoria', 'subcategoria']);

        // Filtro por categoría
        if ($request->has('categoria_id')) {
            $query->porCategoria($request->categoria_id);
        }

        // Filtro por subcategoría
        if ($request->has('subcategoria_id')) {
            $query->where('subcategoria_id', $request->subcategoria_id);
        }

        // Filtro por tipo (consumible/equipo)
        if ($request->has('es_consumible')) {
            if ($request->boolean('es_consumible')) {
                $query->consumibles();
            } else {
                $query->equipos();
            }
        }

        // Filtro de búsqueda
        if ($request->has('buscar') && $request->buscar != '') {
            $query->buscar($request->buscar);
        }

        // Filtro por estado activo
        if ($request->has('activo')) {
            $query->where('activo', $request->boolean('activo'));
        }

        // Ordenamiento
        $sortBy = $request->get('sort_by', 'nombre');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginación
        $perPage = $request->get('per_page', 15);
        $items = $query->paginate($perPage);

        return response()->json($items);
    }

    /**
     * Crear un nuevo ítem.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'codigo' => 'required|string|max:100|unique:items,codigo',
            'nombre' => 'required|string|max:255',
            'categoria_id' => 'required|exists:categorias,id',
            'subcategoria_id' => 'nullable|exists:subcategorias,id',
            'marca' => 'nullable|string|max:100',
            'modelo' => 'nullable|string|max:100',
            'unidad_medida_base' => 'required|string|max:20',
            'es_consumible' => 'boolean',
            'es_peligroso' => 'boolean',
            'descripcion' => 'nullable|string',
            'activo' => 'boolean',
            // Unidades de conversión (opcional)
            'unidades' => 'nullable|array',
            'unidades.*.unidad_medida' => 'required|string|max:20',
            'unidades.*.factor_a_base' => 'required|numeric|min:0.0001',
        ], [
            'codigo.required' => 'El código del ítem es obligatorio.',
            'codigo.unique' => 'Ya existe un ítem con este código.',
            'nombre.required' => 'El nombre del ítem es obligatorio.',
            'categoria_id.required' => 'La categoría es obligatoria.',
            'categoria_id.exists' => 'La categoría seleccionada no existe.',
            'subcategoria_id.exists' => 'La subcategoría seleccionada no existe.',
            'unidad_medida_base.required' => 'La unidad de medida base es obligatoria.',
        ]);

        DB::beginTransaction();
        try {
            // Crear el ítem
            $unidades = $validated['unidades'] ?? [];
            unset($validated['unidades']);
            
            $item = Item::create($validated);

            // Crear unidades de conversión si existen
            if (!empty($unidades)) {
                foreach ($unidades as $unidad) {
                    ItemUnidad::create([
                        'item_id' => $item->id,
                        'unidad_medida' => $unidad['unidad_medida'],
                        'factor_a_base' => $unidad['factor_a_base'],
                    ]);
                }
            }

            DB::commit();

            $item->load(['categoria', 'subcategoria', 'unidades']);

            return response()->json([
                'message' => 'Ítem creado exitosamente.',
                'data' => $item
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al crear el ítem.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar un ítem específico.
     */
    public function show(int $id): JsonResponse
    {
        $item = Item::with(['categoria', 'subcategoria', 'unidades', 'historialPrecios.proveedor'])->find($id);

        if (!$item) {
            return response()->json([
                'message' => 'Ítem no encontrado.'
            ], 404);
        }

        return response()->json([
            'data' => $item
        ]);
    }

    /**
     * Actualizar un ítem existente.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $item = Item::find($id);

        if (!$item) {
            return response()->json([
                'message' => 'Ítem no encontrado.'
            ], 404);
        }

        $validated = $request->validate([
            'codigo' => [
                'required',
                'string',
                'max:100',
                Rule::unique('items', 'codigo')->ignore($item->id)
            ],
            'nombre' => 'required|string|max:255',
            'categoria_id' => 'required|exists:categorias,id',
            'subcategoria_id' => 'nullable|exists:subcategorias,id',
            'marca' => 'nullable|string|max:100',
            'modelo' => 'nullable|string|max:100',
            'unidad_medida_base' => 'required|string|max:20',
            'es_consumible' => 'boolean',
            'es_peligroso' => 'boolean',
            'descripcion' => 'nullable|string',
            'activo' => 'boolean',
        ], [
            'codigo.required' => 'El código del ítem es obligatorio.',
            'codigo.unique' => 'Ya existe un ítem con este código.',
            'nombre.required' => 'El nombre del ítem es obligatorio.',
            'categoria_id.required' => 'La categoría es obligatoria.',
            'categoria_id.exists' => 'La categoría seleccionada no existe.',
            'subcategoria_id.exists' => 'La subcategoría seleccionada no existe.',
            'unidad_medida_base.required' => 'La unidad de medida base es obligatoria.',
        ]);

        $item->update($validated);
        $item->load(['categoria', 'subcategoria', 'unidades']);

        return response()->json([
            'message' => 'Ítem actualizado exitosamente.',
            'data' => $item
        ]);
    }

    /**
     * Eliminar un ítem (soft delete).
     */
    public function destroy(int $id): JsonResponse
    {
        $item = Item::find($id);

        if (!$item) {
            return response()->json([
                'message' => 'Ítem no encontrado.'
            ], 404);
        }

        $item->delete();

        return response()->json([
            'message' => 'Ítem eliminado exitosamente.'
        ]);
    }
}
