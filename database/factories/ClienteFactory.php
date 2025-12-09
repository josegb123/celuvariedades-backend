<?php

namespace Database\Factories;

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
}
