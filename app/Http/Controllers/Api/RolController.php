<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Rol;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RolController extends Controller
{
    public function index()
    {
        // Eager load permissions count
        return Rol::withCount('users')->with('permisos')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre'      => 'required|string|max:255|unique:rols,nombre',
            'descripcion' => 'nullable|string',
            'estado'      => 'boolean',
            'permisos'    => 'array', // Array of permission IDs
            'permisos.*'  => 'exists:permisos,id',
        ]);

        $rol = Rol::create([
            'nombre' => $validated['nombre'],
            'descripcion' => $validated['descripcion'] ?? null,
            'estado' => $validated['estado'] ?? true,
        ]);

        if (isset($validated['permisos'])) {
            $rol->permisos()->sync($validated['permisos']);
        }

        return response()->json([
            'message' => 'Rol creado correctamente',
            'data'    => $rol->load('permisos'),
        ], 201);
    }

    public function show(Rol $rol)
    {
        return $rol->load('permisos');
    }

    public function update(Request $request, Rol $rol)
    {
        $validated = $request->validate([
            'nombre'      => [
                'required',
                'string',
                'max:255',
                Rule::unique('rols', 'nombre')->ignore($rol->id),
            ],
            'descripcion' => 'nullable|string',
            'estado'      => 'boolean',
            'permisos'    => 'array',
            'permisos.*'  => 'exists:permisos,id',
        ]);

        $rol->update([
            'nombre' => $validated['nombre'],
            'descripcion' => $validated['descripcion'] ?? null,
            'estado' => $validated['estado'] ?? true,
        ]);

        if (isset($validated['permisos'])) {
            $rol->permisos()->sync($validated['permisos']);
        }

        return response()->json([
            'message' => 'Rol actualizado correctamente',
            'data'    => $rol->load('permisos'),
        ]);
    }

    public function destroy(Rol $rol)
    {
        // Optional: Check if role has users before deleting
        if ($rol->users()->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar el rol porque tiene usuarios asignados',
            ], 422);
        }

        $rol->delete();

        return response()->json([
            'message' => 'Rol eliminado correctamente',
        ]);
    }
}
