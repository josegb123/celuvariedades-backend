<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use App\Http\Resources\DetalleVentaResource; // Importamos el resource del detalle

class VentaShowResource extends VentaIndexResource
{
  /**
   * Transforma el recurso (Venta) para la vista de detalle.
   */
  public function toArray(Request $request): array
  {
    // 1. Obtiene todos los campos del IndexResource
    $data = parent::toArray($request);

    // 2. Añade los campos detallados/financieros
    $data['totales_financieros'] = [
      'subtotal' => (float) $this->subtotal,
      'iva_monto' => (float) $this->iva_monto,
      'iva_porcentaje' => (float) $this->iva_porcentaje,
      'descuento_total' => (float) $this->descuento_total,
      // Aquí puedes agregar campos de cartera si la relación está cargada
      // 'cartera' => CarteraResource::make($this->whenLoaded('cartera')),
    ];

    // 3. Añade la colección completa de ítems (detalles)
    $data['detalles_completos'] = $this->whenLoaded('detalles', function () {
      // Asegúrate de que la relación 'producto' esté cargada en los detalles
      return DetalleVentaResource::collection($this->detalles);
    });

    return $data;
  }
}