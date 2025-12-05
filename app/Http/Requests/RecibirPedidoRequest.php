<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecibirPedidoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check(); // Only authenticated users can receive orders
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'numero_factura_proveedor' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('pedido_proveedores', 'numero_factura_proveedor'),
            ],
            'fecha_entrega' => ['required', 'date'],
            'user_id' => ['required', 'exists:users,id'], // User who received the order
            'proveedor_id' => ['required', 'exists:proveedores,id'],
            'monto_total' => ['required', 'numeric', 'min:0'],
            'productos' => ['required', 'array', 'min:1'],
            'productos.*.producto_id' => ['required', 'exists:productos,id'],
            'productos.*.cantidad' => ['required', 'numeric', 'min:0.01'],
            'productos.*.precio_compra' => ['required', 'numeric', 'min:0'],
        ];
    }

    /**
     * Prepare the data for validation.
     * Add the authenticated user's ID if not already present.
     */
    protected function prepareForValidation()
    {
        if (!$this->has('user_id')) {
            $this->merge([
                'user_id' => $this->user()->id,
            ]);
        }
    }
}
