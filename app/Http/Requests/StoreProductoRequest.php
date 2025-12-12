<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreProductoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::user()->role === 'admin';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'id' => 'nullable|integer|min:1',
            'categoria_id' => ['sometimes', 'required', 'exists:categorias,id'],
            'user_id' => ['sometimes', 'required', 'exists:users,id'],
            'codigo_barra' => 'nullable|string|max:255|unique:productos,codigo_barra',
            'nombre' => ['sometimes', 'required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'imagen' => ['sometimes', 'nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg,webp', 'max:2048'],
            'imagen_url' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'precio_compra' => ['sometimes', 'required', 'numeric', 'min:0'],
            'precio_venta' => ['sometimes', 'required', 'numeric', 'min:0'],
            'stock_actual' => ['sometimes', 'required', 'integer', 'min:0'],
            'stock_reservado' => ['sometimes', 'required', 'integer', 'min:0'],
            'stock_minimo' => ['sometimes', 'required', 'integer', 'min:0'],
            'proveedores' => 'nullable|array',
            'proveedores.*' => 'exists:proveedores,id',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            // Categoria ID
            'categoria_id.required' => 'La categoría del producto es obligatoria.',
            'categoria_id.exists' => 'La categoría seleccionada no es válida.',

            // User ID
            'user_id.required' => 'El ID del usuario es obligatorio.',
            'user_id.exists' => 'El ID de usuario seleccionado no existe.',

            // Código de Barra
            'codigo_barra.unique' => 'Este código de barras ya ha sido registrado en otro producto.',
            'codigo_barra.max' => 'El código de barras no debe exceder los :max caracteres.',

            // Nombre
            'nombre.required' => 'El nombre del producto es obligatorio.',
            'nombre.string' => 'El nombre debe ser una cadena de texto.',
            'nombre.max' => 'El nombre del producto no debe exceder los :max caracteres.',

            // Descripción
            'descripcion.string' => 'La descripción debe ser una cadena de texto.',

            // Imagen (Archivo)
            'imagen.image' => 'El archivo debe ser una imagen válida (jpeg, png, etc.).',
            'imagen.mimes' => 'El formato de imagen no es compatible. Use jpeg, png, jpg, gif, svg o webp.',
            'imagen.max' => 'El tamaño de la imagen no debe exceder los 2MB.',

            // Imagen URL
            'imagen_url.string' => 'La URL de la imagen debe ser una cadena de texto.',
            'imagen_url.max' => 'La URL de la imagen es demasiado larga.',

            // Precio Compra
            'precio_compra.required' => 'El precio de compra es obligatorio.',
            'precio_compra.numeric' => 'El precio de compra debe ser un número.',
            'precio_compra.min' => 'El precio de compra no puede ser negativo.',

            // Precio Venta
            'precio_venta.required' => 'El precio de venta es obligatorio.',
            'precio_venta.numeric' => 'El precio de venta debe ser un número.',
            'precio_venta.min' => 'El precio de venta no puede ser negativo.',

            // Stock
            'stock_actual.required' => 'El stock actual es obligatorio.',
            'stock_actual.integer' => 'El stock actual debe ser un número entero.',
            'stock_actual.min' => 'El stock actual no puede ser negativo.',

            'stock_reservado.required' => 'El stock reservado es obligatorio.',
            'stock_reservado.integer' => 'El stock reservado debe ser un número entero.',
            'stock_reservado.min' => 'El stock reservado no puede ser negativo.',

            'stock_minimo.required' => 'El stock mínimo es obligatorio.',
            'stock_minimo.integer' => 'El stock mínimo debe ser un número entero.',
            'stock_minimo.min' => 'El stock mínimo no puede ser negativo.',

            // Proveedores (Array de IDs)
            'proveedores.array' => 'Los proveedores deben ser proporcionados como una lista.',
            'proveedores.*.exists' => 'Uno de los IDs de proveedor seleccionados no es válido.',
        ];
    }
}
