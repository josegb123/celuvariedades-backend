<?php

namespace App\Modules\Cartera\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCarteraRequest extends FormRequest
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
            'cliente_id' => 'required|unique:clientes',
            'saldo' => 'required|decimal',
            'total_deuda' => 'required|decimal'
        ];
    }
}
