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
use App\Http\Controllers\SolicitudController;
use App\Http\Controllers\AlmacenController;
use App\Http\Controllers\InventarioController;
use App\Http\Controllers\MovimientoInventarioController;
use App\Http\Controllers\PrestamoController;
use App\Http\Controllers\OrdenCompraController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\DashboardController;


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

    // Solicitudes
    Route::apiResource('solicitudes', SolicitudController::class);
    
    // Rutas de aprobación de solicitudes
    Route::post('solicitudes/{id}/aprobar-subalmacen', [SolicitudController::class, 'aprobarSubalmacen']);
    Route::post('solicitudes/{id}/aprobar-almacen', [SolicitudController::class, 'aprobarAlmacen']);
    Route::post('solicitudes/{id}/aprobar-adquisicion', [SolicitudController::class, 'aprobarAdquisicion']);
    Route::post('solicitudes/{id}/denegar', [SolicitudController::class, 'denegar']);
    
    // Consultas especiales de solicitudes
    Route::get('solicitudes-pendientes/mis-pendientes', [SolicitudController::class, 'pendientes']);
    Route::get('solicitudes-historial/completadas', [SolicitudController::class, 'historial']);

    // Almacenes
    Route::apiResource('almacenes', AlmacenController::class);
    Route::get('almacenes/{id}/stock', [AlmacenController::class, 'stockPorAlmacen']);
    Route::get('almacenes/{id}/movimientos', [AlmacenController::class, 'movimientos']);

    // Inventario
    Route::get('inventario', [InventarioController::class, 'index']);
    Route::get('inventario/stock-detallado', [InventarioController::class, 'stockDetallado']);
    Route::get('inventario/por-categoria', [InventarioController::class, 'stockPorCategoria']);
    Route::get('inventario/bajo-stock', [InventarioController::class, 'itemsBajoStock']);
    Route::get('inventario/valoracion', [InventarioController::class, 'valoracionInventario']);
    Route::get('inventario/kardex/{itemId}', [InventarioController::class, 'kardex']);

    // Movimientos de Inventario
    Route::get('movimientos-inventario', [MovimientoInventarioController::class, 'index']);
    Route::get('movimientos-inventario/{id}', [MovimientoInventarioController::class, 'show']);
    Route::post('movimientos-inventario/entrada', [MovimientoInventarioController::class, 'registrarEntrada']);
    Route::post('movimientos-inventario/salida', [MovimientoInventarioController::class, 'registrarSalida']);
    Route::post('movimientos-inventario/traspaso', [MovimientoInventarioController::class, 'registrarTraspaso']);
    Route::post('movimientos-inventario/ajuste', [MovimientoInventarioController::class, 'registrarAjuste']);

    // Órdenes de Compra
    Route::apiResource('ordenes-compra', OrdenCompraController::class);
    Route::get('ordenes-compra/estado/{estado}', [OrdenCompraController::class, 'porEstado']);
    Route::get('ordenes-compra/pendientes/recepcion', [OrdenCompraController::class, 'pendientesRecepcion']);
    Route::get('ordenes-compra/historial/completas', [OrdenCompraController::class, 'historial']);
    Route::post('ordenes-compra/{id}/aprobar', [OrdenCompraController::class, 'aprobar']);
    Route::post('ordenes-compra/{id}/confirmar', [OrdenCompraController::class, 'confirmar']);
    Route::post('ordenes-compra/{id}/recepcion', [OrdenCompraController::class, 'registrarRecepcion']);
    Route::post('ordenes-compra/{id}/cancelar', [OrdenCompraController::class, 'cancelar']);

    // Préstamos

    Route::apiResource('prestamos', PrestamoController::class);
    Route::get('prestamos/activos/lista', [PrestamoController::class, 'activos']);
    Route::get('prestamos/vencidos/lista', [PrestamoController::class, 'vencidos']);
    Route::get('prestamos/devoluciones/hoy', [PrestamoController::class, 'devolucionesHoy']);
    Route::get('prestamos/historial/completo', [PrestamoController::class, 'historial']);
    Route::post('prestamos/{id}/devolver', [PrestamoController::class, 'devolver']);
    Route::post('prestamos/{id}/cancelar', [PrestamoController::class, 'cancelar']);
    Route::post('prestamos/{id}/recordatorio', [PrestamoController::class, 'enviarRecordatorio']);
    Route::post('prestamos/verificar-vencimientos', [PrestamoController::class, 'verificarVencimientos']);

    // Reportes
    Route::prefix('reportes')->group(function () {
        Route::get('/inventario-valorizado', [ReporteController::class, 'inventarioValorizado']);
        Route::get('/ordenes-compra-estado', [ReporteController::class, 'ordenesCompraPorEstado']);
        Route::get('/consumo-laboratorio', [ReporteController::class, 'consumoPorLaboratorio']);
        Route::get('/estado-prestamos', [ReporteController::class, 'estadoPrestamos']);
        Route::get('/inversion-inventario', [ReporteController::class, 'inversionInventario']);
    });

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);
});
