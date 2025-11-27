<?php

namespace Database\Factories;

use App\Models\Cliente;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use App\Models\DetalleVenta;
use Illuminate\Database\Eloquent\Factories\Factory;

class VentaFactory extends Factory
{
    protected $model = Venta::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'cliente_id' => Cliente::factory(),
            'tipo_venta_id' => 1, // Asume que 1 es Contado, ajusta si tienes un factory para TipoVenta

            // Inicializamos los campos calculados en cero
            'subtotal' => 0.00,
            'descuento_total' => 0.00,
            'iva_porcentaje' => 19.00,
            'iva_monto' => 0.00,
            'total' => 0.00,

            // Otros campos
            'estado' => fake()->randomElement(['pendiente_pago', 'finalizada']),
            'metodo_pago' => fake()->randomElement(['efectivo', 'credito', 'tarjeta']),
            'fecha_emision' => fake()->dateTimeBetween('-6 months', 'now'),
        ];
    }

    /**
     * Estado que crea DetalleVenta relacionados y calcula los totales de la Venta.
     *
     * @param int $maxItems Número máximo de ítems (productos distintos) a incluir en la venta.
     */
    public function withItems(int $maxItems = 5): Factory
    {
        return $this->afterCreating(function (Venta $venta) use ($maxItems) {

            // 1. 🔍 Seleccionar una colección de productos ALEATORIOS y ÚNICOS
            $productosParaVenta = Producto::inRandomOrder()
                ->take(fake()->numberBetween(1, $maxItems)) // CRÍTICO: Toma entre 1 y maxItems productos
                ->get();

            $detalles = $productosParaVenta->map(function ($producto) use ($venta) {
                // 2. Crear los detalles de venta (usando el factory para la lógica de cálculo)
                return DetalleVenta::factory()->create([
                    'venta_id' => $venta->id,
                    'producto_id' => $producto->id,
                    // Sobrescribir los datos del factory con la información real del producto seleccionado
                    'precio_unitario' => $producto->precio_venta,
                    'nombre_producto' => $producto->nombre,
                    'codigo_barra' => $producto->codigo_barra,
                    'precio_costo' => $producto->precio_compra,

                    // Asegura que la cantidad sea aleatoria para este ítem
                    'cantidad' => fake()->numberBetween(1, 5),

                    // NOTA: Para que el subtotal, iva_monto, etc., sean correctos, deberías 
                    // calcularlos aquí o dentro de DetalleVentaFactory y pasarlos.
                    // Por simplicidad, asumimos que DetalleVentaFactory maneja el cálculo basado en la cantidad.
                ]);
            });

            // 3. Recalcular los totales de la cabecera (usando los detalles reales creados)
            $subtotalCalculado = $detalles->sum('subtotal');
            $descuentoTotalCalculado = $detalles->sum('descuento_monto');
            $ivaMontoCalculado = $detalles->sum('iva_monto');
            $totalCalculado = $subtotalCalculado + $ivaMontoCalculado;

            // 4. Actualizar la cabecera de la Venta con los totales calculados
            $venta->update([
                'subtotal' => round($subtotalCalculado, 2),
                'descuento_total' => round($descuentoTotalCalculado, 2),
                'iva_monto' => round($ivaMontoCalculado, 2),
                'total' => round($totalCalculado, 2),
            ]);
        });
    }
}