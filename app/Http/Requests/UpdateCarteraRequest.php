<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCarteraRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {

        return [
            'monto_original' => 'sometimes|required|numeric|min:0',
            'monto_pendiente' => 'sometimes|required|numeric|min:0',
            'fecha_vencimiento' => 'sometimes|nullable|date',
            'estado' => 'sometimes|nullable|string|in:Pendiente,Pagada,Vencida',
        ];
    }
}
