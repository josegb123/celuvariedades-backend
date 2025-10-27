<?php

namespace Database\Seeders;

use App\Models\TipoMovimientoFinanciero;
use Illuminate\Database\Seeder;

class TipoMovimientoFinancieroSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        TipoMovimientoFinanciero::create(['nombre' => 'Ingreso']);
        TipoMovimientoFinanciero::create(['nombre' => 'Egreso']);
    }
}
