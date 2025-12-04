<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductoResource extends JsonResource
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
            'categoria_id' => $this->categoria_id,
            'user_id' => $this->user_id,
            'codigo_barra' => $this->codigo_barra,
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'imagen_url' => $this->imagen_url,
            'precio_compra' => $this->precio_compra,
            'precio_venta' => $this->precio_venta,
            'stock_actual' => $this->stock_actual,
            'stock_reservado' => $this->stock_reservado,
            'stock_minimo' => $this->stock_minimo,
            'is_bajo_stock' => $this->is_bajo_stock,
            'categoria' => new CategoriaResource($this->whenLoaded('categoria')),
            'user' => new UserLiteResource($this->whenLoaded('user')),
            'proveedores' => ProveedorResource::collection($this->whenLoaded('proveedores')),
        ];
    }
}
