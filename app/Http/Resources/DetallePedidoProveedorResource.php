<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DetallePedidoProveedorResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'pedido_proveedor_id' => $this->pedido_proveedor_id,
            'producto_id' => $this->producto_id,
            'cantidad' => (float) $this->cantidad,
            'precio_compra' => (float) $this->precio_compra,
            'subtotal' => (float) $this->subtotal,
            'fecha_creacion' => $this->created_at->format('Y-m-d H:i:s'),

            // --- Relaciones (Carga Condicional) ---

            'producto' => new ProductoResource($this->whenLoaded('producto')),

        ];
    }
}