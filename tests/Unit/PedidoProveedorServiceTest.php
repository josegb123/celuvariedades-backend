<?php

namespace Tests\Unit;

use App\Models\DetallePedidoProveedor;
use App\Models\MovimientoFinanciero;
use App\Models\MovimientoInventario;
use App\Models\PedidoProveedor;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\TipoMovimientoFinanciero;
use App\Models\TipoMovimientoInventario;
use App\Models\User;
use App\Services\PedidoProveedorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class PedidoProveedorServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PedidoProveedorService $service;
    protected User $user;
    protected Proveedor $proveedor;
    protected Producto $producto1;
    protected Producto $producto2;
    protected TipoMovimientoInventario $tipoMovimientoInventarioCompra;
    protected TipoMovimientoFinanciero $tipoMovimientoFinancieroEgresoCompra;

    protected function setUp(): void
    {
        parent::setUp();
        // Mimic Laravel's RefreshDatabase trait
        $this->artisan('migrate:fresh');
        // $this->seed(); // Seed default data

        $this->service = new PedidoProveedorService();

        // Ensure a category exists for products
        \App\Models\Categoria::factory()->create();

        $this->user = User::factory()->create();
        // Create an active CajaDiaria for the user
        \App\Models\CajaDiaria::factory()->create(['user_id' => $this->user->id, 'estado' => 'abierta']);
        $this->proveedor = Proveedor::factory()->create();
        $this->producto1 = Producto::factory()->create(['stock_actual' => 10, 'precio_compra' => 50]);
        $this->producto2 = Producto::factory()->create(['stock_actual' => 20, 'precio_compra' => 100]);

        // Ensure these types exist or are created for the service logic
        $this->tipoMovimientoInventarioCompra = TipoMovimientoInventario::firstOrCreate(
            ['nombre' => 'Compra'],
            ['tipo_operacion' => 'ENTRADA']
        );
        $this->tipoMovimientoFinancieroEgresoCompra = TipoMovimientoFinanciero::firstOrCreate(
            ['nombre' => 'Compra de Productos'],
            ['descripcion' => 'Salida de dinero para adquirir mercancía (Costo de Venta).', 'tipo' => 'Egreso']
        );
    }

    public function test_receive_order_creates_records_and_updates_stock_successfully(): void
    {
        $initialStock1 = $this->producto1->stock_actual;
        $initialStock2 = $this->producto2->stock_actual;

        $data = [
            'numero_factura_proveedor' => 'INV-TEST-001',
            'fecha_entrega' => '2023-01-01',
            'user_id' => $this->user->id,
            'proveedor_id' => $this->proveedor->id,
            'monto_total' => 550.00,
            'productos' => [
                [
                    'producto_id' => $this->producto1->id,
                    'cantidad' => 5,
                    'precio_compra' => 50.00,
                ],
                [
                    'producto_id' => $this->producto2->id,
                    'cantidad' => 3,
                    'precio_compra' => 100.00,
                ],
            ],
            'metodo_pago' => 'efectivo',
        ];

        $pedido = $this->service->receiveOrder($data);

        $this->assertInstanceOf(PedidoProveedor::class, $pedido);
        $this->assertEquals($data['numero_factura_proveedor'], $pedido->numero_factura_proveedor);
        $this->assertEquals($data['monto_total'], $pedido->monto_total);

        // Assert DetallePedidoProveedor created
        $this->assertCount(2, $pedido->detalles);
        $this->assertDatabaseHas('detalle_pedido_proveedores', [
            'pedido_proveedor_id' => $pedido->id,
            'producto_id' => $this->producto1->id,
            'cantidad' => 5,
        ]);

        // Assert stock updated
        $this->assertEquals($initialStock1 + 5, $this->producto1->fresh()->stock_actual);
        $this->assertEquals($initialStock2 + 3, $this->producto2->fresh()->stock_actual);

        // Assert MovimientoInventario created
        $this->assertDatabaseHas('movimiento_inventarios', [
            'producto_id' => $this->producto1->id,
            'tipo_movimiento_id' => $this->tipoMovimientoInventarioCompra->id,
            'cantidad' => 5,
            'referencia_tabla' => 'pedido_proveedores',
            'referencia_id' => $pedido->id,
        ]);

        // Assert MovimientoFinanciero created
        $this->assertDatabaseHas('movimiento_financieros', [
            'tipo_movimiento_id' => $this->tipoMovimientoFinancieroEgresoCompra->id,
            'monto' => $data['monto_total'],
            'tipo' => 'Egreso',
            'referencia_tabla' => 'pedido_proveedores',
            'referencia_id' => $pedido->id,
        ]);
    }

    public function test_receive_order_rolls_back_on_exception(): void
    {
        DB::beginTransaction(); // Start a transaction for this test case
        $data = [
            'numero_factura_proveedor' => 'INV-ERROR',
            'fecha_entrega' => '2023-01-01',
            'user_id' => $this->user->id,
            'proveedor_id' => $this->proveedor->id,
            'monto_total' => 100.00,
            'productos' => [
                [
                    'producto_id' => 99999, // Non-existent product to cause an error
                    'cantidad' => 1,
                    'precio_compra' => 100.00,
                ],
            ],
        ];

        Log::shouldReceive('error')->once(); // Expect an error to be logged

        try {
            $this->service->receiveOrder($data);
        } catch (\Exception $e) {
            $this->assertStringContainsString('No query results for model [App\\Models\\Producto]', $e->getMessage());
        }

        // Assert no records were created due to rollback
        $this->assertDatabaseMissing('pedido_proveedores', ['numero_factura_proveedor' => 'INV-ERROR']);
        $this->assertDatabaseCount('detalle_pedido_proveedores', 0);
        $this->assertDatabaseCount('movimiento_inventarios', 0);
        $this->assertDatabaseCount('movimiento_financieros', 0);

        DB::rollBack(); // Ensure the test transaction is rolled back
    }
}
