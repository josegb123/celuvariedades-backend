<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            TipoMovimientoFinancieroSeeder::class,
            TipoMovimientoInventarioSeeder::class,
            TipoVentaSeeder::class,
            CategoriaSeeder::class,
            ProductoSeeder::class,
            ClienteSeeder::class,
            //VentaSeeder::class,
            //MovimientoFinancieroSeeder::class,
            //DetalleVentaSeeder::class,

        ]);
    }
}
