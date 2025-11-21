<?php

namespace Database\Seeders;

use App\Models\Venta;
use Illuminate\Database\Seeder;

class VentaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Venta::factory()->count(1)->create([
            'tipo_venta_id' => 1, // Asignar un tipo de venta válido
        ]);
    }
}
