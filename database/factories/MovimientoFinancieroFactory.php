<?php

namespace Database\Factories;

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
            'monto' => $this->faker->randomFloat(2, 1000, 100000),
            'tipo_movimiento_id' => TipoMovimientoFinanciero::all()->random()->id,
            'descripcion' => $this->faker->sentence,
            'fecha' => $this->faker->date(),
            'venta_id' => Venta::all()->random()->id,
            'user_id' => User::all()->random()->id,
        ];
    }
}
