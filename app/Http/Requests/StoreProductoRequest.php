<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductoRequest extends FormRequest
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
            'categoria_id' => 'required|exists:categorias,id',
            'user_id' => 'required|exists:users,id', // Assuming user_id is passed in the request, might be set automatically in controller
            'codigo_barra' => 'nullable|string|max:255|unique:productos,codigo_barra',
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'precio_compra' => 'required|numeric|min:0',
            'precio_venta' => 'required|numeric|min:0',
            'stock_actual' => 'required|integer|min:0',
            'stock_reservado' => 'required|integer|min:0',
            'stock_minimo' => 'required|integer|min:0',
        ];
    }
}
