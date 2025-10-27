<?php

namespace Database\Factories;

use App\Models\Cartera;
use App\Models\Cliente;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClienteFactory extends Factory
{
    protected $model = Cliente::class;

    // Necesitamos una variable estática para guardar los IDs de los clientes ya creados
    // para usarlos como avales en las futuras creaciones.
    protected static ?Collection $existingClients = null;

    public function definition(): array
    {

        return [
            'cedula' => fake()->unique()->numberBetween(1000000000, 9999999999),
            'nombre' => fake()->name(),
            'apellidos' => fake()->name(),
            'telefono' => fake()->phoneNumber(),
            'email' => fake()->unique()->safeEmail(),
            'direccion' => fake()->address(),
            'aval_id' => null,
        ];
    }

    // ----------------------------------------------------------------------
    // 2. Creación de la Cartera (HasOne)
    // ----------------------------------------------------------------------

    /**
     * Define un estado para crear la Cartera asociada con un estado aleatorio.
     */
    public function withCartera(): Factory
    {
        return $this->afterCreating(function (Cliente $cliente) {
            $cliente->cartera()->create([
                'venta_id' => null,
                'monto_original' => fake()->randomFloat(2, 100, 1000),
                'monto_pendiente' => fake()->randomFloat(2, 0, 500),
                'fecha_vencimiento' => fake()->dateTimeBetween('+1 month', '+6 months'),
                'estado' => fake()->randomElement(['Pendiente', 'Pagada', 'Vencida']),
            ]);
        });
    }
}
