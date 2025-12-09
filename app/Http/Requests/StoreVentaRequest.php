<?php

// app/Http/Requests/VentaStoreRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth; // Add this line

class StoreVentaRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado a realizar esta solicitud.
     */
    public function authorize(): bool
    {
        $user = Auth::user(); // Add this line
        return $user && ($user->role === 'administrador' || $user->role === 'admin' || $user->role === 'vendedor'); // Modify this line
    }

    /**
     * Obtiene las reglas de validación que se aplican a la solicitud.
     */
    public function rules(): array
    {
        return [
            // --- Cabecera de la Venta ---
            // cliente_id: Puede ser nulo si es una venta POS a consumidor final (C/F).
            'cliente_id' => 'nullable|exists:clientes,id',

            // tipo_venta_id: CRÍTICO. Debe existir en una tabla de catálogo (Contado, Crédito, etc.)
            'tipo_venta_id' => 'required|exists:tipo_ventas,id',

            // DescuentoTotal: Se recibe para ser aplicado en el servicio.
            'descuento_total' => 'nullable|numeric|min:0',
            'abono_inicial' => 'nullable|numeric|min:0',
            // ID de la Caja Diaria: Opcional, pero si se envía, debe existir.
            // --- REGLA CRÍTICA DE CAJA ---
            'caja_diaria_id' => [
                'nullable',
                // Es REQUERIDO si el método de pago es 'efectivo'
                Rule::requiredIf($this->input('metodo_pago') === 'efectivo'),
                // Debe existir en la tabla de cajas diarias
                'exists:cajas_diarias,id',
            ],

            // Método de Pago: Requerido si el estado es 'finalizada' (o se asume que será crédito).
            'metodo_pago' => [
                'nullable',
                'string',
                Rule::in(['efectivo', 'tarjeta', 'transferencia', 'credito', 'plan_separe']),
            ],

            // El estado por defecto será PENDIENTE_PAGO o FINALIZADA, el servicio lo manejará.
            'estado' => 'nullable|string|in:finalizada,cancelada,pendiente_pago,reembolsada',

            // IVA: Se recibe si se permite la modificación manual de la tasa de IVA base.
            'iva_porcentaje' => 'nullable|numeric|min:0|max:100',


            // --- Ítems de la Venta (DetalleVenta) ---
            'items' => 'required|array|min:1',
            // Validación de cada ítem anidado
            'items.*.producto_id' => 'required|integer|exists:productos,id',
            'items.*.cantidad' => 'required|numeric|min:0.01', // La cantidad puede ser decimal (ej. peso)
            // Permitimos descuentos por ítem, aunque se calcule en el servicio.
            'items.*.descuento' => 'nullable|numeric|min:0',

            // Precio Unitario: Opcional. Si no se envía, se toma el precio del producto,
            // pero si se envía, se permite modificarlo (útil para ofertas o cambios manuales).
            'items.*.precio_unitario' => 'nullable|numeric|min:0',
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
            'items.*.producto_id.exists' => 'El ID del producto (:input) en el ítem no existe.',
            'items.*.cantidad.required' => 'Debe especificar la cantidad para todos los ítems.',
            'items.*.cantidad.min' => 'La cantidad de cada producto debe ser mayor a cero (0.01).',
        ];
    }
}