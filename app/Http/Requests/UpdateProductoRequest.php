<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule; // ⬅️ IMPORTAR LA CLASE RULE

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
        // Obtener el ID del producto que se está editando desde la ruta.
        $productId = $this->route('producto');

        return [
            'categoria_id' => ['sometimes', 'required', 'exists:categorias,id'],
            'user_id' => ['sometimes', 'required', 'exists:users,id'],

            // ⬅️ CORRECCIÓN CLAVE: Usar la clase Rule::unique
            'codigo_barra' => [
                'nullable',
                'string',
                'max:255',
                // Ignora el producto con el ID actual de la tabla 'productos'
                Rule::unique('productos', 'codigo_barra')->ignore($productId),
            ],

            'nombre' => ['sometimes', 'required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],

            // Usamos arrays para todas las reglas (más claro que la cadena |)
            'imagen' => ['sometimes', 'nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg,webp', 'max:2048'],

            'imagen_url' => ['nullable', 'string'],
            'precio_compra' => ['sometimes', 'required', 'numeric', 'min:0'],
            'precio_venta' => ['sometimes', 'required', 'numeric', 'min:0'],
            'stock_actual' => ['sometimes', 'required', 'integer', 'min:0'],
            'stock_reservado' => ['sometimes', 'required', 'integer', 'min:0'],
            'stock_minimo' => ['sometimes', 'required', 'integer', 'min:0'],
        ];
    }
}