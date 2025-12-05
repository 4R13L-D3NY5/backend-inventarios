<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function me(Request $request)
    {
        $user = $request->user()?->load('rol.permisos', 'personal');

        if (!$user) {
            return response()->json([
                'message' => 'Usuario no autenticado. Inicie sesión nuevamente.',
            ], 401);
        }

        // Obtener permisos del rol
        $permissions = $user->rol && $user->rol->permisos
            ? $user->rol->permisos->pluck('clave')->toArray()
            : [];

        return response()->json([
            'user'             => $user,
            'permissions'      => $permissions,
            'password_changed' => (bool) $user->password_changed_at,
        ]);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'usuario'  => 'required|string',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt([
            'usuario'  => $credentials['usuario'],
            'password' => $credentials['password'],
            'estado'   => true, // solo usuarios activos
        ])) {
            return response()->json(['message' => 'Credenciales inválidas'], 401);
        }

        /** @var \App\Models\User $user */
        $user = Auth::user()->load('rol.permisos', 'personal');

        $token = $user->createToken('api')->plainTextToken;

        // Obtener permisos del rol
        $permissions = $user->rol && $user->rol->permisos
            ? $user->rol->permisos->pluck('clave')->toArray()
            : [];

        return response()->json([
            'token'            => $token,
            'user'             => $user,
            'permissions'      => $permissions,
            'password_changed' => (bool) $user->password_changed_at,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Sesión cerrada']);
    }

    public function changePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => 'required|string',
            'password'         => 'required|string|min:6|confirmed',
        ]);

        /** @var \App\Models\User $user */
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Usuario no autenticado. Inicie sesión nuevamente.',
            ], 401);
        }

        if (!Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'message' => 'La contraseña actual es incorrecta',
            ], 422);
        }

        $user->password = Hash::make($validated['password']);
        $user->password_changed_at = now();
        $user->save();

        return response()->json([
            'message'           => 'Contraseña actualizada',
            'user'              => $user,
            'password_changed'  => true,
        ]);
    }
}
