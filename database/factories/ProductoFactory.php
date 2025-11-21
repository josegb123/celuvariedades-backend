<?php

namespace Database\Factories;

use App\Models\Categoria;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Producto>
 */
class ProductoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'categoria_id' => Categoria::factory(),
            'user_id' => User::factory(),
            'codigo_barra' => fake()->ean13(),
            'nombre' => fake()->word(),
            'descripcion' => fake()->paragraph(2),
            'precio_compra' => fake()->randomFloat(2, 50000, 250000),
            'precio_venta' => fake()->randomFloat(2, 70000, 350000),
            'stock_actual' => fake()->numberBetween(0, 100),
            'stock_reservado' => fake()->numberBetween(0, 10),
            'stock_minimo' => fake()->numberBetween(0, 10),
        ];
    }
}
