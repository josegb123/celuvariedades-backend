<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ProveedorCollection extends ResourceCollection
{
  /**
   * Transforma la colección de recursos en un array.
   *
   * @return array<int|string, mixed>
   */
  public function toArray(Request $request): array
  {
    // Utilizamos el ProveedorResource para formatear cada elemento de la colección
    return ProveedorResource::collection($this->collection)->toArray($request);
  }

  /**
   * Agrega metadatos (como paginación) a la respuesta de la colección.
   *
   * @return array
   */
  public function with(Request $request): array
  {
    // Devolvemos el array de paginación automáticamente generado por Laravel
    return [
      'meta' => [
        'current_page' => $this->currentPage(),
        'last_page' => $this->lastPage(),
        'per_page' => $this->perPage(),
        'total' => $this->total(),
      ],
      'links' => [
        'first' => $this->url(1),
        'last' => $this->url($this->lastPage()),
        'prev' => $this->previousPageUrl(),
        'next' => $this->nextPageUrl(),
      ],
    ];
  }
}