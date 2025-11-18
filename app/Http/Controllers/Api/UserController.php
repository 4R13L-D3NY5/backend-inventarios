<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Personal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with(['rol', 'personal']);

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('usuario', 'like', "%$search%")
                  ->orWhere('name', 'like', "%$search%")
                  ->orWhere('email', 'like', "%$search%");
            });
        }

        if ($request->has('rol_id')) {
            $query->where('rol_id', $request->rol_id);
        }

        if ($request->has('estado')) {
            $query->where('estado', $request->estado);
        }

        $sortBy   = $request->sortBy ?? 'created_at';
        $sortDesc = $request->boolean('sortDesc') ? 'desc' : 'asc';

        $query->orderBy($sortBy, $sortDesc);

        $perPage = $request->perPage ?? 10;

        return $query->paginate($perPage);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'usuario'     => 'required|string|max:100|unique:users,usuario',
            'email'       => 'nullable|email|max:255|unique:users,email',
            'password'    => 'required|string|min:6',
            'rol_id'      => 'required|exists:roles,id',
            'personal_id' => 'nullable|exists:personal,id',
            'estado'      => 'boolean',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['estado']   = $validated['estado'] ?? true;

        $user = User::create($validated);
        $user->load('rol', 'personal');

        return response()->json([
            'message' => 'Usuario creado correctamente',
            'data'    => $user,
        ], 201);
    }

    public function show(User $user)
    {
        return $user->load('rol', 'personal');
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'usuario'     => [
                'required',
                'string',
                'max:100',
                Rule::unique('users', 'usuario')->ignore($user->id),
            ],
            'email'       => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'rol_id'      => 'required|exists:roles,id',
            'personal_id' => 'nullable|exists:personal,id',
            'estado'      => 'boolean',
            'password'    => 'nullable|string|min:6',
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);
        $user->load('rol', 'personal');

        return response()->json([
            'message' => 'Usuario actualizado correctamente',
            'data'    => $user,
        ]);
    }

    public function destroy(User $user)
    {
        $user->delete();

        return response()->json([
            'message' => 'Usuario eliminado correctamente',
        ]);
    }

    public function toggleStatus(User $user)
    {
        $user->estado = !$user->estado;
        $user->save();

        return response()->json([
            'message' => 'Estado de usuario actualizado',
            'data'    => $user,
        ]);
    }
}
