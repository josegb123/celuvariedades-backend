<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MovimientoFinancieroResource extends JsonResource
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
            'monto' => $this->monto,
            'tipo' => $this->tipo, // Ingreso o Egreso
            'metodo_pago' => $this->metodo_pago,

            // Auditoría de referencia
            'referencia_tabla' => $this->referencia_tabla,
            'referencia_id' => $this->referencia_id,

            // Relaciones de auditoría
            'tipo_movimiento' => [
                'id' => $this->tipoMovimiento->id ?? null,
                'nombre' => $this->tipoMovimiento->nombre ?? 'N/A',
                'descripcion' => $this->tipoMovimiento->descripcion ?? 'N/A',
            ],
            'registrado_por' => [
                'id' => $this->user->id ?? null,
                'nombre' => $this->user->name ?? 'Sistema',
            ],

            'fecha_registro' => $this->created_at->toDateTimeString(),
        ];
    }
}