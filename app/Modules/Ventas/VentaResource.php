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
            'cliente_id',
            'user_id',
            'fecha_emision',
            'descuento',
            'impuestos',
            'subtotal_venta',
            'total_venta',
        ];
    }
}
