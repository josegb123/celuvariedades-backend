<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DetalleVentaResource extends JsonResource
{
    /**
     * Transforma el recurso (DetalleVenta) en un array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'venta_id' => $this->venta_id,
            'producto_id' => $this->producto_id,

            // --- Campos Transaccionales ---
            'cantidad' => (float) $this->cantidad,
            'precio_unitario' => (float) $this->precio_unitario, // Precio Bruto unitario en el momento de la venta
            'subtotal' => (float) $this->subtotal,

            // --- Desglose de Impuestos y Descuentos ---
            'iva_porcentaje' => (float) $this->iva_porcentaje,
            'iva_monto' => (float) $this->iva_monto,
            'descuento_monto' => (float) $this->descuento_monto,

            // --- Campos Históricos (CRÍTICO para informes y trazabilidad) ---
            'nombre_producto_historico' => $this->nombre_producto,
            'codigo_barra_historico' => $this->codigo_barra,
            'precio_costo_historico' => (float) $this->precio_costo,

            // --- Información del Producto Actual (Relación) ---
            'producto' => $this->whenLoaded('producto', function () {
                // Exponemos la información actual del producto para referencia y stock
                return [
                    'id' => $this->producto->id,
                    'nombre_actual' => $this->producto->nombre,
                    'stock_actual_inventario' => (int) $this->producto->stock_actual,
                ];
            }),

            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}