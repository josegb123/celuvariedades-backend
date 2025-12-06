<?php

use App\Http\Controllers\AbonoController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CajaDiariaController;
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
use App\Http\Controllers\PedidoProveedorController;
use App\Http\Controllers\DevolucionController; // Import DevolucionController
use Illuminate\Support\Facades\Route;

// 1. RUTAS PÚBLICAS (No requieren token)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Autenticación - Logout (requiere token para saber qué token revocar)
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

// Obtener Usuario Autenticado
Route::get('/user', [AuthController::class, 'user'])->middleware('auth:sanctum');

// Gestión de Usuarios
Route::apiResource('/usuarios', UserController::class)->middleware('auth:sanctum');
Route::put('/usuarios/{id}/restore', [UserController::class, 'restore'])->middleware('auth:sanctum');

// Gestión de Clientes
Route::apiResource('/clientes', ClienteController::class)->middleware('auth:sanctum');
// Rutas para Cartera (asociadas a clientes)
Route::apiResource('/carteras', CarteraController::class)->only([
    'store',
    'show',
    'update',
])->middleware('auth:sanctum');
// Rutas para Cuentas por Cobrar
Route::get('/cuentas-por-cobrar', [CuentaPorCobrarController::class, 'index'])->middleware('auth:sanctum');
Route::get('/cuentas-por-cobrar/{id}', [CuentaPorCobrarController::class, 'show'])->middleware('auth:sanctum');
// Rutas para Abonos
Route::post('/abonos', [AbonoController::class, 'store'])->middleware('auth:sanctum');

// Gestión de Productos y Categorías
Route::apiResource('/categorias', CategoriaController::class)->middleware('auth:sanctum');
Route::get('/productos/bajo-stock', [ProductoController::class, 'getBajoStock'])->middleware('auth:sanctum');
Route::apiResource('/productos', ProductoController::class)->middleware('auth:sanctum');

// Gestión de Proveedores
Route::apiResource('proveedor', ProveedorController::class)->middleware('auth:sanctum');
Route::post('/recibir-pedidos', [PedidoProveedorController::class, 'store'])->middleware('auth:sanctum');

// Gestión de Ventas y Facturación
Route::apiResource('/ventas', VentaController::class)->middleware('auth:sanctum');
Route::apiResource('/facturas', FacturaController::class)->middleware('auth:sanctum');

// Gestión Financiera
Route::apiResource('/movimientos-financieros', MovimientoFinancieroController::class)->middleware('auth:sanctum');
Route::apiResource('/tipo-movimientos-financieros', TipoMovimientoFinancieroController::class)->middleware('auth:sanctum');

// Rutas de Caja Diaria
Route::prefix('cajas')->middleware('auth:sanctum')->controller(CajaDiariaController::class)->group(function () {
    Route::get('/activa', 'getCajaActiva');
    Route::post('/apertura', 'abrirCaja');
    Route::post('/{cajaDiaria}/cierre', 'cerrarCaja');
});

// Estadísticas
Route::prefix('estadisticas')->middleware('auth:sanctum')->group(function () {
    Route::get('/ticket-promedio', [EstadisticasController::class, 'getTicketPromedio']);
    Route::get('/historial-ganancias', [EstadisticasController::class, 'historialGanancias']);
    Route::get('/productos-bajo-stock', [EstadisticasController::class, 'productosBajoStock']);
    Route::get('/top-clientes', [EstadisticasController::class, 'TopClientes']);
    Route::get('/top-productos', [EstadisticasController::class, 'TopProductos']);
    Route::get('/ventas-por-periodo', [EstadisticasController::class, 'getVentasPorPeriodo']);
    Route::get('/historial-ventas', [EstadisticasController::class, 'historialGanancias']);
});

// Gestión de Devoluciones
Route::prefix('devoluciones')->middleware('auth:sanctum')->controller(DevolucionController::class)->group(function () {
    Route::post('/', 'store'); // POST /api/devoluciones
    Route::get('/pendientes', 'getPendientes'); // GET /api/devoluciones/pendientes
    Route::put('/{id}/status', 'updateStatus'); // PUT /api/devoluciones/{id}/status
});

