<?php

namespace App\Services;

use App\Models\AbonoCartera;
use App\Models\CajaDiaria;
use App\Models\CuentaPorCobrar;
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
    // Transacción de nivel superior: Si algo falla en Cartera o en Caja, 
    // todo se revierte (rollback).
    return DB::transaction(function () use ($validatedData) {

      $cuenta = CuentaPorCobrar::findOrFail($validatedData['cuenta_por_cobrar_id']);
      // 1. GESTIÓN DE LA DEUDA
      // Llama al servicio de CuentaPorCobrar para actualizar el saldo y crear el AbonoCartera.
      $abono = $this->cuentaPorCobrarService->abonarCuentaPorCobrar(
        cuentaPorCobrarId: $cuenta->id,
        monto: $validatedData['monto'],
        metodoPago: $validatedData['metodo_pago'],
        userId: $validatedData['user_id'],
        referenciaPago: $validatedData['referencia_pago'] ?? ""
      );


      $descripcionMovimiento = "Abono a Cartera de Cliente ID {$cuenta->cliente_id} por Venta ID {$cuenta->venta_id}";
      // 2. GESTIÓN FINANCIERA (CAJA/BANCO)
      // Llama al servicio financiero para registrar la entrada de dinero.
      $this->movimientoFinancieroService->registrarMovimiento(
        monto: $abono->monto_abonado,
        tipoMovimientoNombre: 'Abono a Cartera',
        metodoPago: $abono->metodo_pago,
        userId: $abono->user_id,
        descripcion: $descripcionMovimiento,
        referenciaTabla: 'abono_carteras',
        referenciaId: $abono->id,
        ventaId: $cuenta->venta_id,
        cajaDiariaId: $validatedData['caja_diaria_id'] ?? null
      );

      return $abono;
    });
  }
}