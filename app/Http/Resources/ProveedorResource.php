<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProveedorResource extends JsonResource
{
    /**
     * Transforma el recurso en un array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombreComercial' => $this->nombre_comercial,
            'nombreContacto' => $this->nombre_contacto,
            'identificacion' => $this->identificacion,
            'telefono' => $this->telefono,
            'email' => $this->email,
            'direccion' => $this->direccion,
            'ciudad' => $this->ciudad,
            'notas' => $this->notas,
            'activo' => (bool) $this->activo,
            'productosSuministrados' => ProductoResource::collection($this->whenLoaded('productos')),
        ];
    }
}