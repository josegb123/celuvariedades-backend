<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVentaRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado a realizar esta solicitud.
     */
    public function authorize(): bool
    {
        // Verificar que el usuario esté autenticado.
        return auth()->check();
    }

    /**
     * Obtiene las reglas de validación que se aplican a la solicitud.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // El 'sometimes' asegura que solo se valide si el campo está presente en el request.

            // --- Campos de Control y Estado ---
            'estado' => [
                'sometimes',
                'string',
                Rule::in(['finalizada', 'cancelada', 'pendiente_pago', 'reembolsada', 'anulada']),
                // Añadí 'anulada' como posible estado de control para ser más robusto.
            ],

            'metodo_pago' => [
                'sometimes',
                'nullable', // Puede ser nulo si el estado es 'pendiente_pago'
                'string',
                Rule::in(['efectivo', 'tarjeta', 'transferencia', 'credito', 'plan_separe', 'otro']),
            ],

            // --- Campos Modificables ---            
            'iva_porcentaje' => 'sometimes|nullable|numeric|min:0|max:100',
            'descuento_total' => 'sometimes|nullable|numeric|min:0',

            // Importante: Los IDs o totales transaccionales (subtotal, total, items) NO deben
            // ser actualizables vía PUT/PATCH, ya que representan un registro histórico.

            // Puedes agregar 'cliente_id' si se permite cambiar el cliente después de la venta.
            // 'cliente_id' => 'sometimes|nullable|exists:clientes,id', 
        ];
    }
}