<?php

namespace App\Modules\Ventas\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVentaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'cliente_id' => 'required|integer|exists:clientes,id', // Debe existir en la tabla 'clientes'
            'user_id' => [
                'required',
                'integer',
                'exists:users,id', // Debe existir en la tabla 'users'
                // 'unique:users' fue REMOVIDO: Un user_id debe ser ÚNICO en la tabla donde se guarda esta venta/registro.
                // Si es una clave foránea, DEBE existir, pero no necesita ser única.
            ],

            // --- Fechas ---
            'fecha_emision' => 'required|date|before_or_equal:today', // Fecha válida y no posterior a hoy

            // --- Valores Financieros ---
            'descuento' => 'nullable|numeric|min:0', // Opcional, pero si está, debe ser numérico y no negativo
            'impuestos' => 'required|numeric|min:0', // Requerido, numérico y no negativo
            'subtotal_venta' => 'required|numeric|min:0', // Requerido, numérico y no negativo
            'total_venta' => 'required|numeric|min:0|gte:subtotal_venta', // Requerido, numérico, no negativo y mayor o igual al subtotal
        ];
    }
}
