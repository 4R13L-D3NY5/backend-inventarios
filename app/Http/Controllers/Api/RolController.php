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
        return Rol::withCount('users')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre'      => 'required|string|max:255|unique:rols,nombre',
            'descripcion' => 'nullable|string',
            'estado'      => 'boolean',
        ]);

        $rol = Rol::create($validated);

        return response()->json([
            'message' => 'Rol creado correctamente',
            'data'    => $rol,
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
        ]);

        $rol->update($validated);

        return response()->json([
            'message' => 'Rol actualizado correctamente',
            'data'    => $rol,
        ]);
    }

    public function destroy(Rol $rol)
    {
        $rol->delete();

        return response()->json([
            'message' => 'Rol eliminado correctamente',
        ]);
    }
}
