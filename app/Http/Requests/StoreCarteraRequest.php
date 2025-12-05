<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCarteraRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'venta_id' => 'required|exists:ventas,id|unique:carteras,venta_id',
            'cliente_id' => 'required|exists:clientes,id',
            'monto_original' => 'required|numeric|min:0',
            'monto_pendiente' => 'required|numeric|min:0',
            'fecha_vencimiento' => 'nullable|date',
            'estado' => 'nullable|string|in:Pendiente,Pagada,Vencida',
        ];
    }
}
