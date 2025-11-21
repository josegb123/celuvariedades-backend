<?php

namespace Database\Seeders;

use App\Models\TipoMovimientoInventario;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TipoMovimientoInventarioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        TipoMovimientoInventario::insert([
            ['nombre' => 'Venta', 'tipo_operacion' => 'SALIDA'],
            ['nombre' => 'Compra', 'tipo_operacion' => 'ENTRADA'],
            ['nombre' => 'Devolucion', 'tipo_operacion' => 'ENTRADA'],
            ['nombre' => 'Devolucion_Proveedor', 'tipo_operacion' => 'SALIDA'],
        ]);
    }
}
