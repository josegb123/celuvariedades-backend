<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\CuentaPorCobrar;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AvalController extends Controller
{
    /**
     * Check if a given aval (client) has pending dues.
     *
     * @param int $id The ID of the client acting as an aval.
     * @return JsonResponse
     */
    public function hasPendingDues(int $id): JsonResponse
    {
        try {
            // Validate that the client (aval) exists
            $cliente = Cliente::find($id);

            if (!$cliente) {
                return response()->json(['message' => 'Aval (Cliente) no encontrado.'], 404);
            }

            // Check for pending dues where the client is the customer
            $hasPendingDues = CuentaPorCobrar::where('cliente_id', $id)
                                            ->whereIn('estado', ['Pendiente', 'Vencida'])
                                            ->exists();

            return response()->json(['has_pending_dues' => $hasPendingDues]);

        } catch (\Throwable $th) {
            // Log the error for debugging
            \Log::error("Error checking pending dues for aval ID {$id}: " . $th->getMessage());

            return response()->json(['message' => 'Error interno del servidor al verificar aval.', 'error' => $th->getMessage()], 500);
        }
    }
}
