<?php

namespace Tests\Feature;

use App\Models\MovimientoFinanciero;
use App\Models\MovimientoInventario;
use App\Models\PedidoProveedor;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\User;
use App\Models\TipoMovimientoFinanciero;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PedidoProveedorFeatureTest extends TestCase
{
    use RefreshDatabase; // Resets the database for each test

    protected User $user;
    protected Proveedor $proveedor;
    protected Producto $producto1;
    protected Producto $producto2;
    protected TipoMovimientoFinanciero $tipoMovimientoFinancieroEgresoCompra;

    protected function setUp(): void
    {
        parent::setUp();
        // $this->seed(); // Removed to prevent duplicate entry errors and handle seeding explicitly

        // Ensure a category exists for products
        \App\Models\Categoria::factory()->create();

        $this->user = User::factory()->create();
        // Create an active CajaDiaria for the user
        \App\Models\CajaDiaria::factory()->create(['user_id' => $this->user->id, 'estado' => 'abierta']);
        $this->proveedor = Proveedor::factory()->create();
        $this->producto1 = Producto::factory()->create(['stock_actual' => 10, 'precio_compra' => 50]);
        $this->producto2 = Producto::factory()->create(['stock_actual' => 20, 'precio_compra' => 100]);

        // Ensure these types exist or are created for the service logic
        \App\Models\TipoMovimientoInventario::firstOrCreate(
            ['nombre' => 'Compra'],
            ['tipo_operacion' => 'ENTRADA']
        );
        $this->tipoMovimientoFinancieroEgresoCompra = \App\Models\TipoMovimientoFinanciero::firstOrCreate(
            ['nombre' => 'Compra de Productos'],
            ['descripcion' => 'Salida de dinero para adquirir mercancía (Costo de Venta).', 'tipo' => 'Egreso']
        );
    }

    public function test_unauthenticated_user_cannot_receive_orders(): void
    {
        $response = $this->postJson('/api/recibir-pedidos', []);
        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_receive_orders_with_valid_data(): void
    {
        Sanctum::actingAs($this->user);

        $payload = [
            'numero_factura_proveedor' => 'INV-001-' . now()->timestamp,
            'fecha_entrega' => now()->toDateString(),
            'user_id' => $this->user->id,
            'proveedor_id' => $this->proveedor->id,
            'monto_total' => 1000.00,
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
        ];

        $response = $this->postJson('/api/recibir-pedidos', $payload);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'pedido' => [
                    'id',
                    'numero_factura_proveedor',
                    'fecha_entrega',
                    'user_id',
                    'proveedor_id',
                    'monto_total',
                    'estado',
                    'created_at',
                    'updated_at',
                    'detalles',
                ],
            ]);

        // Assertions for database changes
        $this->assertDatabaseHas('pedido_proveedores', [
            'numero_factura_proveedor' => $payload['numero_factura_proveedor'],
            'proveedor_id' => $payload['proveedor_id'],
            'monto_total' => $payload['monto_total'],
            'user_id' => $payload['user_id'],
            'estado' => 'recibido',
        ]);

        $pedido = PedidoProveedor::where('numero_factura_proveedor', $payload['numero_factura_proveedor'])->first();
        $this->assertNotNull($pedido);

        $this->assertDatabaseHas('detalle_pedido_proveedores', [
            'pedido_proveedor_id' => $pedido->id,
            'producto_id' => $this->producto1->id,
            'cantidad' => 5,
            'precio_compra' => 50.00,
            'subtotal' => 250.00,
        ]);
        $this->assertDatabaseHas('detalle_pedido_proveedores', [
            'pedido_proveedor_id' => $pedido->id,
            'producto_id' => $this->producto2->id,
            'cantidad' => 3,
            'precio_compra' => 100.00,
            'subtotal' => 300.00,
        ]);

        // Assert product stock updated
        $this->assertEquals(10 + 5, $this->producto1->fresh()->stock_actual);
        $this->assertEquals(20 + 3, $this->producto2->fresh()->stock_actual);

        // Assert inventory movements created
        $this->assertDatabaseHas('movimiento_inventarios', [
            'producto_id' => $this->producto1->id,
            'tipo_movimiento_id' => 2, // Assuming 'Compra' type_movimiento_inventario_id is 2 from seeders
            'cantidad' => 5,
            'referencia_tabla' => 'pedido_proveedores',
            'referencia_id' => $pedido->id,
        ]);
        $this->assertDatabaseHas('movimiento_inventarios', [
            'producto_id' => $this->producto2->id,
            'tipo_movimiento_id' => 2,
            'cantidad' => 3,
            'referencia_tabla' => 'pedido_proveedores',
            'referencia_id' => $pedido->id,
        ]);

        // Assert financial movement created (Egreso)
        $this->assertDatabaseHas('movimiento_financieros', [
            'tipo_movimiento_id' => $this->tipoMovimientoFinancieroEgresoCompra->id,
            'monto' => 1000.00,
            'tipo' => 'Egreso',
            'referencia_tabla' => 'pedido_proveedores',
            'referencia_id' => $pedido->id,
        ]);
    }

    public function test_authenticated_user_cannot_receive_orders_with_invalid_data(): void
    {
        Sanctum::actingAs($this->user);

        $invalidPayload = [
            'fecha_entrega' => 'not-a-date',
            'proveedor_id' => 9999, // Non-existent supplier
            'productos' => [
                [
                    'producto_id' => 9999, // Non-existent product
                    'cantidad' => -5, // Invalid quantity
                    'precio_compra' => -10, // Invalid price
                ],
            ],
        ];

        $response = $this->postJson('/api/recibir-pedidos', $invalidPayload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'fecha_entrega',
                'proveedor_id',
                'monto_total',
                'productos.0.producto_id',
                'productos.0.cantidad',
                'productos.0.precio_compra',
            ]);
    }

    public function test_receiving_order_updates_product_stock_and_creates_movements(): void
    {
        Sanctum::actingAs($this->user);

        $initialStock1 = $this->producto1->stock_actual;
        $initialStock2 = $this->producto2->stock_actual;

        $payload = [
            'fecha_entrega' => now()->toDateString(),
            'user_id' => $this->user->id,
            'proveedor_id' => $this->proveedor->id,
            'monto_total' => 50 * 5 + 100 * 3,
            'productos' => [
                ['producto_id' => $this->producto1->id, 'cantidad' => 5, 'precio_compra' => 50],
                ['producto_id' => $this->producto2->id, 'cantidad' => 3, 'precio_compra' => 100],
            ],
        ];

        $response = $this->postJson('/api/recibir-pedidos', $payload);
        $response->assertStatus(201);

        $this->assertEquals($initialStock1 + 5, $this->producto1->fresh()->stock_actual);
        $this->assertEquals($initialStock2 + 3, $this->producto2->fresh()->stock_actual);

        $this->assertDatabaseCount('pedido_proveedores', 1);
        $this->assertDatabaseCount('detalle_pedido_proveedores', 2);
        $this->assertDatabaseCount('movimiento_inventarios', 2);
        $this->assertDatabaseCount('movimiento_financieros', 1);
    }

    public function test_receiving_order_handles_duplicate_invoice_number(): void
    {
        Sanctum::actingAs($this->user);

        $invoiceNumber = 'INV-DUPLICATE-001';

        $payload1 = [
            'numero_factura_proveedor' => $invoiceNumber,
            'fecha_entrega' => now()->toDateString(),
            'user_id' => $this->user->id,
            'proveedor_id' => $this->proveedor->id,
            'monto_total' => 100.00,
            'productos' => [
                ['producto_id' => $this->producto1->id, 'cantidad' => 1, 'precio_compra' => 100],
            ],
        ];

        $response1 = $this->postJson('/api/recibir-pedidos', $payload1);
        $response1->assertStatus(201);

        $payload2 = [
            'numero_factura_proveedor' => $invoiceNumber, // Duplicate invoice number
            'fecha_entrega' => now()->addDay()->toDateString(),
            'user_id' => $this->user->id,
            'proveedor_id' => $this->proveedor->id,
            'monto_total' => 200.00,
            'productos' => [
                ['producto_id' => $this->producto2->id, 'cantidad' => 1, 'precio_compra' => 200],
            ],
        ];

        $response2 = $this->postJson('/api/recibir-pedidos', $payload2);
        $response2->assertStatus(422)
            ->assertJsonValidationErrors(['numero_factura_proveedor']);
    }

    public function test_monto_total_calculation_is_accurate_and_matches_payload(): void
    {
        Sanctum::actingAs($this->user);

        $payload = [
            'fecha_entrega' => now()->toDateString(),
            'user_id' => $this->user->id,
            'proveedor_id' => $this->proveedor->id,
            'monto_total' => 250.00 + 300.00, // Explicitly provide calculated total
            'productos' => [
                ['producto_id' => $this->producto1->id, 'cantidad' => 5, 'precio_compra' => 50],
                ['producto_id' => $this->producto2->id, 'cantidad' => 3, 'precio_compra' => 100],
            ],
        ];

        $response = $this->postJson('/api/recibir-pedidos', $payload);
        $response->assertStatus(201);

        $this->assertDatabaseHas('pedido_proveedores', [
            'monto_total' => 550.00,
        ]);

        $movimientoFinanciero = MovimientoFinanciero::where('referencia_tabla', 'pedido_proveedores')
                                                    ->where('referencia_id', PedidoProveedor::first()->id)
                                                    ->first();
        $this->assertNotNull($movimientoFinanciero);
        $this->assertEquals(550.00, $movimientoFinanciero->monto);
    }
}
