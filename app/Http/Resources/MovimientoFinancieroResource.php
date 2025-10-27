<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MovimientoFinancieroResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'monto' => $this->monto,
            'tipo_movimiento_id' => $this->tipo_movimiento_id,
            'descripcion' => $this->descripcion,
            'fecha' => $this->fecha,
            'venta_id' => $this->venta_id,
            'user_id' => $this->user_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
