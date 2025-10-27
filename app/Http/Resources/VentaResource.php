<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VentaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'user_id' => $this->user_id,
            'cliente_id' => $this->cliente_id,
            'subtotal' => $this->subtotal,
            'descuento_total' => $this->descuento,
            'iva_porcentaje' => $this->iva_porcentaje,
            'iva_monto' => $this->iva_monto,
            'estado' => $this->estado,
            'metodo_pago' => $this->metodo_pago,
            'total' => $this->total,
            'fecha_emision' => $this->fecha_emision,
        ];
    }
}
