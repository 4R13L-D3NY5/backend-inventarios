<?php

namespace App\Http\Controllers;

use App\Models\Subcategoria;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class SubcategoriaController extends Controller
{
    /**
     * Listar todas las subcategorías.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Subcategoria::with('categoria');

        // Filtro por categoría
        if ($request->has('categoria_id')) {
            $query->porCategoria($request->categoria_id);
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
        $subcategorias = $query->paginate($perPage);

        return response()->json($subcategorias);
    }

    /**
     * Crear una nueva subcategoría.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'categoria_id' => 'required|exists:categorias,id',
            'nombre' => [
                'required',
                'string',
                'max:100',
                Rule::unique('subcategorias')->where(function ($query) use ($request) {
                    return $query->where('categoria_id', $request->categoria_id);
                })
            ],
            'descripcion' => 'nullable|string',
            'activo' => 'boolean',
        ], [
            'categoria_id.required' => 'La categoría es obligatoria.',
            'categoria_id.exists' => 'La categoría seleccionada no existe.',
            'nombre.required' => 'El nombre de la subcategoría es obligatorio.',
            'nombre.unique' => 'Ya existe una subcategoría con este nombre en la categoría seleccionada.',
        ]);

        $subcategoria = Subcategoria::create($validated);
        $subcategoria->load('categoria');

        return response()->json([
            'message' => 'Subcategoría creada exitosamente.',
            'data' => $subcategoria
        ], 201);
    }

    /**
     * Mostrar una subcategoría específica.
     */
    public function show(int $id): JsonResponse
    {
        $subcategoria = Subcategoria::with(['categoria', 'items'])->find($id);

        if (!$subcategoria) {
            return response()->json([
                'message' => 'Subcategoría no encontrada.'
            ], 404);
        }

        return response()->json([
            'data' => $subcategoria
        ]);
    }

    /**
     * Actualizar una subcategoría existente.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $subcategoria = Subcategoria::find($id);

        if (!$subcategoria) {
            return response()->json([
                'message' => 'Subcategoría no encontrada.'
            ], 404);
        }

        $validated = $request->validate([
            'categoria_id' => 'required|exists:categorias,id',
            'nombre' => [
                'required',
                'string',
                'max:100',
                Rule::unique('subcategorias')->where(function ($query) use ($request) {
                    return $query->where('categoria_id', $request->categoria_id);
                })->ignore($subcategoria->id)
            ],
            'descripcion' => 'nullable|string',
            'activo' => 'boolean',
        ], [
            'categoria_id.required' => 'La categoría es obligatoria.',
            'categoria_id.exists' => 'La categoría seleccionada no existe.',
            'nombre.required' => 'El nombre de la subcategoría es obligatorio.',
            'nombre.unique' => 'Ya existe una subcategoría con este nombre en la categoría seleccionada.',
        ]);

        $subcategoria->update($validated);
        $subcategoria->load('categoria');

        return response()->json([
            'message' => 'Subcategoría actualizada exitosamente.',
            'data' => $subcategoria
        ]);
    }

    /**
     * Eliminar una subcategoría.
     */
    public function destroy(int $id): JsonResponse
    {
        $subcategoria = Subcategoria::find($id);

        if (!$subcategoria) {
            return response()->json([
                'message' => 'Subcategoría no encontrada.'
            ], 404);
        }

        // Verificar si tiene ítems asociados
        if ($subcategoria->items()->count() > 0) {
            return response()->json([
                'message' => 'No se puede eliminar la subcategoría porque tiene ítems asociados.'
            ], 422);
        }

        $subcategoria->delete();

        return response()->json([
            'message' => 'Subcategoría eliminada exitosamente.'
        ]);
    }
}
