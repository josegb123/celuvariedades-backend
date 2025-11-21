<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductoRequest extends FormRequest
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
            'categoria_id' => 'sometimes|required|exists:categorias,id',
            'user_id' => 'sometimes|required|exists:users,id',
            'codigo_barra' => 'nullable|string|max:255|unique:productos,codigo_barra,' . $this->route('producto'),
            'nombre' => 'sometimes|required|string|max:255',
            'descripcion' => 'nullable|string',
            'imagen' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'imagen_url' => 'nullable|string',
            'precio_compra' => 'sometimes|required|numeric|min:0',
            'precio_venta' => 'sometimes|required|numeric|min:0',
            'stock_actual' => 'sometimes|required|integer|min:0',
            'stock_reservado' => 'sometimes|required|integer|min:0',
            'stock_minimo' => 'sometimes|required|integer|min:0',
        ];
    }
}
