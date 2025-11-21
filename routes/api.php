<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\RolController;
use App\Http\Controllers\Api\PermisoController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\SubcategoriaController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\HistorialPrecioController;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);

    // Usuarios
    Route::apiResource('users', UserController::class);
    Route::post('users/{user}/toggle-status', [UserController::class, 'toggleStatus']);

    // Roles
    Route::apiResource('roles', RolController::class);

    // Permisos
    Route::apiResource('permisos', PermisoController::class);

    // Proveedores
    Route::apiResource('proveedores', ProveedorController::class);

    // Categorías
    Route::apiResource('categorias', CategoriaController::class);

    // Subcategorías
    Route::apiResource('subcategorias', SubcategoriaController::class);

    // Ítems
    Route::apiResource('items', ItemController::class);

    // Historial de Precios
    Route::apiResource('historial-precios', HistorialPrecioController::class);
    
    // Rutas especiales para comparativa y tendencia
    Route::get('items/{item}/comparativa-proveedores', [HistorialPrecioController::class, 'comparativa']);
    Route::get('items/{item}/tendencia-precios', [HistorialPrecioController::class, 'tendencia']);
});
