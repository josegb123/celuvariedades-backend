<?php

namespace App\Http\Controllers;

use App\Http\Requests\AbrirCajaRequest;
use App\Http\Requests\CerrarCajaRequest;
use App\Models\CajaDiaria;
use App\Services\CajaDiariaService;
use Exception;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class CajaDiariaController extends Controller
{
    protected $cajaDiariaService;

    public function __construct(CajaDiariaService $cajaDiariaService)
    {
        $this->cajaDiariaService = $cajaDiariaService;
    }

    /**
     * [ENDPOINT] GET /api/cajas/activa
     * Verifica si existe una caja abierta. Si la caja pertenece a una fecha anterior,
     * retorna un mensaje específico para que el frontend fuerce el proceso de arqueo.
     */
    public function getCajaActiva()
    {
        $userId = Auth::id();
        $cajaActiva = $this->cajaDiariaService->obtenerCajaActiva($userId);

        if ($cajaActiva && $cajaActiva->created_at->lt(Carbon::today())) {
            return response()->json([
                'message' => 'Existe una sesión de caja pendiente de cierre de una fecha anterior.',
                'requiere_cierre_manual' => true,
                'fecha_pendiente' => $cajaActiva->created_at->format('Y-m-d'),
                'caja' => $cajaActiva
            ], 200); // 426 Upgrade Required (o 200 con bandera) para indicar cambio de estado
        }

        if (!$cajaActiva) {
            return response()->json([
                'requiere_cierre_manual' => false,
                'message' => 'No hay una sesión de caja activa.',
                'caja' => null
            ], 200);
        }

        return response()->json([
            'requiere_cierre_manual' => false,
            'message' => 'Sesión de caja activa encontrada.',
            'caja' => $cajaActiva
        ], 200);
    }

    /**
     * [ENDPOINT] POST /api/cajas/apertura
     * Intenta abrir una nueva caja. Si hay una pendiente de días anteriores,
     * bloquea la operación hasta que se sanee la cartera diaria.
     */
    public function abrirCaja(AbrirCajaRequest $request)
    {
        try {
            $userId = Auth::id();

            $cajaPrevia = $this->cajaDiariaService->obtenerCajaActiva($userId);
            if ($cajaPrevia && $cajaPrevia->created_at->lt(Carbon::today())) {
                return response()->json([
                    'message' => 'No se puede abrir una nueva caja. Debe cerrar primero la sesión pendiente del ' . $cajaPrevia->created_at->format('Y-m-d')
                ], 409);
            }

            $caja = $this->cajaDiariaService->abrirCaja($userId, $request->input('fondo_inicial'));

            return response()->json([
                'message' => 'Caja abierta con éxito.',
                'caja' => $caja
            ], 201);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        }
    }

    /**
     * [ENDPOINT] POST /api/cajas/{cajaDiaria}/cierre
     * Cierra la sesión de caja proporcionada. Este método será invocado por el frontend
     * tanto para cierres normales como para cierres de fechas pendientes.
     */
    public function cerrarCaja(CerrarCajaRequest $request, CajaDiaria $cajaDiaria)
    {
        try {
            if ($cajaDiaria->user_id !== Auth::id()) {
                return response()->json(['message' => 'No tienes permiso para cerrar esta caja.'], 403);
            }

            $cajaCerrada = $this->cajaDiariaService->cerrarCaja($cajaDiaria, $request->input('monto_cierre_fisico'));

            return response()->json([
                'message' => 'Caja cerrada y arqueada con éxito.',
                'reporte' => [
                    'id' => $cajaCerrada->id,
                    'fondo_inicial' => $cajaCerrada->fondo_inicial,
                    'monto_teorico' => $cajaCerrada->monto_cierre_teorico,
                    'monto_fisico' => $cajaCerrada->monto_cierre_fisico,
                    'diferencia' => $cajaCerrada->diferencia,
                    'estado' => $cajaCerrada->estado,
                ]
            ], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        }
    }
}