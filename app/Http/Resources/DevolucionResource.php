<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DevolucionResource extends JsonResource
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
            'venta_id' => $this->venta_id,
            'id_unico_producto' => $this->id_unico_producto,
            'cantidad' => $this->cantidad,
            'motivo' => $this->motivo,
            'costo_unitario' => (float) $this->costo_unitario,
            'estado_gestion' => $this->estado_gestion,

            'producto_info' => [
                'id' => $this->producto->id,
                'nombre' => $this->producto->nombre,
                'codigo_barra' => $this->producto->codigo_barra,
            ],

            'cliente_info' => [
                'id' => $this->cliente->id,
                'nombre' => $this->cliente->nombre,
                'cedula' => $this->cliente->cedula,
            ],

            'created_at' => $this->created_at,
        ];
    }
}
