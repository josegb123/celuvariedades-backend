<?php

namespace Database\Factories;

use App\Models\Cliente;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Venta>
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
        $iva_monto = fake()->randomFloat(4, 0, 50000);
        $descuento_total = fake()->randomFloat(4, 0, 50000);
        $subtotal = $iva_monto + $descuento_total;
        $total = fake()->randomFloat(4, 0, 50000) + $subtotal;

        return [
            'user_id' => User::factory(),
            'cliente_id' => Cliente::factory(),
            'subtotal' => $subtotal,
            'descuento_total' => $descuento_total,
            'iva_porcentaje' => 19.00,
            'iva_monto' => $iva_monto,
            'estado' => fake()->randomElement(['progreso', 'finalizada']),
            'metodo_pago' => fake()->randomElement(['efectivo', 'credito', 'nequi']),
            'total' => $total,
            'fecha_emision' => fake()->dateTimeThisDecade(),
        ];
    }
}
