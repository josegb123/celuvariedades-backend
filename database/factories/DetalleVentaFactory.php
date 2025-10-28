<?php

namespace Database\Factories;

use App\Models\Producto;
use App\Models\Venta;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DetalleVenta>
 */
class DetalleVentaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $producto = Producto::all()->random();
        $cantidad = fake()->numberBetween(0, 15);
        $precio_unitario = $producto->precio_venta;
        $subtotal = $cantidad * $precio_unitario;

        return [
            'venta_id' => Venta::all()->random()->id,
            'producto_id' => $producto->id,
            'cantidad' => $cantidad,
            'precio_unitario' => $precio_unitario,
            'subtotal' => $subtotal,
        ];
    }
}
