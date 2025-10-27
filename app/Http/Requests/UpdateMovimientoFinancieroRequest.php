<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMovimientoFinancieroRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'monto' => 'sometimes|numeric',
            'tipo_movimiento_id' => 'sometimes|exists:tipo_movimiento_financieros,id',
            'descripcion' => 'sometimes|string',
            'fecha' => 'sometimes|date',
            'venta_id' => 'sometimes|nullable|exists:ventas,id',
            'user_id' => 'sometimes|exists:users,id',
        ];
    }
}
