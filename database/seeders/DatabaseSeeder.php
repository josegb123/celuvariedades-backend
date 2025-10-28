<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            TipoVentaSeeder::class, // Seeder para tipos de venta
            TipoMovimientoFinancieroSeeder::class,
            CategoriaSeeder::class,
            ProductoSeeder::class,
            ClienteSeeder::class,
            VentaSeeder::class,
            MovimientoFinancieroSeeder::class,
            DetalleVentaSeeder::class,
            FacturaSeeder::class,

        ]);
    }
}
