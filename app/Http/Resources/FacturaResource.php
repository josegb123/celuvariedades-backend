<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FacturaResource extends JsonResource
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
            'fecha_emision' => $this->fecha_emision,
            'total' => $this->total,
            'pdf_path' => $this->pdf_path,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
