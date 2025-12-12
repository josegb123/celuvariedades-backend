<?php

namespace App\Services;

use App\Models\AbonoCartera;
use App\Models\CajaDiaria;
use App\Models\Cliente;
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
      // 1. Asignamos el valor de 'tipo_abono', usando 'a_deuda' como valor por defecto si no existe o es null.
      $tipoAbono = $validatedData['tipo_abono'] ?? 'a_deuda';

      // 2. Usamos el valor normalizado en el condicional.
      if ($tipoAbono === 'inicial') {
        $tipoMovimientoNombre = "Abono inicial a venta";
      } else {
        // Esto cubrirá 'a_deuda' (el valor por defecto) y cualquier otro valor que no sea 'inicial'.
        $tipoMovimientoNombre = "Abono a deuda";
      }

      $cliente = Cliente::findOrFail($cuenta->cliente_id)->nombre;

      $descripcionMovimiento = "{$tipoMovimientoNombre} por {$cliente}  a Venta ID {$cuenta->venta_id}";

      // 2. GESTIÓN FINANCIERA (CAJA/BANCO)
      // Llama al servicio financiero para registrar la entrada de dinero.
      $this->movimientoFinancieroService->registrarMovimiento(
        monto: $abono->monto_abonado,
        tipoMovimientoNombre: $tipoMovimientoNombre,
        metodoPago: $abono->metodo_pago,
        userId: $abono->user_id,
        descripcion: $descripcionMovimiento,
        referenciaTabla: $tipoMovimientoNombre,
        referenciaId: $abono->id,
        ventaId: $cuenta->venta_id,
        cajaDiariaId: $validatedData['caja_diaria_id'] ?? null
      );

      return $abono;
    });
  }
}