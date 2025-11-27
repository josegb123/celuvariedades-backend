<?php

namespace Database\Factories;

use App\Models\Producto;
use App\Models\Venta;
use App\Models\DetalleVenta;
use Illuminate\Database\Eloquent\Factories\Factory;

class DetalleVentaFactory extends Factory
{
    protected $model = DetalleVenta::class; // Especificar el modelo

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $producto = Producto::inRandomOrder()->first() ?? Producto::factory()->create();

        $cantidad = fake()->numberBetween(1, 10);
        $precioUnitario = $producto->precio_venta;
        $descuentoMonto = fake()->boolean(20) ? fake()->randomFloat(2, 100, 500) : 0.00;
        $ivaPorcentaje = 19.00; // Asumir 19%

        // Cálculo del subtotal y del IVA para el detalle
        $subtotalBrutoLinea = $cantidad * $precioUnitario;
        $subtotalNetoLinea = $subtotalBrutoLinea - $descuentoMonto;
        $ivaMonto = $subtotalNetoLinea * ($ivaPorcentaje / 100);

        return [
            // Relaciones (venta_id se suele establecer en el VentaFactory)
            'venta_id' => Venta::inRandomOrder()->first()->id ?? Venta::factory(), // Fallback si no hay ventas
            'producto_id' => $producto->id,

            // Datos Transaccionales
            'cantidad' => $cantidad,
            'precio_unitario' => $precioUnitario,
            'subtotal' => $subtotalNetoLinea, // Subtotal de la línea (neto de descuento, sin IVA)

            // --- Campos Históricos (CRÍTICOS) ---
            'nombre_producto' => $producto->nombre,
            'codigo_barra' => $producto->codigo_barra,
            'precio_costo' => $producto->precio_compra,

            // --- Desglose de Impuestos/Descuentos ---
            'iva_porcentaje' => $ivaPorcentaje,
            'iva_monto' => $ivaMonto,
            'descuento_monto' => $descuentoMonto,
        ];
    }
}