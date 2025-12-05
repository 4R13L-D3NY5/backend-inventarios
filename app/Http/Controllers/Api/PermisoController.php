<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Permiso;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PermisoController extends Controller
{
    public function index(Request $request)
    {
        $query = Permiso::query();

        if ($request->has('rol_id')) {
            $query->whereHas('rols', function ($q) use ($request) {
                $q->where('rols.id', $request->rol_id);
            });
        }

        if ($request->has('grupo')) {
            $query->where('grupo', $request->grupo);
        }

        if ($request->has('estado')) {
            $query->where('estado', $request->estado);
        }

        return $query->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre'      => 'required|string|max:255',
            'clave'       => 'required|string|max:255|unique:permisos,clave',
            'descripcion' => 'nullable|string',
            'grupo'       => 'nullable|string',
            'estado'      => 'boolean',
        ]);

        $permiso = Permiso::create($validated);

        return response()->json([
            'message' => 'Permiso creado correctamente',
            'data'    => $permiso,
        ], 201);
    }

    public function show(Permiso $permiso)
    {
        return $permiso->load('rols');
    }

    public function update(Request $request, Permiso $permiso)
    {
        $validated = $request->validate([
            'nombre'      => 'required|string|max:255',
            'clave'       => [
                'required',
                'string',
                'max:255',
                Rule::unique('permisos', 'clave')->ignore($permiso->id),
            ],
            'descripcion' => 'nullable|string',
            'grupo'       => 'nullable|string',
            'estado'      => 'boolean',
        ]);

        $permiso->update($validated);

        return response()->json([
            'message' => 'Permiso actualizado correctamente',
            'data'    => $permiso,
        ]);
    }

    public function destroy(Permiso $permiso)
    {
        $permiso->delete();

        return response()->json([
            'message' => 'Permiso eliminado correctamente',
        ]);
    }
}
