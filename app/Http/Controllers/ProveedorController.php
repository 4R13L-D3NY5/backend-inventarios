<?php

namespace App\Http\Controllers;

use App\Models\Proveedor;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class ProveedorController extends Controller
{
    /**
     * Listar todos los proveedores con paginación y búsqueda.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = Proveedor::with(['historialPrecios.item', 'compras.items.item']);

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
        $proveedores = $query->paginate($perPage);

        return response()->json($proveedores);
    }

    /**
     * Crear un nuevo proveedor.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        // Validación
        $validated = $request->validate([
            'nombre' => 'required|string|max:255|unique:proveedores,nombre',
            'nit' => 'nullable|string|max:50',
            'telefono' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'direccion' => 'required|string',
            'observaciones' => 'nullable|string',
            'activo' => 'boolean',
        ], [
            'nombre.required' => 'El nombre del proveedor es obligatorio.',
            'nombre.unique' => 'Ya existe un proveedor con este nombre.',
            'telefono.required' => 'El teléfono es obligatorio.',
            'email.email' => 'El formato del email no es válido.',
            'direccion.required' => 'La dirección es obligatoria.',
        ]);

        $proveedor = Proveedor::create($validated);

        return response()->json([
            'message' => 'Proveedor creado exitosamente.',
            'data' => $proveedor
        ], 201);
    }

    /**
     * Mostrar un proveedor específico.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $proveedor = Proveedor::find($id);

        if (!$proveedor) {
            return response()->json([
                'message' => 'Proveedor no encontrado.'
            ], 404);
        }

        return response()->json([
            'data' => $proveedor
        ]);
    }

    /**
     * Actualizar un proveedor existente.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $proveedor = Proveedor::find($id);

        if (!$proveedor) {
            return response()->json([
                'message' => 'Proveedor no encontrado.'
            ], 404);
        }

        // Validación
        $validated = $request->validate([
            'nombre' => [
                'required',
                'string',
                'max:255',
                Rule::unique('proveedores', 'nombre')->ignore($proveedor->id)
            ],
            'nit' => 'nullable|string|max:50',
            'telefono' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'direccion' => 'required|string',
            'observaciones' => 'nullable|string',
            'activo' => 'boolean',
        ], [
            'nombre.required' => 'El nombre del proveedor es obligatorio.',
            'nombre.unique' => 'Ya existe un proveedor con este nombre.',
            'telefono.required' => 'El teléfono es obligatorio.',
            'email.email' => 'El formato del email no es válido.',
            'direccion.required' => 'La dirección es obligatoria.',
        ]);

        $proveedor->update($validated);

        return response()->json([
            'message' => 'Proveedor actualizado exitosamente.',
            'data' => $proveedor
        ]);
    }

    /**
     * Eliminar un proveedor (soft delete).
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $proveedor = Proveedor::find($id);

        if (!$proveedor) {
            return response()->json([
                'message' => 'Proveedor no encontrado.'
            ], 404);
        }

        $proveedor->delete();

        return response()->json([
            'message' => 'Proveedor eliminado exitosamente.'
        ]);
    }
}
