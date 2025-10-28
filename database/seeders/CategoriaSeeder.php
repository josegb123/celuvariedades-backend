<?php

namespace Database\Seeders;

use App\Models\Categoria;
use Illuminate\Database\Seeder;

class CategoriaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Categoria::create(['nombre' => 'Electrodomesticos']);
        Categoria::create(['nombre' => 'Ropa']);
        Categoria::create(['nombre' => 'Celulares']);
        Categoria::create(['nombre' => 'Calzado']);
        Categoria::create(['nombre' => 'Cosmeticos']);
        Categoria::create(['nombre' => 'Accesorios']);
        Categoria::create(['nombre' => 'Variedades']);
    }
}
