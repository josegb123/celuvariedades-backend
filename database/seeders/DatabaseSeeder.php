<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            TipoVentaSeeder::class, // Seeder para tipos de venta
            TipoMovimientoFinancieroSeeder::class,
            ClienteSeeder::class,
            VentaSeeder::class,
            MovimientoFinancieroSeeder::class,
            FacturaSeeder::class,
        ]);
    }
}
