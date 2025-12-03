<?php

namespace App\Services;

use App\Models\CajaDiaria;
use App\Models\Venta;
use Illuminate\Support\Facades\DB;
use Exception;

class CajaDiariaService
{
    /**
     * Obtiene la sesión de caja activa (abierta) para un usuario.
     * @param int $userId ID del usuario (cajero).
     * @return CajaDiaria|null
     */
    public function obtenerCajaActiva(int $userId): ?CajaDiaria
    {
        return CajaDiaria::where('user_id', $userId)
            ->where('estado', 'abierta')
            ->latest('fecha_apertura')
            ->first();
    }

    /**
     * Registra el fondo inicial para abrir una nueva sesión de caja.
     * @param int $userId ID del usuario (cajero).
     * @param float $fondoInicial Monto inicial de la caja (base de cambio).
     * @return CajaDiaria
     * @throws Exception Si ya existe una caja abierta para el usuario.
     */
    public function abrirCaja(int $userId, float $fondoInicial): CajaDiaria
    {
        // Validación 1: Verificar que no haya una caja abierta
        if ($this->obtenerCajaActiva($userId)) {
            throw new Exception("Ya existe una caja abierta para este usuario. Debe cerrarla primero.");
        }

        // Validación 2: El fondo inicial debe ser positivo
        if ($fondoInicial < 0) {
            throw new Exception("El fondo inicial no puede ser negativo.");
        }

        // Creación del registro de apertura
        return CajaDiaria::create([
            'user_id' => $userId,
            'fondo_inicial' => $fondoInicial,
            'fecha_apertura' => now(),
            'estado' => 'abierta',
        ]);
    }

    /**
     * Cierra una sesión de caja, calcula el monto teórico y registra la diferencia.
     * @param CajaDiaria $caja Sesión de caja a cerrar.
     * @param float $montoCierreFisico Monto de efectivo contado por el cajero.
     * @return CajaDiaria
     * @throws Exception Si la caja ya estaba cerrada.
     */
    public function cerrarCaja(CajaDiaria $caja, float $montoCierreFisico): CajaDiaria
    {
        if ($caja->estado !== 'abierta') {
            throw new Exception("La caja ya se encuentra cerrada o cancelada.");
        }

        // Paso 1: Calcular el monto teórico esperado.
        $montoTeorico = $this->calcularMontoTeorico($caja);

        // Paso 2: Calcular la diferencia (Sobrante > 0, Faltante < 0)
        $diferencia = $montoCierreFisico - $montoTeorico;

        // Paso 3: Actualizar el registro de cierre
        $caja->update([
            'fecha_cierre' => now(),
            'monto_cierre_teorico' => $montoTeorico,
            'monto_cierre_fisico' => $montoCierreFisico,
            'diferencia' => $diferencia,
            'estado' => 'cerrada',
        ]);

        return $caja;
    }

    /**
     * Calcula el monto de efectivo teórico que debería haber en la caja.
     * Fórmula: Fondo Inicial + Ventas en Efectivo - Egresos/Retiros en Efectivo.
     * @param CajaDiaria $caja
     * @return float
     */
    public function calcularMontoTeorico(CajaDiaria $caja): float
    {
        $fondoInicial = $caja->fondo_inicial;

        // 1. Sumar las ventas en efectivo (asumiendo que 'efectivo' es el método de pago)
        $ventasEfectivo = $caja->ventas()
            ->where('metodo_pago', 'efectivo')
            ->sum(DB::raw('total')); // Asegúrate de que esta columna sea el total de la venta

        // 2. Restar Egresos / Sumar Ingresos manuales (Opcional, si tienes tabla de movimientos)
        // Por ahora, solo usamos el fondo inicial y las ventas.
        // Si no usas descuentos a nivel de venta, usa 'subtotal'
        // Si usas descuentos, usa el 'total_a_pagar' después de impuestos y descuentos.

        // NOTA: Si necesitas considerar retiros o gastos manuales, deberías incluirlos aquí:
        // $egresosEfectivo = $caja->movimientos()->where('tipo', 'egreso')->sum('monto');
        // $ingresosEfectivo = $caja->movimientos()->where('tipo', 'ingreso')->sum('monto');
        // $totalTeorico = $fondoInicial + $ventasEfectivo + $ingresosEfectivo - $egresosEfectivo;


        $totalTeorico = $fondoInicial + $ventasEfectivo;

        return (float) $totalTeorico;
    }
}