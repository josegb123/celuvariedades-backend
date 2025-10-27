<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMovimientoFinancieroRequest extends FormRequest
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
            'monto' => 'required|numeric',
            'tipo_movimiento_id' => 'required|exists:tipo_movimiento_financieros,id',
            'descripcion' => 'required|string',
            'fecha' => 'required|date',
            'venta_id' => 'nullable|exists:ventas,id',
            'user_id' => 'required|exists:users,id',
        ];
    }
}
