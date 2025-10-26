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
            'cliente_id' => $this->cliente_id,
            'user_id' => $this->user_id,
            'fecha_emision' => $this->fecha_emision,
            'descuento' => $this->descuento,
            'impuestos' => $this->impuestos,
            'subtotal_venta' => $this->subtotal_venta,
            'total_venta' => $this->total_venta,
        ];
    }
}
