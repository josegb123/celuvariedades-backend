<?php

namespace App\Services;

use App\Models\AbonoCartera;
use Illuminate\Support\Facades\DB;
use Exception;

class AbonoService
{
  private CuentaPorCobrarService $cuentaPorCobrarService;
  private MovimientoFinancieroService $movimientoFinancieroService;

  public function __construct(
    CuentaPorCobrarService $cuentaPorCobrarService,
    MovimientoFinancieroService $movimientoFinancieroService
  ) {
    $this->cuentaPorCobrarService = $cuentaPorCobrarService;
    $this->movimientoFinancieroService = $movimientoFinancieroService;
  }

  /**
   * Procesa un abono completo, asegurando que el saldo de cartera se actualice 
   * y que el ingreso de dinero se registre en el libro de caja.
   *
   * @param array $validatedData Datos validados del AbonoRequest.
   * @return AbonoCartera
   */
  public function procesarAbono(array $validatedData): AbonoCartera
  {
    // 🚨 Transacción de nivel superior: Si algo falla en Cartera o en Caja, 
    // todo se revierte (rollback).
    return DB::transaction(function () use ($validatedData) {

      // 1. GESTIÓN DE LA DEUDA
      // Llama al servicio de CuentaPorCobrar para actualizar el saldo y crear el AbonoCartera.
      $abono = $this->cuentaPorCobrarService->abonarCuentaPorCobrar(
        cuentaPorCobrarId: $validatedData['cuenta_por_cobrar_id'],
        monto: $validatedData['monto'],
        metodoPago: $validatedData['metodo_pago'],
        userId: $validatedData['user_id'],
        referenciaPago: $validatedData['referencia_pago'] ?? null
      );

      // 2. GESTIÓN FINANCIERA (CAJA/BANCO)
      // Llama al servicio financiero para registrar la entrada de dinero.
      $this->movimientoFinancieroService->registrarMovimiento(
        monto: $abono->monto_abonado,
        tipoMovimientoNombre: 'Abono a Cartera', // Nombre de tipo de movimiento ya definido
        metodoPago: $abono->metodo_pago,
        userId: $abono->user_id,
        referenciaTabla: 'abono_carteras', // Tabla de origen del movimiento financiero
        referenciaId: $abono->id
      );

      return $abono;
    });
  }
}