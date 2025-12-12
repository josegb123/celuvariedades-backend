<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CuentaPorCobrarResource extends JsonResource
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
            'monto_original' => (float) $this->monto_original,
            'monto_pendiente' => (float) $this->monto_pendiente,
            'fecha_vencimiento' => $this->fecha_vencimiento ? $this->fecha_vencimiento : null,
            'estado' => $this->estado,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
            'esta_vencida' => Carbon::parse($this->fecha_vencimiento) ? $this->fecha_vencimiento <= Carbon::now() && $this->estado === 'Pendiente' : false,
            'porcentaje_pagado' => $this->monto_original > 0
                ? round((($this->monto_original - $this->monto_pendiente) / $this->monto_original) * 100, 2) : 0,
            'cliente' => new ClienteResource($this->whenLoaded('cliente')),
            'venta' => new VentaIndexResource($this->whenLoaded('venta')),
            'saldo_cliente' => new SaldoClienteResource($this->whenLoaded('saldoCliente')),

        ];
    }
}
