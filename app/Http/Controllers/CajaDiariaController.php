<?php

namespace App\Http\Controllers;

use App\Models\CajaDiaria;
use App\Services\CajaDiariaService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Exception;
use Illuminate\Support\Facades\Auth;

class CajaDiariaController extends Controller
{
    protected $cajaDiariaService;

    // Inyección de dependencias del servicio
    public function __construct(CajaDiariaService $cajaDiariaService)
    {
        $this->cajaDiariaService = $cajaDiariaService;
    }

    /**
     * [ENDPOINT] GET /api/cajas/activa
     * Obtiene la sesión de caja abierta para el usuario actual.
     */
    public function getCajaActiva()
    {
        $userId = Auth::id();
        $cajaActiva = $this->cajaDiariaService->obtenerCajaActiva($userId);

        if (!$cajaActiva) {
            return response()->json([
                'message' => 'No hay una sesión de caja abierta para este usuario.',
                'caja' => null
            ], 200); // 200 OK, pero con mensaje de no encontrada
        }

        // Se puede cargar la relación 'ventas' para mostrar estadísticas en tiempo real
        // $cajaActiva->load(['ventas']);

        return response()->json([
            'message' => 'Sesión de caja activa encontrada.',
            'caja' => $cajaActiva
        ], 200);
    }

    /**
     * [ENDPOINT] POST /api/cajas/apertura
     * Abre una nueva sesión de caja registrando el fondo inicial.
     */
    public function abrirCaja(Request $request)
    {
        try {
            // Validación de entrada
            $request->validate([
                'fondo_inicial' => ['required', 'numeric', 'min:0'],
            ]);

            $userId = Auth::id();
            $fondoInicial = $request->input('fondo_inicial');

            $caja = $this->cajaDiariaService->abrirCaja($userId, $fondoInicial);

            return response()->json([
                'message' => 'Caja abierta con éxito.',
                'caja' => $caja
            ], 201); // 201 Creado
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Error de validación.', 'errors' => $e->errors()], 422);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 409); // 409 Conflicto
        }
    }

    /**
     * [ENDPOINT] POST /api/cajas/{cajaDiaria}/cierre
     * Cierra la sesión de caja, calcula el teórico y registra el físico.
     * Utiliza la inyección de modelo (Route Model Binding).
     */
    public function cerrarCaja(Request $request, CajaDiaria $cajaDiaria)
    {
        try {
            // Validación de entrada
            $request->validate([
                'monto_cierre_fisico' => ['required', 'numeric', 'min:0'],
            ]);

            // Validación de Permiso: Asegurar que solo el propietario pueda cerrar su caja
            if ($cajaDiaria->user_id !== Auth::id()) {
                return response()->json(['message' => 'No tienes permiso para cerrar esta caja.'], 403);
            }

            $montoCierreFisico = $request->input('monto_cierre_fisico');

            $cajaCerrada = $this->cajaDiariaService->cerrarCaja($cajaDiaria, $montoCierreFisico);

            // Retornar información detallada del cierre para el reporte
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
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Error de validación.', 'errors' => $e->errors()], 422);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        }
    }
}