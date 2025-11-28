<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMovimientoFinancieroRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado a realizar esta solicitud.
     */
    public function authorize(): bool
    {
        // Asumiendo que se requiere autenticación para registrar movimientos financieros
        return auth()->check();
    }

    /**
     * Obtiene las reglas de validación que se aplican a la solicitud.
     */
    public function rules(): array
    {
        // Lista de nombres de tipos de movimiento válidos que pueden ser creados manualmente
        $tiposPermitidos = [
            'Ingreso Operacional Vario', // Manualmente un ingreso
            'Gasto Operacional Vario',  // Manualmente un egreso
            // 'Compra de Productos' podría ser manual, pero 'Venta de Productos' y 'Abono a Cartera' son automáticos.
        ];

        return [
            // CRÍTICO: Usamos el nombre del tipo para el servicio
            'tipo_movimiento_nombre' => [
                'required',
                'string',
                Rule::in($tiposPermitidos),
            ],
            // El monto siempre debe ser positivo
            'monto' => 'required|numeric|min:0.01',

            'metodo_pago' => [
                'required',
                'string',
                Rule::in(['efectivo', 'tarjeta', 'transferencia', 'cheque', 'otro']),
            ],

            // Opcional, pero útil para describir el gasto/ingreso
            'descripcion_adicional' => 'nullable|string|max:255',

            // Los campos referencia_tabla y referencia_id no son requeridos para movimientos manuales (varios).
            'referencia_tabla' => 'nullable|string|max:50',
            'referencia_id' => 'nullable|integer',
        ];
    }

    /**
     * Prepara los datos para la validación.
     * Agregamos el user_id del usuario autenticado.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'user_id' => auth()->id(),
        ]);
    }
}