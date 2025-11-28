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
   * Registra un nuevo abono para una Cuenta Por Cobrar específica.
   *
   * @param StoreAbonoRequest $request
   * @param int $cuentaPorCobrarId ID del recurso maestro
   * @return JsonResponse
   */
  public function store(StoreAbonoRequest $request, int $cuentaPorCobrarId)
  {
    try {
      $validatedData = $request->validated();

      // 🚨 Aseguramos que el ID de la cuenta esté en los datos a procesar
      $validatedData['cuenta_por_cobrar_id'] = $cuentaPorCobrarId;
      $validatedData['user_id'] = auth()->id();

      $abono = $this->abonoService->procesarAbono($validatedData);

      // 🚨 Puedes crear un AbonoCarteraResource aquí
      return response()->json([
        'message' => 'Abono registrado exitosamente. Deuda actualizada.',
        'abono' => $abono, // O usa un AbonoCarteraResource
      ], SymfonyResponse::HTTP_CREATED);

    } catch (\Throwable $e) {
      return response()->json([
        'error' => 'Error al procesar el abono',
        'message' => $e->getMessage(),
      ], SymfonyResponse::HTTP_BAD_REQUEST);
    }
  }
}