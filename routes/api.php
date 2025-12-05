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
use Illuminate\Support\Facades\Route;

// 1. RUTAS PÚBLICAS (No requieren token)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// 2. RUTAS PROTEGIDAS (Requieren Token Sanctum)
Route::middleware('auth:sanctum')->group(function () {

    // Autenticación
    Route::post('/logout', [AuthController::class, 'logout']);

    // Gestión de Usuarios
    Route::apiResource('/usuarios', UserController::class);
    Route::put('/usuarios/{id}/restore', [UserController::class, 'restore']);

    // Gestión de Clientes
    Route::apiResource('/clientes', ClienteController::class);
    // Rutas para Cartera (asociadas a clientes)
    Route::apiResource('/carteras', CarteraController::class)->only([
        'store',
        'show',
        'update',
    ]);
    // Rutas para Cuentas por Cobrar
    Route::get('/cuentas-por-cobrar', [CuentaPorCobrarController::class, 'index']);
    Route::get('/cuentas-por-cobrar/{id}', [CuentaPorCobrarController::class, 'show']);
    // Rutas para Abonos
    Route::post('/abonos', [AbonoController::class, 'store']);

    // Gestión de Productos y Categorías
    Route::apiResource('/categorias', CategoriaController::class);
    Route::get('/productos/bajo-stock', [ProductoController::class, 'getBajoStock']);
    Route::apiResource('/productos', ProductoController::class);

    // Gestión de Proveedores
    Route::apiResource('proveedor', ProveedorController::class);

    // Gestión de Ventas y Facturación
    Route::apiResource('/ventas', VentaController::class);
    Route::apiResource('/facturas', FacturaController::class);

    // Gestión Financiera
    Route::apiResource('/movimientos-financieros', MovimientoFinancieroController::class);
    Route::apiResource('/tipo-movimientos-financieros', TipoMovimientoFinancieroController::class);

    // Rutas de Caja Diaria
    Route::prefix('cajas')->controller(CajaDiariaController::class)->group(function () {
        Route::get('/activa', 'getCajaActiva'); // [GET] /api/cajas/activa: Obtiene la sesión abierta actual del usuario
        Route::post('/apertura', 'abrirCaja'); // [POST] /api/cajas/apertura: Abre una nueva sesión de caja
        Route::post('/{cajaDiaria}/cierre', 'cerrarCaja'); // [POST] /api/cajas/{cajaDiaria}/cierre: Cierra la sesión específica por ID
    });

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
        Route::get('/historial-ventas', [EstadisticasController::class, 'historialGanancias']); // Ya se manejaba arriba. Duplicado.
    });
});

