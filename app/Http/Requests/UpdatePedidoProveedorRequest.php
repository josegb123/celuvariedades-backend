<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule; // Añadido

class UpdatePedidoProveedorRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'proveedor_id' => 'sometimes|required|exists:proveedores,id',
            'estado' => ['sometimes', 'required', 'string', Rule::in(['pendiente', 'recibido', 'cancelado'])],
            'productos' => 'sometimes|array|min:1',
            'productos.*.producto_id' => 'required|exists:productos,id',
            'productos.*.cantidad' => 'required|integer|min:1',
            'productos.*.precio_compra' => 'required|numeric|min:0',
            // Los campos como numero_factura_proveedor y fecha_entrega se manejarían en RecibirPedidoRequest
        ];
    }
}
