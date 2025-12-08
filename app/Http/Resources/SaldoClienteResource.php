<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaldoClienteResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'cliente_id' => $this->cliente_id,
            'cuenta_por_cobrar_id' => $this->cuenta_por_cobrar_id,
            'monto_original' => $this->monto_original,
            'monto_pendiente' => $this->monto_pendiente,
            'estado' => $this->estado,
            'motivo' => $this->motivo
        ];
    }
}
