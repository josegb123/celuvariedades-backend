<?php

namespace App\Http\Controllers;

use App\Services\AbonoService;
use App\Http\Requests\StoreAbonoRequest;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class AbonoController extends Controller
{
  private AbonoService $abonoService;

  public function __construct(AbonoService $abonoService)
  {
    $this->abonoService = $abonoService;
  }

  /**
   * Registra un nuevo abono para una Cuenta Por Cobrar específica (ID en el body).
   *
   * @param StoreAbonoRequest $request
   * @return JsonResponse
   */
  public function store(StoreAbonoRequest $request)
  {
    try {
      $validatedData = $request->validated();

      // Aseguramos el user_id
      $validatedData['user_id'] = auth()->id();

      $abono = $this->abonoService->procesarAbono($validatedData);

      return response()->json([
        'message' => 'Abono registrado exitosamente. Deuda y movimiento financiero actualizados.',
        'abono' => $abono->load('cuentaPorCobrar'), // Cargamos la relación para la respuesta
      ], SymfonyResponse::HTTP_CREATED);

    } catch (\Throwable $e) {
      // El uso de 400 es adecuado cuando la lógica de negocio falla (ej. saldo excedido).
      return response()->json([
        'error' => 'Error al procesar el abono',
        'message' => $e->getMessage(),
      ], SymfonyResponse::HTTP_BAD_REQUEST);
    }
  }
}