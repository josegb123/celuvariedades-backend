<?php

namespace Database\Factories;

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Proveedor; // ¡Nueva Importación!
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

        return [
            'categoria_id' => Categoria::all()->random()->id,
            // Asegúrate de que tienes Users creados, si no, usa User::factory()
            'user_id' => User::all()->random()->id,
            'codigo_barra' => fake()->ean13(),

            // Uso de Nombres coherentes
            'nombre' => fake()->unique()->randomElement($nombres) . ' ' . fake()->word(),

            // Uso de Descripciones coherentes
            'descripcion' => fake()->randomElement($adjetivos) . ' ' . fake()->sentence(5),

            'imagen_url' => $imageUrl,

            // El precio de compra aquí será un valor de referencia o precio base
            'precio_compra' => fake()->randomFloat(2, 50, 250),
            'precio_venta' => fake()->randomFloat(2, 70, 350),
            'stock_actual' => fake()->numberBetween(10, 100),
            'stock_reservado' => fake()->numberBetween(0, 5),
            'stock_minimo' => fake()->numberBetween(5, 15),
        ];
    }

    /**
     * Define el callback de configuración después de crear el modelo.
     * Asocia proveedores al producto.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Producto $producto) {
            // 1. Obtener una colección de IDs de proveedores activos
            // Asegúrate de que ProveedorFactory ya haya creado datos.
            $proveedoresIds = Proveedor::activo()->pluck('id');

            // 2. Si no hay proveedores, salimos (esto no debería pasar en el Seeder)
            if ($proveedoresIds->isEmpty()) {
                return;
            }

            // 3. Seleccionar entre 1 y 3 proveedores para este producto
            $proveedoresParaAdjuntar = $proveedoresIds->random(
                fake()->numberBetween(1, min(3, $proveedoresIds->count()))
            );

            // 4. Crear los datos de la tabla pivote para cada proveedor seleccionado
            $syncData = [];
            foreach ($proveedoresParaAdjuntar as $proveedorId) {
                // Generamos un precio de costo aleatorio, basado en el precio de compra del producto
                $precioCosto = fake()->randomFloat(
                    2,
                    $producto->precio_compra * 0.9, // 10% menos que el precio base
                    $producto->precio_compra * 1.1  // 10% más que el precio base
                );

                // Preparamos los datos a sincronizar, incluyendo los campos del pivot
                $syncData[$proveedorId] = [
                    'precio_costo' => $precioCosto,
                    'referencia_proveedor' => fake()->bothify('REF-####-??'),
                ];
            }

            // 5. Adjuntar los proveedores al producto usando la relación belongsToMany
            $producto->proveedores()->sync($syncData);
        });
    }
}