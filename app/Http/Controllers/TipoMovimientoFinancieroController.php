<?php

namespace App\Http\Controllers;

use App\Http\Resources\TipoMovimientoFinancieroResource;
use App\Models\TipoMovimientoFinanciero;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Exception;

class TipoMovimientoFinancieroController extends Controller
{
    /**
     * Muestra una lista de todos los tipos de movimiento financiero.
     * Es ideal para obtener listas no paginadas para selectores o filtros.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            // No usamos paginación, ya que se espera que sean pocas categorías.
            $tipos = TipoMovimientoFinanciero::orderBy('tipo')->orderBy('nombre')->get();

            return TipoMovimientoFinancieroResource::collection($tipos)->response()->setStatusCode(200);

        } catch (Exception $e) {
            return response()->json([
                'error' => 'Error al obtener los tipos de movimiento',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Almacena un nuevo tipo de movimiento financiero en la base de datos.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Anidar las reglas de validación (simulando un StoreRequest)
            $validator = Validator::make($request->all(), [
                'nombre' => ['required', 'string', 'max:100', 'unique:tipos_movimiento_financiero,nombre'],
                'descripcion' => ['nullable', 'string', 'max:255'],
                'tipo' => ['required', 'string', Rule::in(['Ingreso', 'Egreso'])],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => 'Error de validación',
                    'messages' => $validator->errors()
                ], 422);
            }

            $validatedData = $validator->validated();

            $tipoMovimiento = TipoMovimientoFinanciero::create([
                'nombre' => $validatedData['nombre'],
                'descripcion' => $validatedData['descripcion'] ?? null,
                'tipo' => $validatedData['tipo'],
            ]);

            return response()->json([
                'data' => $tipoMovimiento,
                'message' => 'Tipo de movimiento creado con éxito.'
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'error' => 'Error al crear el tipo de movimiento',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Muestra un tipo de movimiento financiero específico.
     * * @param TipoMovimientoFinanciero $tipoMovimientoFinanciero
     * @return JsonResponse
     */
    public function show(TipoMovimientoFinanciero $tipoMovimientoFinanciero): JsonResponse
    {
        return response()->json([
            'data' => $tipoMovimientoFinanciero
        ], 200);
    }

    /**
     * Actualiza un tipo de movimiento financiero existente.
     *
     * @param Request $request
     * @param TipoMovimientoFinanciero $tipoMovimientoFinanciero
     * @return JsonResponse
     */
    public function update(Request $request, TipoMovimientoFinanciero $tipoMovimientoFinanciero): JsonResponse
    {
        try {
            // Anidar las reglas de validación (simulando un UpdateRequest)
            $validator = Validator::make($request->all(), [
                // El nombre debe ser único, excluyendo el registro actual
                'nombre' => [
                    'required',
                    'string',
                    'max:100',
                    Rule::unique('tipos_movimiento_financiero')->ignore($tipoMovimientoFinanciero->id)
                ],
                'descripcion' => ['nullable', 'string', 'max:255'],
                'tipo' => ['required', 'string', Rule::in(['Ingreso', 'Egreso'])],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => 'Error de validación',
                    'messages' => $validator->errors()
                ], 422);
            }

            $validatedData = $validator->validated();

            $tipoMovimientoFinanciero->update([
                'nombre' => $validatedData['nombre'],
                'descripcion' => $validatedData['descripcion'] ?? $tipoMovimientoFinanciero->descripcion,
                'tipo' => $validatedData['tipo'],
            ]);

            return response()->json([
                'data' => $tipoMovimientoFinanciero,
                'message' => 'Tipo de movimiento actualizado con éxito.'
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'error' => 'Error al actualizar el tipo de movimiento',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Elimina un tipo de movimiento financiero.
     * * ⚠️ Se recomienda verificar si el tipo tiene movimientos asociados 
     * antes de permitir la eliminación.
     *
     * @param TipoMovimientoFinanciero $tipoMovimientoFinanciero
     * @return JsonResponse
     */
    public function destroy(TipoMovimientoFinanciero $tipoMovimientoFinanciero): JsonResponse
    {
        try {

            if ($tipoMovimientoFinanciero->movimientos()->exists()) {
                return response()->json([
                    'error' => 'No se puede eliminar',
                    'message' => 'Este tipo tiene movimientos registrados y no puede ser eliminado.'
                ], 409);
            }

            $tipoMovimientoFinanciero->delete();

            return response()->json([
                'message' => 'Tipo de movimiento eliminado con éxito.'
            ], 204); // 204 No Content para éxito sin contenido de respuesta

        } catch (Exception $e) {
            return response()->json([
                'error' => 'Error al eliminar el tipo de movimiento',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}