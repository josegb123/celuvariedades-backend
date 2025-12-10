<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DevolucionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // 1. Calcular el valor de reembolso de este ítem
        $precioUnitarioVenta = $this->whenLoaded('detalleVenta', function () {
            return (float) $this->detalleVenta->precio_unitario;
        }, 0.00);

        $montoReembolsado = $this->cantidad * $precioUnitarioVenta;

        // 2. Determinar la gestión financiera
        // Asumiendo que el controlador puede pasar el método de reembolso usado,
        // o si tienes una relación con el modelo SaldoCliente o MovimientoFinanciero.
        // Si no tienes una relación directa con el SaldoCliente, se puede inferir.

        $gestionFinanciera = 'Desconocida';
        if ($this->whenLoaded('venta')) {
            if ($this->venta->cuentaPorCobrar && $this->venta->cuentaPorCobrar->monto_pendiente >= 0) {
                // Si había crédito y aún queda saldo pendiente, fue reducción de deuda.
                $gestionFinanciera = 'Reducción de Deuda (Cartera)';
            } else {
                // En cualquier otro caso, pudo ser egreso o saldo cliente (Nota Crédito)
                $gestionFinanciera = 'Generación Saldo Cliente / Egreso de Caja';
            }
        }


        return [
            'id' => $this->id,
            'venta_id' => $this->venta_id,
            'detalle_venta_id' => $this->detalle_venta_id, // Campo clave añadido

            // Información de cantidad y valores
            'cantidad_devuelta' => $this->cantidad,
            'precio_unitario_costo' => (float) $this->costo_unitario,
            'precio_unitario_venta' => $precioUnitarioVenta,
            'monto_total_reembolsado' => $montoReembolsado,

            // Información de gestión
            'motivo' => $this->motivo,
            'estado_gestion' => $this->estado_gestion,
            'gestion_financiera' => $gestionFinanciera, // Nuevo dato

            // Relaciones (Asegúrate de precargar estas relaciones en el controlador)
            'producto_info' => $this->whenLoaded('producto', function () {
                return [
                    'id' => $this->producto->id,
                    'nombre' => $this->producto->nombre,
                    'codigo_barra' => $this->producto->codigo_barra,
                ];
            }),

            'cliente_info' => $this->whenLoaded('cliente', function () {
                return [
                    'id' => $this->cliente->id,
                    'nombre' => $this->cliente->nombre,
                    'cedula' => $this->cliente->cedula,
                ];
            }),

            'created_at' => $this->created_at,
        ];
    }
}