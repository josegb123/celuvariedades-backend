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

            $cartera_type = rand(1, 3);
            $carteraFactory = Cartera::factory();

            if ($cartera_type === 1) {
                $carteraFactory = $carteraFactory->withSaldo();
            } elseif ($cartera_type === 2) {
                $carteraFactory = $carteraFactory->withDeuda();
            }
            // Si es 3, usa el estado base (0, 0)

            // Creación de la ÚNICA Cartera
            $carteraFactory->for($cliente)->create();
        });
    }
}
