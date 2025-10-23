<?php

namespace Database\Seeders;

use App\Models\Cliente;
use App\Models\Cartera;
use App\Models\User;
use App\Models\Mensaje;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()
            ->count(10)
            ->create();

        // 1. Crear el pool completo de clientes primero (e.g., 100).
        // NOTA: 'aval_id' es NULL por defecto en la definición del Factory.
        $allClients = Cliente::factory(100)->create();

        // 2. Iterar sobre la colección para asignar las relaciones con lógica de sorteo.
        $allClients->each(function (Cliente $cliente) use ($allClients) {

            // --- Sorteo de Aval (Auto-Relación) ---

            // 33% de probabilidad de tener un aval
            if (rand(1, 3) === 1) {

                // Excluir al cliente actual para que no sea su propio aval
                $possibleAvals = $allClients->where('id', '!=', $cliente->id);

                // Solo si el pool de clientes tiene otros elementos
                if ($possibleAvals->isNotEmpty()) {
                    $aval = $possibleAvals->random();

                    // Asignar y guardar la relación
                    $cliente->aval_id = $aval->id;
                    $cliente->save(); // Es necesario guardar para persistir el cambio de aval_id
                }
            }

            // Crear mensajes aleatorios
            if (rand(1, 3) === 1) {
                $allClients
                    ->random()
                    ->has(Mensaje::factory()->count(3), 'mensajes')
                    ->create();
            }

            // --- Sorteo de Cartera (HasOne) ---

            // 50% de probabilidad de tener una cartera (para simular inactividad)
            if (rand(1, 2) === 1) {
                $cartera_type = rand(1, 3);
                $carteraFactory = Cartera::factory();

                if ($cartera_type === 1) {
                    $carteraFactory = $carteraFactory->withSaldo();
                } elseif ($cartera_type === 2) {
                    $carteraFactory = $carteraFactory->withDeuda();
                }
                // Si es 3, se crea con el estado base (0, 0)

                // Crear UNA ÚNICA Cartera asociada al cliente
                $carteraFactory->for($cliente)->create();
            }
        });
    }
}
