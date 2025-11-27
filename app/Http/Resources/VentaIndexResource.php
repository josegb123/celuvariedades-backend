<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VentaIndexResource extends JsonResource
{
  /**
   * Transforma el recurso (Venta) para la vista de índice (listado).
   */
  public function toArray(Request $request): array
  {
    return [
      'venta_id' => $this->id,

      // --- Información Clave para el Listado ---
      'fecha_emision' => $this->fecha_emision,
      'estado' => $this->estado,

      // Campo Calculado: Muestra el resumen de los productos (ej: 3x Pantalón, 1x Camisa)
      'resumen_productos' => $this->resumen_productos,

      // Totales para visualización rápida
      'total_venta' => (float) $this->total,
      'metodo_pago' => $this->metodo_pago,

      // --- Relaciones Ligeras (Solo nombres) ---
      'usuario_vendedor' => $this->whenLoaded('user', function () {
        return $this->user->name;
      }),
      'cliente_nombre' => $this->whenLoaded('cliente', function () {
        return $this->cliente->nombre . ' ' . $this->cliente->apellidos;
      }),

      'created_at' => $this->created_at->format('Y-m-d H:i:s'),
    ];
  }
}