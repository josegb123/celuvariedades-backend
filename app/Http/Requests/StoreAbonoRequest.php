<?php

namespace App\Http\Requests;

use App\Models\CuentaPorCobrar;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAbonoRequest extends FormRequest
{
  /**
   * Determina si el usuario está autorizado a realizar esta solicitud.
   * Asume que el usuario debe estar autenticado para registrar un abono.
   */
  public function authorize(): bool
  {
    return auth()->check();
  }

  /**
   * Obtiene las reglas de validación que se aplican a la solicitud.
   */
  public function rules(): array
  {
    // El ID de la cuenta por cobrar viene de la URL (ruta anidada: {cuentaPorCobrar})
    // y se puede acceder a él a través de $this->route('cuentaPorCobrar').
    $cuentaPorCobrarId = $this->route('cuentaPorCobrar');

    return [
      'monto' => 'required|numeric|min:1', // El monto debe ser mayor a 0

      'metodo_pago' => [
        'required',
        'string',
        // Asegura que el método de pago sea uno de los permitidos
        Rule::in(['efectivo', 'tarjeta', 'transferencia', 'cheque', 'otro']),
      ],

      'referencia_pago' => 'nullable|string|max:100', // Ej: número de transferencia o recibo

      // CRÍTICO: Validación de la cuenta por cobrar en la URL
      'cuenta_por_cobrar_id' => [
        'required',
        'integer',
        // Asegura que la cuenta exista y no esté ya pagada o anulada
        Rule::exists('cuentas_por_cobrar', 'id')->where(function ($query) {
          $query->whereIn('estado', ['Pendiente', 'Vencida']);
        }),
      ],
    ];
  }

  /**
   * Prepara los datos para la validación.
   *
   * Agrega el ID de la CuentaPorCobrar desde la URL al cuerpo de la solicitud 
   * para que pueda ser validado por la regla de existencia.
   */
  protected function prepareForValidation(): void
  {
    // Fusionamos el ID que viene en la URL al cuerpo del request
    $this->merge([
      'cuenta_por_cobrar_id' => $this->route('cuentaPorCobrar'),
    ]);
  }

  /**
   * Personaliza los mensajes de validación.
   */
  public function messages(): array
  {
    return [
      'cuenta_por_cobrar_id.exists' => 'La cuenta por cobrar especificada no existe o ya ha sido saldada/anulada.',
      'monto.min' => 'El monto del abono debe ser superior a cero.',
    ];
  }
}