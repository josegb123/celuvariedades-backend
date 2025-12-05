<?php

namespace Database\Factories;

use App\Models\CajaDiaria;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CajaDiaria>
 */
class CajaDiariaFactory extends Factory
{
    protected $model = CajaDiaria::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(), // Automatically create a User if not provided
            'fecha_apertura' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'fondo_inicial' => $this->faker->randomFloat(2, 0, 1000),
            'monto_cierre_teorico' => null, // Renamed from saldo_final
            'monto_cierre_fisico' => null, // Added as per migration
            'diferencia' => null, // Added as per migration
            'fecha_cierre' => null,
            'estado' => $this->faker->randomElement(['abierta', 'cerrada']),
        ];
    }

    /**
     * Indicate that the caja is in 'abierta' state.
     */
    public function abierta(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'estado' => 'abierta',
                'fecha_cierre' => null,
                'saldo_final' => null,
            ];
        });
    }

    /**
     * Indicate that the caja is in 'cerrada' state.
     */
    public function cerrada(): Factory
    {
        return $this->state(function (array $attributes) {
            $fechaApertura = $attributes['fecha_apertura'] ?? $this->faker->dateTimeBetween('-2 months', '-1 month');
            $fondoInicial = $attributes['fondo_inicial'] ?? $this->faker->randomFloat(2, 0, 1000);
            return [
                'estado' => 'cerrada',
                'fecha_cierre' => $this->faker->dateTimeBetween($fechaApertura, 'now'),
                'monto_cierre_teorico' => $this->faker->randomFloat(2, $fondoInicial, $fondoInicial + 500),
                'monto_cierre_fisico' => $this->faker->randomFloat(2, $fondoInicial, $fondoInicial + 500),
                'diferencia' => $this->faker->randomFloat(2, -50, 50),
            ];
        });
    }
}

