<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class CategoriaController extends Controller
{
    /**
     * Listar todas las categorías.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Categoria::query();

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
        $categorias = $query->paginate($perPage);

        return response()->json($categorias);
    }

    /**
     * Crear una nueva categoría.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:100|unique:categorias,nombre',
            'descripcion' => 'nullable|string',
            'activo' => 'boolean',
        ], [
            'nombre.required' => 'El nombre de la categoría es obligatorio.',
            'nombre.unique' => 'Ya existe una categoría con este nombre.',
        ]);

        $categoria = Categoria::create($validated);

        return response()->json([
            'message' => 'Categoría creada exitosamente.',
            'data' => $categoria
        ], 201);
    }

    /**
     * Mostrar una categoría específica.
     */
    public function show(int $id): JsonResponse
    {
        $categoria = Categoria::with(['subcategorias', 'items'])->find($id);

        if (!$categoria) {
            return response()->json([
                'message' => 'Categoría no encontrada.'
            ], 404);
        }

        return response()->json([
            'data' => $categoria
        ]);
    }

    /**
     * Actualizar una categoría existente.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $categoria = Categoria::find($id);

        if (!$categoria) {
            return response()->json([
                'message' => 'Categoría no encontrada.'
            ], 404);
        }

        $validated = $request->validate([
            'nombre' => [
                'required',
                'string',
                'max:100',
                Rule::unique('categorias', 'nombre')->ignore($categoria->id)
            ],
            'descripcion' => 'nullable|string',
            'activo' => 'boolean',
        ], [
            'nombre.required' => 'El nombre de la categoría es obligatorio.',
            'nombre.unique' => 'Ya existe una categoría con este nombre.',
        ]);

        $categoria->update($validated);

        return response()->json([
            'message' => 'Categoría actualizada exitosamente.',
            'data' => $categoria
        ]);
    }

    /**
     * Eliminar una categoría.
     */
    public function destroy(int $id): JsonResponse
    {
        $categoria = Categoria::find($id);

        if (!$categoria) {
            return response()->json([
                'message' => 'Categoría no encontrada.'
            ], 404);
        }

        // Verificar si tiene ítems asociados
        if ($categoria->items()->count() > 0) {
            return response()->json([
                'message' => 'No se puede eliminar la categoría porque tiene ítems asociados.'
            ], 422);
        }

        $categoria->delete();

        return response()->json([
            'message' => 'Categoría eliminada exitosamente.'
        ]);
    }
}
