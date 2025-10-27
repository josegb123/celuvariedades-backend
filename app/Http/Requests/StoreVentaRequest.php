<?php

// app/Http/Requests/VentaStoreRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVentaRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado a realizar esta solicitud.
     */
    public function authorize(): bool
    {
        // En una aplicación real, se verificaría aquí el rol (ej. Vendedor)
        return auth()->check();
    }

    /**
     * Obtiene las reglas de validación que se aplican a la solicitud.
     */
    public function rules(): array
    {
        return [
            // --- Cabecera de la Venta ---
            'cliente_id' => 'nullable|exists:clientes,id',
            'tipo_venta_id' => 'required|exists:tipos_ventas,id', // CRÍTICO para la lógica de Cartera/Inventario

            // Nota: subtotal, iva_monto y total se calculan en el servicio, no se reciben.
            'descuento_total' => 'nullable|numeric|min:0',

            'metodo_pago' => [
                'nullable',
                'string',
                Rule::in(['efectivo', 'tarjeta', 'transferencia', 'credito', 'plan_separe']),
            ],

            // --- Ítems de la Venta (DetalleVenta) ---
            'items' => 'required|array|min:1',
            'items.*.producto_id' => 'required|exists:productos,id',
            'items.*.cantidad' => 'required|integer|min:1',
            'fecha_emision' => 'nullable|date',
            'estado' => 'nullable|string|in:finalizada,cancelada,pendiente_pago,reembolsada',
            'iva_porcentaje' => 'nullable|numeric|min:0|max:100',
        ];
    }

    /**
     * Mensajes de error personalizados.
     */
    public function messages(): array
    {
        return [
            'tipo_venta_id.required' => 'Debe especificar el tipo de venta (Contado, Crédito, etc.).',
            'items.required' => 'La venta debe contener al menos un producto.',
            'items.*.producto_id.required' => 'Cada ítem requiere un producto válido.',
            'items.*.cantidad.min' => 'La cantidad de cada producto debe ser al menos 1.',
        ];
    }
}
