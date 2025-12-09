<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DevolucionShowResource extends JsonResource
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
            'producto_id' => $this->producto_id,
            'cliente_id' => $this->cliente_id,
            'cantidad' => (float) $this->cantidad,
            'motivo' => $this->motivo,
            'costo_unitario' => (float) $this->costo_unitario,
            'estado_gestion' => $this->estado_gestion,

            // Relaciones necesarias y limpias
            'cliente' => [
                'nombre' => $this->cliente->nombre,
                'cedula' => $this->cliente->cedula,
            ],

            'producto' => $this->whenLoaded('producto', function () {

                // Obtenemos la colección de proveedores (Many-to-Many)
                $proveedoresCollection = $this->producto->proveedores;

                // Accedemos al primer elemento de la colección de proveedores (el único que necesitamos para contacto)
                $primerProveedor = $proveedoresCollection->first();

                return [
                    'id' => $this->producto->id,
                    'nombre' => $this->producto->nombre,
                    // Si el primer proveedor existe, lo incluimos usando ProveedorResource
                    'proveedores' => $primerProveedor ? new ProveedorResource($primerProveedor) : null,
                ];
            }),
        ];
    }
}