<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PedidoProveedorResource extends JsonResource
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
            'numero_factura_proveedor' => $this->numero_factura_proveedor,
            'fecha_entrega' => $this->fecha_entrega?->format('Y-m-d'), // Formatear la fecha
            'monto_total' => $this->monto_total,
            'estado' => $this->estado,
            'fecha_creacion' => $this->created_at->format('Y-m-d H:i:s'),
            'fecha_actualizacion' => $this->updated_at->format('Y-m-d H:i:s'),

            // --- Relaciones ---

            // 1. Usuario que recibió el pedido
            'user' => new UserResource($this->whenLoaded('user')),

            // 2. Proveedor al que pertenece el pedido
            'proveedor' => new ProveedorResource($this->whenLoaded('proveedor')),

            // 3. Detalles del pedido (productos)
            'detalles' => DetallePedidoProveedorResource::collection($this->whenLoaded('detalles')),
        ];
    }
}