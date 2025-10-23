<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CarteraController;
use App\Http\Controllers\ClienteController;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Route;

// 1. RUTAS PÚBLICAS (No requieren token)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);



// Ruta de Logout (requiere token para saber qué token revocar)
Route::post('/logout', [AuthController::class, 'logout']);

// Ruta de Prueba para obtener el usuario autenticado
Route::get('/user', function (Request $request) {
    return $request->user();
});

// enpoint de Clientes
Route::apiResource('/clientes', ClienteController::class);

//endpoint de carteras
Route::apiResource('/carteras', CarteraController::class)->only([
    'store',
    'show',
    'edit',
    'update'
]);

// 2. RUTAS PROTEGIDAS (Requieren Token)
Route::middleware('auth:sanctum')->group(function () {
    //insertar las rutas aqui al final

});
