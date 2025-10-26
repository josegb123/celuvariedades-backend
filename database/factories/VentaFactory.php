<?php

namespace Database\Factories;

use App\Models\User;
use App\Modules\Cliente\Cliente;
use App\Modules\Ventas\Venta;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Ventas\Venta>
 */
class VentaFactory extends Factory
{
    protected $model = Venta::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $impuestos = fake()->randomFloat(4, 0, 50000);
        $descuento = fake()->randomFloat(4, 0, 50000);
        $subtotal = $impuestos + $descuento;
        $total = fake()->randomFloat(4, 0, 50000) + $subtotal;

        return [
            'user_id' => User::factory(),
            'cliente_id' => Cliente::factory(),
            'impuestos' => $impuestos,
            'descuento' => $descuento,
            'fecha_emision' => fake()->dateTimeThisDecade(),
            'subtotal_venta' => $subtotal,
            'total_venta' => $total,
        ];
    }
}
