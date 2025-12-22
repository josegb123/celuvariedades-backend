<?php

use App\Http\Controllers\AbonoController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CajaDiariaController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\CuentaPorCobrarController;
use App\Http\Controllers\DetallesNegocioController;
use App\Http\Controllers\EstadisticasController;
use App\Http\Controllers\MovimientoFinancieroController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\TipoMovimientoFinancieroController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VentaController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\PedidoProveedorController;
use App\Http\Controllers\DevolucionController; // Import DevolucionController
use App\Http\Controllers\AvalController; // Import AvalController
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// 1. RUTAS PÚBLICAS (No requieren token)
Route::post('/login', [AuthController::class, 'login']);

// Ajustes de los detalles del negocio
Route::get('/settings/business', [DetallesNegocioController::class, 'show']);

// Rutas autenticadas para ambos roles (admin y vendedor)
Route::middleware('auth:sanctum')->group(function () {
    // Solo si el servidor web no redirige /api/storage automáticamente
    Route::get('/storage/{folder}/{filename}', function ($folder, $filename) {
        $path = storage_path("app/public/{$folder}/{$filename}");

        if (!File::exists($path)) {
            abort(404);
        }

        $file = File::get($path);
        $type = File::mimeType($path);

        $response = Response::make($file, 200);
        $response->header("Content-Type", $type);

        return $response;
    });


    Route::post('/register', [AuthController::class, 'register']);
    // Autenticación - Logout (requiere token para saber qué token revocar)
    Route::post('/logout', [AuthController::class, 'logout']);

    // Obtener Usuario Autenticado
    Route::get('/user', [AuthController::class, 'user']);

    // Gestión de Clientes (CRUD disponible para ambos)
    Route::apiResource('/clientes', ClienteController::class);

    // Rutas para Avales
    Route::get('/avales/{id}/has-pending-dues', [AvalController::class, 'hasPendingDues']);

    // Rutas para Cuentas por Cobrar (Consulta y registro de abonos para ambos)
    Route::get('/cuentas-por-cobrar', [CuentaPorCobrarController::class, 'index']);
    Route::get('/cuentas-por-cobrar/{id}', [CuentaPorCobrarController::class, 'show']);
    Route::post('/abonos', [AbonoController::class, 'store']);

    // Gestión de Productos (Consulta para ambos; CRUD restringido por Form Request)
    Route::get('/productos', [ProductoController::class, 'index']);
    // Gestión de Categorías (CRUD)
    Route::apiResource('/categorias', CategoriaController::class);
    // Ruta específica de bajo stock (debe ir antes de productos/{producto})
    Route::get('/productos/bajo-stock', [ProductoController::class, 'getBajoStock']);

    Route::get('/productos/{producto}', [ProductoController::class, 'show']);

    // Gestión de Ventas (Registro y consulta para ambos; Actualizar/Eliminar restringido por Form Request)
    Route::post('/ventas', [VentaController::class, 'store']);
    Route::get('/ventas', [VentaController::class, 'index']);
    Route::get('/ventas/{venta}', [VentaController::class, 'show']);
    Route::get('/ventas/{venta}/imprimir-pos', [VentaController::class, 'imprimirFacturaPos']);
    Route::get('/estadisticas/productos-bajo-stock', [EstadisticasController::class, 'productosBajoStock']);

    // Rutas de Caja Diaria (Solo obtener activa para ambos; apertura/cierre restringido por Form Request)
    Route::prefix('cajas')->controller(CajaDiariaController::class)->group(function () {
        Route::get('/activa', 'getCajaActiva');
    });

    // Rutas de Caja Diaria (Apertura y Cierre)
    Route::prefix('cajas')->controller(CajaDiariaController::class)->group(function () {
        Route::post('/apertura', 'abrirCaja');
        Route::post('/{cajaDiaria}/cierre', 'cerrarCaja');
    });

    // Gestión Financiera (Accessible a vendedores)
    Route::apiResource('/movimientos-financieros', MovimientoFinancieroController::class);
    Route::apiResource('/tipo-movimientos-financieros', TipoMovimientoFinancieroController::class);

    // Rutas EXCLUSIVAS PARA ADMINISTRADORES
    Route::middleware('admin')->group(function () {
        // Gestión de Usuarios (CRUD Admin-only)
        Route::apiResource('/usuarios', UserController::class);
        Route::put('/usuarios/{id}/restore', [UserController::class, 'restore']);

        // Gestión de Productos (CRUD Admin-only; Consulta ya manejada arriba)
        Route::post('/productos', [ProductoController::class, 'store']);
        Route::put('/productos/{producto}', [ProductoController::class, 'update']);
        Route::delete('/productos/{producto}', [ProductoController::class, 'destroy']);

        // Gestión de Proveedores (CRUD Admin-only)
        Route::apiResource('proveedor', ProveedorController::class);
        Route::apiResource('/pedidos-proveedor', PedidoProveedorController::class);

        // Estadísticas (Admin-only)
        Route::prefix('estadisticas')->group(function () {
            Route::get('/ticket-promedio', [EstadisticasController::class, 'getTicketPromedio']);
            Route::get('/historial-ganancias', [EstadisticasController::class, 'historialGanancias']);

            Route::get('/top-clientes', [EstadisticasController::class, 'topClientes']);
            Route::get('/top-productos', [EstadisticasController::class, 'topProductosVendidos']);
            Route::get('/ventas-por-periodo', [EstadisticasController::class, 'getVentasPorPeriodo']);
            Route::get('/historial-ventas', [EstadisticasController::class, 'historialGanancias']);
            Route::get('/productos-baja-rotacion', [EstadisticasController::class, 'productosBajaRotacion']);
            Route::get('/valor-pedidos-proveedores', [EstadisticasController::class, 'valorPedidosProveedores']);
            Route::get('/top-clientes-frecuencia', [EstadisticasController::class, 'topClientesFrecuencia']);
            Route::get('/ventas-por-categoria', [EstadisticasController::class, 'ventasPorCategoria']);
            Route::get('/exportar-ventas-excel', [EstadisticasController::class, 'exportarVentasExcel']);
            Route::get('/exportar-ventas-pdf', [EstadisticasController::class, 'exportarVentasPdf']);
            Route::get('/cuadre-caja', [EstadisticasController::class, 'cuadreDeCaja']); // New route for Cash Reconciliation
        });

        // Gestión de Devoluciones (Admin-only)
        Route::prefix('devoluciones')->controller(DevolucionController::class)->group(function () {
            Route::post('/', 'store');
            Route::post('/anular-venta/{ventaId}', 'anularVenta');
            Route::get('/pendientes', 'getPendientes');
            Route::put('/{id}/status', 'updateStatus');
            Route::get('/', 'index'); // Added index for dev
            Route::get('/{id}', 'show'); // Added show for dev
        });

        // Venta (actualizar/eliminar solo Admin)
        Route::put('/ventas/{venta}', [VentaController::class, 'update']);
        Route::delete('/ventas/{venta}', [VentaController::class, 'destroy']);

        // Actualizar configuración (Solo Admin)
        Route::put('/settings/business', [DetallesNegocioController::class, 'update']);
        //Route::put('/settings/business', [DetallesNegocioController::class, 'update']);
    });


    /* Route::post('/test', function (Request $request) {


        $request->validate(
            [
                'logo_image' => 'image|max:2048|mimes:jpeg,png,jpg,gif,svg,webp',
            ]
        );
        if ($request->has('logo_image')) {
            $archivo = $request->file('logo_image');
            $ruta = $archivo->store('images', 'public');
            return response()->json(['url' => Storage::url($ruta)], 210);
        } else {
            return response()->json("fallido", 422);
        }
    }); */
});

