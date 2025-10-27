<?php

namespace Database\Seeders;

use App\Models\MovimientoFinanciero;
use Illuminate\Database\Seeder;

class MovimientoFinancieroSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        MovimientoFinanciero::factory(10)->create();
    }
}
