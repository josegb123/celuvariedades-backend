<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class StoreAbonoRequest extends FormRequest
{
  public function authorize(): bool
  {
    $user = Auth::user();
    return $user && ($user->role === 'administrador' || $user->role === 'admin' || $user->role === 'vendedor');
  }

  public function rules(): array
  {
    return [
      'cuenta_por_cobrar_id' => [
        'required',
        'integer',
        // Asegura que la cuenta exista y no esté ya pagada o anulada
        Rule::exists('cuentas_por_cobrar', 'id')->where(function ($query) {
          $query->whereIn('estado', ['Pendiente', 'Vencida']);
        }),
      ],
      'caja_diaria_id' => 'nullable|integer',
      'monto' => 'required|numeric|min:1',
      'metodo_pago' => [
        'required',
        'string',
        Rule::in(['efectivo', 'tarjeta', 'transferencia', 'cheque', 'otro']),
      ],

      'referencia_pago' => 'nullable|string|max:100', //referencia externa de otro sistema de pago, como nequi o transferencia bancaria, con el fin de trazar el origen del pago
    ];
  }

  public function messages(): array
  {
    return [
      'cuenta_por_cobrar_id.exists' => 'La cuenta por cobrar especificada no existe o ya ha sido saldada/anulada.',
      'monto.min' => 'El monto del abono debe ser superior a cero.',
    ];
  }
}