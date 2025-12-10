<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreDevolucionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::user()->role === 'admin';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'venta_id' => ['required', 'integer', 'exists:ventas,id'],

            // REGLA CORREGIDA
            'metodo_reembolso' => [
                'required',
                'string',
                'max:50',
                // Incluye los métodos de caja y SaldoCliente
                'in:Efectivo,Transferencia,Tarjeta,SaldoCliente',
            ],

            'items_devueltos' => ['required', 'array', 'min:1'],
            'items_devueltos.*.detalle_venta_id' => ['required', 'integer', 'exists:detalle_ventas,id'],
            'items_devueltos.*.cantidad' => ['required', 'numeric', 'min:0.01'],
            'items_devueltos.*.motivo' => ['required', 'string', 'max:255'],
            'items_devueltos.*.notas' => ['nullable', 'string', 'max:255'],
        ];
    }
}