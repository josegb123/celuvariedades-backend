<?php

namespace Database\Factories;

use App\Models\Categoria;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductoFactory extends Factory
{
    public function definition(): array
    {
        // 1. Cargar los diccionarios desde el archivo de configuración
        $nombres = config('test_data.productos.nombres');
        $adjetivos = config('test_data.productos.adjetivos_clave');

        // 2. Lógica de imagen (Picsum Photos)
        $hasImage = fake()->boolean(80);
        $imageUrl = null;
        if ($hasImage) {
            $width = fake()->numberBetween(600, 800);
            $height = fake()->numberBetween(400, 600);
            $imageId = fake()->numberBetween(1, 1084);
            $imageUrl = "https://picsum.photos/id/{$imageId}/{$width}/{$height}.webp";
        }

        return [
            'categoria_id' => Categoria::all()->random()->id,
            'user_id' => User::factory(),
            'codigo_barra' => fake()->ean13(),

            // ?? Uso de Nombres coherentes
            'nombre' => fake()->randomElement($nombres),

            // ?? Uso de Descripciones coherentes
            // Combina una oración genérica con un adjetivo clave del diccionario
            'descripcion' => fake()->randomElement($adjetivos),

            'imagen_url' => $imageUrl,

            'precio_compra' => fake()->randomFloat(2, 50, 250),
            'precio_venta' => fake()->randomFloat(2, 70, 350),
            'stock_actual' => fake()->numberBetween(10, 100),
            'stock_reservado' => fake()->numberBetween(0, 5),
            'stock_minimo' => fake()->numberBetween(5, 15),
        ];
    }
}