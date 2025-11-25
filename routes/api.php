<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CarteraController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\FacturaController;
use App\Http\Controllers\MovimientoFinancieroController;
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
    Route::apiResource('/categorias', CategoriaController::class);
    Route::apiResource('/facturas', FacturaController::class);
    Route::apiResource('/productos', ProductoController::class);
    Route::apiResource('/usuarios', UserController::class);
    Route::put('/usuarios/{id}/restore', [UserController::class, 'restore']);
    Route::post('productos/{producto}', [ProductoController::class, 'update']);

});
