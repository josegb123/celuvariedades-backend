<?php

namespace Database\Seeders;

use App\Models\Cliente;
use Illuminate\Database\Seeder;

class ClienteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Cliente::firstOrCreate(
            ['cedula' => '0000000000'],
            [
                'nombre' => 'Cliente Anónimo',
                'apellidos' => 'Venta Anónima',
                'telefono' => 'N/A',
                'email' => 'anonimo@tuempresa.com',
                'direccion' => 'N/A',
                'aval_id' => null,
            ]
        );
        Cliente::factory()->count(15)->create();
    }
}
