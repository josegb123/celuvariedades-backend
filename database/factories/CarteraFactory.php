<?php

namespace Database\Factories;

use App\Models\Cartera;
use Illuminate\Database\Eloquent\Factories\Factory;

class CarteraFactory extends Factory
{
    protected $model = Cartera::class;

    /**
     * Define the model's default state (Cartera inactivo/neutro).
     */
    public function definition(): array
    {
        return [
            // Por defecto, ambas columnas inician en cero.
            // La clave foránea 'cliente_id' será establecida por el ClienteFactory
            // cuando se usa el método 'for($cliente)'.
            'saldo' => 0.00,
            'total_deuda' => 0.00,
        ];
    }

    // ----------------------------------------------------------------------
    // ESTADOS PARA SIMULAR CARTERAS ACTIVAS
    // ----------------------------------------------------------------------

    /**
     * Define un estado para simular una cartera con Saldo (devolución/crédito).
     */
    public function withSaldo(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                // Simula un saldo de 50.00 a 500.00
                'saldo' => fake()->randomFloat(2, 50, 500),
            ];
        });
    }

    /**
     * Define un estado para simular una cartera con Deuda (deuda contraída).
     */
    public function withDeuda(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                // Simula una deuda de 100.00 a 5000.00
                'total_deuda' => fake()->randomFloat(2, 100, 5000),
            ];
        });
    }
}
