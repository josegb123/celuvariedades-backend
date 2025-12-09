<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDevolucionRequest extends FormRequest
{
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
            // 1. Datos de la Venta y Financieros
            'venta_id' => ['required', 'integer', 'exists:ventas,id'],
            'metodo_reembolso' => ['nullable', 'string', 'max:50', 'in:Efectivo,Transferencia,Tarjeta,Otro'], // Requerido para el egreso financiero

            // 2. Colección de Ítems a Devolver
            'items_devueltos' => ['required', 'array', 'min:1'],

            // Reglas para cada ítem devuelto
            'items_devueltos.*.detalle_venta_id' => [
                'required',
                'integer',
                'exists:detalle_ventas,id' // Asegura que la línea de la venta exista
            ],

            'items_devueltos.*.cantidad' => [
                'required',
                'numeric',
                'min:0.01' // Debe ser mayor que cero
                // Nota: La validación de que la cantidad no exceda la pendiente se hace en el SERVICE.
            ],

            'items_devueltos.*.motivo' => ['required', 'string', 'max:255'],
            'items_devueltos.*.notas' => ['nullable', 'string', 'max:500'],

            // ❌ Se eliminan: cliente_id (obtenido de Venta)
            // ❌ Se eliminan: producto_id, id_unico_producto (reemplazados por detalle_venta_id)
            // ❌ Se eliminan: costo_unitario (obtenido de detalle_venta)
        ];
    }

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

            'metodo_reembolso.string' => 'El método de reembolso debe ser una cadena de texto.',
            'metodo_reembolso.in' => 'El método de reembolso no es válido.',

            'items_devueltos.required' => 'Debe especificar al menos un ítem de venta para la devolución.',
            'items_devueltos.array' => 'El formato de los ítems devueltos no es válido.',
            'items_devueltos.min' => 'Debe incluir al menos un ítem en la lista de devolución.',

            // ERRORES DE PRODUCTOS INDIVIDUALES (items_devueltos.*)

            // detalle_venta_id
            'items_devueltos.*.detalle_venta_id.required' => 'El ID del detalle de venta es obligatorio para todos los ítems de la devolución.',
            'items_devueltos.*.detalle_venta_id.integer' => 'El ID del detalle de venta debe ser un número entero.',
            'items_devueltos.*.detalle_venta_id.exists' => 'El detalle de venta con el ID especificado no existe o no corresponde a una venta activa.',

            // cantidad
            'items_devueltos.*.cantidad.required' => 'La cantidad a devolver es obligatoria.',
            'items_devueltos.*.cantidad.numeric' => 'La cantidad debe ser un valor numérico válido (puede ser decimal).',
            'items_devueltos.*.cantidad.min' => 'La cantidad mínima a devolver debe ser mayor que cero.',

            // motivo
            'items_devueltos.*.motivo.required' => 'Debe especificar un motivo para la devolución del producto.',
            'items_devueltos.*.motivo.string' => 'El motivo debe ser texto.',
            'items_devueltos.*.motivo.max' => 'El motivo de la devolución no debe exceder los 255 caracteres.',

            // notas
            'items_devueltos.*.notas.string' => 'Las notas deben ser texto.',
            'items_devueltos.*.notas.max' => 'Las notas no deben exceder los 500 caracteres.',
        ];
    }
}