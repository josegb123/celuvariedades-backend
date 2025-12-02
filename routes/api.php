<?php

use App\Http\Controllers\AbonoController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CarteraController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\CuentaPorCobrarController;
use App\Http\Controllers\EstadisticasController;
use App\Http\Controllers\FacturaController;
use App\Http\Controllers\MovimientoFinancieroController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\TipoMovimientoFinancieroController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VentaController;
use App\Http\Controllers\ProductoController;
use Illuminate\Http\Request; // Cambiado de Illuminate\Http\Client\Request
use Illuminate\Support\Facades\Route;

// 1. RUTAS PÚBLICAS (No requieren token)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Ruta de Logout (requiere token para saber qué token revocar)
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);

// Ruta de Prueba para obtener el usuario autenticado
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// 2. RUTAS PROTEGIDAS (Requieren Token)
Route::middleware('auth:sanctum')->group(function () {
    // Aquí se pueden agregar rutas protegidas adicionales
    Route::apiResource('/clientes', ClienteController::class);
    Route::apiResource('/ventas', VentaController::class);
    Route::apiResource('/carteras', CarteraController::class)->only([
        'store',
        'show',
        'update',
    ]);
    Route::apiResource('/movimientos-financieros', MovimientoFinancieroController::class);
    Route::apiResource('/tipo-movimientos-financieros', TipoMovimientoFinancieroController::class);
    Route::apiResource('/categorias', CategoriaController::class);
    Route::apiResource('/facturas', FacturaController::class);
    Route::apiResource('/productos', ProductoController::class);
    Route::apiResource('/usuarios', UserController::class);
    Route::put('/usuarios/{id}/restore', [UserController::class, 'restore']);
    Route::post('/productos/{producto}', [ProductoController::class, 'update']);
    Route::apiResource('proveedor', ProveedorController::class);

    Route::get('/cuentas-por-cobrar', [CuentaPorCobrarController::class, 'index']);
    Route::get('/cuentas-por-cobrar/{id}', [CuentaPorCobrarController::class, 'show']);
    Route::post('/abonos', [AbonoController::class, 'store']);

    // Estadísticas



    Route::prefix('estadisticas')->group(function () {
        // Métricas Clave
        Route::get('/ticket-promedio', [EstadisticasController::class, 'getTicketPromedio']);
        Route::get('/historial-ganancias', [EstadisticasController::class, 'historialGanancias']);
        Route::get('/productos-bajo-stock', [EstadisticasController::class, 'productosBajoStock']);

        // Rankings
        Route::get('/top-clientes', [EstadisticasController::class, 'TopClientes']);
        Route::get('/top-productos', [EstadisticasController::class, 'TopProductos']);

        // Series de Tiempo
        Route::get('/ventas-por-periodo', [EstadisticasController::class, 'getVentasPorPeriodo']);
        Route::get('/estadisticas/historial-ventas', [EstadisticasController::class, 'historialGanancias']);
    });
});
