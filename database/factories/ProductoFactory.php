<?php

namespace Database\Factories;

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductoFactory extends Factory
{
    /**
     * Define el modelo asociado al factory.
     * @var string
     */
    protected $model = Producto::class;

    /**
     * Define el estado por defecto del modelo.
     */
    public function definition(): array
    {
        // 1. Cargar los diccionarios desde el archivo de configuración
        $nombres = config('test_data.productos.nombres');
        $adjetivos = config('test_data.productos.adjetivos_clave');

        // 2. Lógica de imagen (Picsum Photos)
        $hasImage = fake()->boolean(80);
        $imageUrl = null;
        if ($hasImage) {
            $width = fake()->numberBetween(600, 800);
            $height = fake()->numberBetween(400, 600);
            $imageId = fake()->numberBetween(1, 1084);
            $imageUrl = "https://picsum.photos/id/{$imageId}/{$width}/{$height}.webp";
        }

        // 3. Precio COP productos
        $minCompra = 20000;
        $maxCompra = 50000;
        $precioCompra = $this->faker->numberBetween($minCompra, $maxCompra);
        $margenMinimo = 30000;
        $precioVenta = $this->faker->numberBetween(
            $precioCompra + $margenMinimo,
            $precioCompra + 30000
        );

        return [
            'categoria_id' => Categoria::all()->random()->id,
            'user_id' => User::all()->random()->id,
            'codigo_barra' => fake()->ean13(),

            // Uso de Nombres coherentes
            'nombre' => fake()->unique()->randomElement($nombres) . ' ' . fake()->word(),

            // Uso de Descripciones coherentes
            'descripcion' => fake()->randomElement($adjetivos) . ' ' . fake()->sentence(5),

            'imagen_url' => $imageUrl,
            // Valores en COP (rango de millones)
            'precio_compra' => $precioCompra,
            'precio_venta' => $precioVenta,

            // Stock (simplemente ajustamos los rangos a tus necesidades)
            'stock_actual' => $this->faker->numberBetween(10, 100),
            'stock_reservado' => $this->faker->numberBetween(0, 5),
            'stock_minimo' => $this->faker->numberBetween(5, 15),
        ];
    }

    /**
     * Define el callback de configuración después de crear el modelo.
     * Asocia proveedores al producto sin datos en la tabla pivote.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Producto $producto) {
            // 1. Obtener una colección de IDs de proveedores activos
            $proveedoresIds = Proveedor::activo()->pluck('id');

            // 2. Si no hay proveedores, salimos
            if ($proveedoresIds->isEmpty()) {
                return;
            }

            // 3. Seleccionar entre 1 y 3 proveedores para este producto
            $count = fake()->numberBetween(1, min(3, $proveedoresIds->count()));

            // Seleccionar los IDs.
            // Nota: random() puede devolver un solo modelo/valor si solo se pide 1.
            $proveedoresParaAdjuntar = $proveedoresIds->random($count);

            // 4. Adjuntar los proveedores al producto usando sync()
            // Si $proveedoresParaAdjuntar no es una Collection (solo ocurre si count=1), 
            // la convertimos a Collection o Array para sync.
            if (!($proveedoresParaAdjuntar instanceof \Illuminate\Support\Collection)) {
                // Si solo devolvió un único ID, lo ponemos en un array.
                $syncIds = [$proveedoresParaAdjuntar];
            } else {
                // Si devolvió una Colección de IDs, usamos la Colección directamente.
                $syncIds = $proveedoresParaAdjuntar;
            }

            // 5. Adjuntar los IDs al producto
            $producto->proveedores()->sync($syncIds);

        });
    }
}