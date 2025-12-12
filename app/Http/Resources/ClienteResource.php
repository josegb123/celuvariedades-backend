<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClienteResource extends JsonResource
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
            'cedula' => $this->cedula,
            'nombre' => $this->nombre,
            'apellidos' => $this->apellidos,
            'telefono' => $this->telefono,
            'email' => $this->email,
            'direccion' => $this->direccion,
            'aval_id' => $this->aval_id,
            'estado_financiero' => $this->whenLoaded('saldos', function () {

                return SaldoClienteResource::collection($this->saldos);
            }),
            'deudas' => $this->whenLoaded('cuentasPorCobrar', function () {

                return CuentaPorCobrarResource::collection($this->cuentasPorCobrar);
            })
        ];
    }
}
