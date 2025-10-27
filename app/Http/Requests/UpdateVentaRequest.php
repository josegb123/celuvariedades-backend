<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVentaRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado a realizar esta solicitud.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Obtiene las reglas de validación que se aplican a la solicitud.
     */
    public function rules(): array
    {
        return [
            // Usamos 'sometimes' porque el campo es opcional en la actualización
            'estado' => [
                'sometimes',
                'string',
                Rule::in(['finalizada', 'cancelada', 'pendiente_pago', 'reembolsada']),
            ],

            'metodo_pago' => [
                'sometimes',
                'string',
                Rule::in(['efectivo', 'tarjeta', 'transferencia', 'credito', 'plan_separe']),
            ],
            'fecha_emision' => 'sometimes|nullable|date',
            'iva_porcentaje' => 'sometimes|nullable|numeric|min:0|max:100',
            'descuento_total' => 'sometimes|nullable|numeric|min:0',

            // No se permite actualizar items, totales o tipo_venta para mantener la integridad.
        ];
    }
}
