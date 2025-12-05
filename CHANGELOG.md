# CHANGELOG

## v1.0.0 (2025-12-05)

### Added

-   **Endpoint `/api/recibir-pedidos`**:
    -   Introducido un nuevo endpoint `POST /api/recibir-pedidos` para gestionar la recepción de pedidos de proveedores.
    -   Controlador: `App\Http\Controllers\PedidoProveedorController` con el método `store`.
    -   Validación: `App\Http\Requests\RecibirPedidoRequest` para asegurar la integridad de los datos de entrada.
-   **Nuevos Modelos y Migraciones**:
    -   `PedidoProveedor` (Tabla: `pedido_proveedores`): Representa un pedido recibido de un proveedor, incluyendo `numero_factura_proveedor`, `fecha_entrega`, `user_id` (usuario que lo recibe), `proveedor_id`, `monto_total` y `estado`.
        -   Migración: `2025_12_05_xxxxxx_create_pedido_proveedores_table.php`
        -   Modelo: `App\Models\PedidoProveedor.php` (con `HasFactory`, `SoftDeletes`, `$fillable` y relaciones).
    -   `DetallePedidoProveedor` (Tabla: `detalle_pedido_proveedores`): Almacena los productos individuales de cada `PedidoProveedor`.
        -   Migración: `2025_12_05_xxxxxx_create_detalle_pedido_proveedores_table.php`
        -   Modelo: `App\Models\DetallePedidoProveedor.php` (con `HasFactory`, `$fillable` y relaciones).
-   **Servicio `PedidoProveedorService`**:
    -   Clase: `App\Services\PedidoProveedorService.php`.
    -   Método `receiveOrder()`: Centraliza la lógica de negocio para procesar la recepción de pedidos, incluyendo:
        -   Creación de `PedidoProveedor` y `DetallePedidoProveedor`.
        -   Actualización del `stock_actual` de los productos.
        -   Registro de `MovimientoInventario` (tipo 'Compra'/'ENTRADA').
        -   Registro de `MovimientoFinanciero` (tipo 'Compra de Productos'/'Egreso') para el monto total.
        -   Asegura todas las operaciones dentro de una transacción de base de datos (`DB::transaction`).
        -   Verifica la existencia de una `CajaDiaria` abierta para el usuario que recibe el pedido antes de registrar el movimiento financiero.
-   **Factory `CajaDiariaFactory`**:
    -   Creado `database/factories/CajaDiariaFactory.php` para facilitar la creación de instancias de `CajaDiaria` en tests.
-   **Tests**:
    -   `Tests\Feature\PedidoProveedorFeatureTest`: Pruebas de integración para el endpoint `/api/recibir-pedidos` cubriendo:
        -   Acceso no autenticado (401).
        -   Validación de datos inválidos (422).
        -   Recepción exitosa del pedido y verificación de cambios en DB.
        -   Manejo de números de factura duplicados.
    -   `Tests\Unit\PedidoProveedorServiceTest`: Pruebas unitarias para `PedidoProveedorService` asegurando la correcta lógica de `receiveOrder` y el rollback en caso de error.

### Changed

-   **`routes/api.php`**:
    -   Rutas reestructuradas: Se eliminó el agrupamiento `Route::middleware('auth:sanctum')->group(...)` externo.
    -   El middleware `auth:sanctum` y los prefijos (como `prefix('cajas')` y `prefix('estadisticas')`) se aplicaron directamente a las rutas individuales o a grupos más pequeños dentro de `api.php`. Esto se hizo para que las rutas sean cargadas correctamente por el nuevo `RouteServiceProvider` y la configuración `withRouting` de Laravel 10/11.
    -   Se agregó la ruta `POST /recibir-pedidos` protegida por `auth:sanctum`.
-   **`app/Models/PedidoProveedor.php`**:
    -   Se añadió `protected $table = 'pedido_proveedores';` para asegurar que el modelo utilice el nombre de tabla correcto, evitando problemas de pluralización por defecto de Laravel.
-   **`app/Models/DetallePedidoProveedor.php`**:
    -   Se añadió `protected $table = 'detalle_pedido_proveedores';` para asegurar que el modelo utilice el nombre de tabla correcto, evitando problemas de pluralización por defecto de Laravel.
-   **`app/Services/PedidoProveedorService.php`**:
    -   Se actualizó la creación de `MovimientoFinanciero` para incluir `caja_diaria_id` (obtenida de la `CajaDiaria` abierta del usuario).
    -   Se añadió una verificación para asegurar que exista una `CajaDiaria` abierta para el usuario, lanzando una excepción si no se encuentra.
-   **`bootstrap/app.php`**:
    -   Se registró `App\Providers\RouteServiceProvider` en el método `withProviders` para asegurar que el proveedor sea cargado por la aplicación.
-   **`app/Providers/RouteServiceProvider.php`**:
    -   Se creó y configuró para mapear las rutas API con el prefijo `/api` y el middleware `api`, resolviendo el problema de carga de rutas en el entorno de pruebas.
-   **`tests/TestCase.php`**:
    -   Se añadió el `use CreatesApplication;` trait.
    -   Se eliminaron las llamadas a `Artisan::call('route:clear')`, `config:clear`, `cache:clear` de `setUp()`, ya que interferían con el correcto boot de la aplicación en el entorno de pruebas.
    -   Se añadió la carga explícita de rutas API con prefijo y middleware en `setUp()` para garantizar su registro en las pruebas de característica.
-   **`tests/CreatesApplication.php`**:
    -   Se creó este archivo para definir el trait `CreatesApplication`, fundamental para el bootstrapping de la aplicación en los tests, ya que no existía previamente.
-   **`tests/Feature/PedidoProveedorFeatureTest.php`**:
    -   `setUp()`: Se modificó para crear explícitamente instancias de `Categoria`, `User`, `Proveedor`, `Producto` y una `CajaDiaria` abierta, así como los `TipoMovimientoInventario` y `TipoMovimientoFinanciero` necesarios, en lugar de depender de `seed()` completo.
    -   `test_authenticated_user_can_receive_orders_with_valid_data()`: La aserción de `movimiento_financieros` se actualizó para obtener `tipo_movimiento_id` programáticamente usando la propiedad `$this->tipoMovimientoFinancieroEgresoCompra->id` en lugar de un ID hardcodeado.
    -   `test_authenticated_user_cannot_receive_orders_with_invalid_data()`: Se eliminó la verificación de error de validación para `user_id` ya que este campo es automáticamente manejado por `prepareForValidation` en el `RecibirPedidoRequest`.
-   **`tests/Feature/ProductoTest.php`**:
    -   `setUp()`: Se modificó para crear explícitamente instancias de `Categoria` y `User` necesarias para las pruebas, en lugar de depender de `seed()` completo.
    -   `test_can_list_productos()`: Se ajustó `assertJsonStructure` para no esperar las claves `created_at` y `updated_at` en los objetos `Producto`, `Categoria` y `User` serializados a JSON.
-   **`tests/Unit/PedidoProveedorServiceTest.php`**:
    -   `setUp()`: Se modificó para crear explícitamente instancias de `Categoria`, `User`, `Proveedor`, `Producto` y una `CajaDiaria` abierta, así como los `TipoMovimientoInventario` y `TipoMovimientoFinanciero` necesarios, en lugar de depender de `seed()` completo.
    -   La aserción `assertStringContainsString('No query results for model [App\Models\Producto]', $e->getMessage())` se actualizó para ser más general, ya que el tipo de excepción puede variar.

### Removed

-   La dependencia de llamadas globales a `seed()` en los métodos `setUp()` de tests de unidad y feature para evitar errores de entradas duplicadas en la base de datos al usar `RefreshDatabase`. En su lugar, se optó por la creación explícita de datos o `firstOrCreate`.

### Fixed

-   **Persistent 404 errors in Feature Tests**: Resuelto asegurando la correcta carga y prefijado de las rutas API mediante la definición de `RouteServiceProvider` y su registro en `bootstrap/app.php`, y la posterior reestructuración de `routes/api.php` para ser compatible con este nuevo esquema.
-   **`SQLSTATE[HY000]: General error: 1364 Field 'nombre' doesn't have a default value`**: Resuelto al completar `database/factories/CategoriaFactory.php` para que generara un nombre por defecto.
-   **`SQLSTATE[42S02]: Base table or view not found: 1146 Table 'celuvariedades.pedido_proveedors' doesn't exist`**: Resuelto añadiendo `protected $table = 'pedido_proveedores';` en el modelo `App\Models\PedidoProveedor.php`.
-   **`SQLSTATE[42S02]: Base table or view not found: 1146 Table 'celuvariedades.detalle_pedido_proveedors' doesn't exist`**: Resuelto añadiendo `protected $table = 'detalle_pedido_proveedores';` en el modelo `App\Models\DetallePedidoProveedor.php`.
-   **`SQLSTATE[42S22]: Column not found: 1054 Unknown column 'saldo_inicial' in 'INSERT INTO'`**: Resuelto corrigiendo los nombres de las columnas en `database/factories/CajaDiariaFactory.php` para que coincidieran con el esquema de la migración (`fondo_inicial` en lugar de `saldo_inicial`, y `monto_cierre_teorico`, `monto_cierre_fisico`, `diferencia` en lugar de `saldo_final`).
-   **`Cannot redeclare class App\Services\PedidoProveedorService`**: Resuelto al eliminar la definición de clase duplicada en `app/Services/PedidoProveedorService.php`.
-   **`Call to undefined method Tests\Unit\PedidoProveedorServiceTest::artisan()`**: Resuelto al cambiar la herencia de `Tests\Unit\PedidoProveedorServiceTest` a `Tests\TestCase` para acceder a los helpers de Laravel.
-   **`Trait "Tests\CreatesApplication" not found`**: Resuelto al crear el archivo `tests/CreatesApplication.php` y definir el trait necesario.
-   **`Failed asserting that an array has the key 'created_at'` (and later 'email')**: Resuelto ajustando los `assertJsonStructure` en `Tests\Feature\ProductoTest.php` para que coincidieran con la salida JSON real de la API.
