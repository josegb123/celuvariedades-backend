<?php

namespace Database\Factories;

use App\Models\CajaDiaria;
use App\Models\TipoMovimientoFinanciero;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MovimientoFinanciero>
 */
class MovimientoFinancieroFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tipo_movimiento_id' => TipoMovimientoFinanciero::all()->random()->id,
            'monto' => $this->faker->randomFloat(2, 1000, 100000),
            'tipo' => $this->faker->randomElement(['ingreso', 'egreso']),
            'metodo_pago' => $this->faker->randomElement(['efectivo', 'tarjeta', 'transferencia']),
            'descripcion' => $this->faker->sentence,
            'venta_id' => Venta::all()->random()->id,
            'user_id' => User::all()->random()->id,
            'referencia_tabla' => $this->faker->word(),
            'referencia_id' => $this->faker->randomDigit(),
            'caja_diaria_id' => CajaDiaria::all()->random()->id,
        ];
    }
}
