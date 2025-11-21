<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Producto;
use App\Models\Categoria;

class ProductoTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_can_list_productos()
    {
        Producto::factory()->count(3)->create();

        $response = $this->actingAs($this->user)->getJson('/api/productos');
        $response->dump();
        $response->json();

        $response->assertOk()
                 ->assertJsonCount(3, 'data')
                 ->assertJsonStructure([
                     'data' => [
                         '*' => [
                             'id',
                             'categoria_id',
                             'user_id',
                             'nombre',
                             'descripcion',
                             'precio_compra',
                             'precio_venta',
                             'stock_actual',
                             'stock_reservado',
                             'stock_minimo',
                             'created_at',
                             'updated_at',
                             'categoria' => [
                                'id',
                                'nombre',
                                'created_at',
                                'updated_at',
                             ],
                             'user' => [
                                'id',
                                'name',
                                'email',
                                'created_at',
                                'updated_at',
                             ]
                         ]
                     ]
                 ]);
    }

    public function test_can_create_producto()
    {
        $categoria = Categoria::factory()->create();
        $productoData = [
            'categoria_id' => $categoria->id,
            'user_id' => $this->user->id,
            'nombre' => $this->faker->word,
            'descripcion' => $this->faker->sentence,
            'precio_compra' => $this->faker->randomFloat(2, 10, 100),
            'precio_venta' => $this->faker->randomFloat(2, 100, 200),
            'stock_actual' => $this->faker->numberBetween(10, 100),
            'stock_reservado' => 0,
            'stock_minimo' => 5,
        ];

        $response = $this->actingAs($this->user)->postJson('/api/productos', $productoData);

        $response->assertStatus(201)
                 ->assertJsonFragment(['nombre' => $productoData['nombre']]);

        $this->assertDatabaseHas('productos', ['nombre' => $productoData['nombre']]);
    }

    public function test_can_show_producto()
    {
        $producto = Producto::factory()->create();

        $response = $this->actingAs($this->user)->getJson('/api/productos/' . $producto->id);

        $response->assertOk()
                 ->assertJsonFragment(['nombre' => $producto->nombre]);
    }

    public function test_can_update_producto()
    {
        $producto = Producto::factory()->create();
        $updatedNombre = 'Updated ' . $this->faker->word;

        $response = $this->actingAs($this->user)->putJson('/api/productos/' . $producto->id, [
            'nombre' => $updatedNombre,
        ]);

        $response->assertOk()
                 ->assertJsonFragment(['nombre' => $updatedNombre]);

        $this->assertDatabaseHas('productos', ['id' => $producto->id, 'nombre' => $updatedNombre]);
    }

    public function test_can_delete_producto()
    {
        $producto = Producto::factory()->create();

        $response = $this->actingAs($this->user)->deleteJson('/api/productos/' . $producto->id);

        $response->assertStatus(204);

        $this->assertSoftDeleted('productos', ['id' => $producto->id]);
    }
}
