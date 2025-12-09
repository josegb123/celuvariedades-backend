<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth; // Añadido

class RecibirPedidoRequest extends FormRequest
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
            'productos.*.precio_venta' => ['required', 'numeric', 'min:0'],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'numero_factura_proveedor.unique' => 'El número de factura de proveedor ingresado ya existe en nuestros registros.',
            'numero_factura_proveedor.max' => 'El número de factura no puede superar los :max caracteres.',

            'fecha_entrega.required' => 'La fecha de entrega del pedido es obligatoria.',
            'fecha_entrega.date' => 'La fecha de entrega debe ser un formato de fecha válido.',

            'user_id.required' => 'El identificador del usuario es obligatorio.',
            'user_id.exists' => 'El usuario que recibe el pedido no es válido.',

            'proveedor_id.required' => 'Debe seleccionar un proveedor para este pedido.',
            'proveedor_id.exists' => 'El proveedor seleccionado no existe.',

            'monto_total.required' => 'El monto total del pedido es obligatorio.',
            'monto_total.numeric' => 'El monto total debe ser un valor numérico.',
            'monto_total.min' => 'El monto total debe ser mayor o igual a :min.',

            'productos.required' => 'Debe incluir al menos un producto en el pedido.',
            'productos.array' => 'El formato de los productos debe ser una lista válida.',
            'productos.min' => 'El pedido debe contener al menos :min producto.',

            // Mensajes para los detalles de cada producto (productos.*)
            'productos.*.producto_id.required' => 'El identificador del producto es obligatorio para todos los ítems.',
            'productos.*.producto_id.exists' => 'Uno de los productos seleccionados no existe en el inventario.',

            'productos.*.cantidad.required' => 'La cantidad es obligatoria para cada producto.',
            'productos.*.cantidad.numeric' => 'La cantidad debe ser un valor numérico.',
            'productos.*.cantidad.min' => 'La cantidad de producto debe ser mayor a cero.',

            'productos.*.precio_compra.required' => 'El precio de compra es obligatorio para cada producto.',
            'productos.*.precio_compra.numeric' => 'El precio de compra debe ser un valor numérico.',
            'productos.*.precio_compra.min' => 'El precio de compra debe ser mayor o igual a :min.',

            'productos.*.precio_venta.required' => 'El precio de venta es obligatorio para cada producto.',
            'productos.*.precio_venta.numeric' => 'El precio de venta debe ser un valor numérico.',
            'productos.*.precio_venta.min' => 'El precio de venta debe ser mayor o igual a :min.',
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