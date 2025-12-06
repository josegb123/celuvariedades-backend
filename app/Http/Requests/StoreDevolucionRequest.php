<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDevolucionRequest extends FormRequest
{

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages()
    {
        return [
            // ERRORES GLOBALES
            'venta_id.required' => 'El campo ID de la venta es obligatorio.',
            'venta_id.integer' => 'El ID de la venta debe ser un número entero.',
            'venta_id.exists' => 'La Venta seleccionada no existe en el sistema.',

            'productos_devueltos.required' => 'Debe especificar al menos un producto para la devolución.',
            'productos_devueltos.array' => 'El formato de los productos devueltos no es válido.',
            'productos_devueltos.min' => 'Debe incluir al menos un producto en la lista de devolución.',

            'cliente_id.required' => 'El campo ID del cliente es obligatorio.',
            'cliente_id.integer' => 'El ID del cliente debe ser un número entero.',
            'cliente_id.exists' => 'El Cliente seleccionado no existe en el sistema.',

            // ERRORES DE PRODUCTOS INDIVIDUALES (productos_devueltos.*)

            // producto_id
            'productos_devueltos.*.producto_id.required' => 'El ID del producto es obligatorio para todos los ítems de la devolución.',
            'productos_devueltos.*.producto_id.integer' => 'El ID del producto debe ser un número entero.',
            'productos_devueltos.*.producto_id.exists' => 'El producto con el ID especificado no existe en el catálogo.',

            // id_unico_producto (Tu mensaje original)
            'productos_devueltos.*.id_unico_producto.required' => 'El código de identificación único del producto (ID único) es obligatorio.',
            'productos_devueltos.*.id_unico_producto.string' => 'El código de identificación único debe ser texto.',
            'productos_devueltos.*.id_unico_producto.max' => 'El código de identificación único no debe exceder los 255 caracteres.',
            'productos_devueltos.*.id_unico_producto.unique' => 'El producto identificado con **:input** ya ha sido registrado en una devolución. Una unidad de producto solo puede devolverse una vez.',

            // cantidad
            'productos_devueltos.*.cantidad.required' => 'La cantidad a devolver es obligatoria.',
            'productos_devueltos.*.cantidad.integer' => 'La cantidad debe ser un número entero.',
            'productos_devueltos.*.cantidad.min' => 'La cantidad mínima a devolver es 1.',
            'productos_devueltos.*.cantidad.max' => 'Solo se puede devolver 1 unidad de cada código de producto único a la vez.',

            // motivo
            'productos_devueltos.*.motivo.required' => 'Debe especificar un motivo para la devolución del producto.',
            'productos_devueltos.*.motivo.string' => 'El motivo debe ser texto.',
            'productos_devueltos.*.motivo.max' => 'El motivo de la devolución no debe exceder los 255 caracteres.',

            // costo_unitario
            'productos_devueltos.*.costo_unitario.required' => 'El costo unitario del producto es obligatorio para calcular el reembolso.',
            'productos_devueltos.*.costo_unitario.numeric' => 'El costo unitario debe ser un valor numérico.',
            'productos_devueltos.*.costo_unitario.min' => 'El costo unitario no puede ser negativo.',

            // notas
            'productos_devueltos.*.notas.string' => 'Las notas deben ser texto.',
            'productos_devueltos.*.notas.max' => 'Las notas no deben exceder los 500 caracteres.',
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
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
            'venta_id' => ['required', 'integer', 'exists:ventas,id'],
            'productos_devueltos' => ['required', 'array', 'min:1'],
            'cliente_id' => ['required', 'integer', 'exists:clientes,id'],
            'productos_devueltos.*.producto_id' => ['required', 'integer', 'exists:productos,id'],
            'productos_devueltos.*.id_unico_producto' => ['required', 'string', 'max:255', 'unique:devoluciones,id_unico_producto'],
            'productos_devueltos.*.cantidad' => ['required', 'integer', 'min:1', 'max:1'], // Always 1 per unique product
            'productos_devueltos.*.motivo' => ['required', 'string', 'max:255'],
            'productos_devueltos.*.costo_unitario' => ['required', 'numeric', 'min:0'],
            'productos_devueltos.*.notas' => ['nullable', 'string', 'max:500'],
        ];
    }
}
