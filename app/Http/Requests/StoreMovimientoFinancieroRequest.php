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
        $tiposPermitidos = [
            'Venta de Productos',
            'Abono a Cartera',
            'Ingreso Operacional Vario',
            'Compra de Productos',
            'Gasto Operacional Vario',
            'Reembolso a Cliente'

        ];

        return [
            'tipo_movimiento_nombre' => [
                'required',
                'string',
                Rule::in($tiposPermitidos),
            ],
            'monto' => 'required|numeric|min:0.01',
            'metodo_pago' => [
                'required',
                'string',
                Rule::in(['efectivo', 'tarjeta', 'transferencia', 'cheque', 'otro']),
            ],

            'descripcion' => 'required|string|max:255',
            'user_id' => ['required', 'integer', 'exists:users,id'],
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