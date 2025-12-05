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
            'name'        => 'nullable|string|max:255',
            'usuario'     => 'required|string|max:100|unique:users,usuario',
            'email'       => 'nullable|email|max:255|unique:users,email',
            'password'    => 'required|string|min:6',
            'rol_id'      => 'required|exists:rols,id',
            'estado'      => 'boolean',
            // Datos de Personal
            'personal.nombres'   => 'required|string|max:255',
            'personal.apellidos' => 'required|string|max:255',
            'personal.ci'        => 'required|string|unique:personals,ci',
            'personal.celular'   => 'nullable|string|max:20',
        ]);

        // Crear el registro de Personal primero
        $personal = Personal::create([
            'nombres'   => $validated['personal']['nombres'],
            'apellidos' => $validated['personal']['apellidos'],
            'ci'        => $validated['personal']['ci'],
            'celular'   => $validated['personal']['celular'] ?? null,
            'estado'    => true,
        ]);

        // Crear el usuario con el personal_id
        $validated['password'] = Hash::make($validated['password']);
        $validated['estado']   = $validated['estado'] ?? true;
        $validated['personal_id'] = $personal->id;

        // Remover los datos de personal del array validated
        unset($validated['personal']);

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
            'name'        => 'nullable|string|max:255',
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
            'rol_id'      => 'required|exists:rols,id',
            'estado'      => 'boolean',
            'password'    => 'nullable|string|min:6',
            // Datos de Personal
            'personal.nombres'   => 'required|string|max:255',
            'personal.apellidos' => 'required|string|max:255',
            'personal.ci'        => [
                'required',
                'string',
                Rule::unique('personals', 'ci')->ignore($user->personal_id),
            ],
            'personal.celular'   => 'nullable|string|max:20',
        ]);

        // Actualizar o crear Personal
        if ($user->personal_id) {
            // Actualizar Personal existente
            $user->personal->update([
                'nombres'   => $validated['personal']['nombres'],
                'apellidos' => $validated['personal']['apellidos'],
                'ci'        => $validated['personal']['ci'],
                'celular'   => $validated['personal']['celular'] ?? null,
            ]);
        } else {
            // Crear nuevo Personal
            $personal = Personal::create([
                'nombres'   => $validated['personal']['nombres'],
                'apellidos' => $validated['personal']['apellidos'],
                'ci'        => $validated['personal']['ci'],
                'celular'   => $validated['personal']['celular'] ?? null,
                'estado'    => true,
            ]);
            $validated['personal_id'] = $personal->id;
        }

        // Remover los datos de personal del array validated
        unset($validated['personal']);

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

    public function resetPassword(User $user)
    {
        // Resetear contraseña a: {usuario}123
        $newPassword = $user->usuario . '123';
        $user->password = Hash::make($newPassword);
        $user->password_changed_at = null; // Forzar cambio de contraseña en próximo login
        $user->save();

        return response()->json([
            'message' => 'Contraseña reseteada correctamente',
            'new_password' => $newPassword,
        ]);
    }
}
