<?php

namespace Database\Seeders;

use App\Models\DetalleVenta;
use Illuminate\Database\Seeder;

class DetalleVentaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DetalleVenta::factory()->count(75)->create();
    }
}
