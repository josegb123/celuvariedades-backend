<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreProveedorRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado para hacer esta petición.
     *
     * @return bool
     */
    public function authorize(): bool
    {

        return Auth::user()->role === 'admin';
    }

    /**
     * Obtiene las reglas de validación que se aplican a la petición.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            // Campos obligatorios y únicos
            'nombre_comercial' => ['required', 'string', 'max:255', 'unique:proveedores,nombre_comercial'],
            'identificacion' => ['required', 'string', 'max:50', 'unique:proveedores,identificacion'],
            'email' => ['nullable', 'email', 'max:255', 'unique:proveedores,email'],

            // Campos obligatorios pero que pueden ser repetidos
            'nombre_contacto' => ['required', 'string', 'max:255'],
            'telefono' => ['required', 'string', 'max:20'],
            'direccion' => ['required', 'string', 'max:500'],
            'ciudad' => ['required', 'string', 'max:100'],

            // Campos opcionales
            'notas' => ['nullable', 'string'],

            // Campo de estado (debe ser booleano)
            'activo' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Obtiene los mensajes de error personalizados para las reglas de validación.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'required' => 'El campo :attribute es obligatorio.',
            'unique' => 'El :attribute ya ha sido registrado.',
            'email' => 'El :attribute debe ser un correo electrónico válido.',
            'string' => 'El campo :attribute debe ser texto.',
            'boolean' => 'El campo :attribute solo puede ser verdadero o falso.',
            'max' => 'El campo :attribute no puede tener más de :max caracteres.',
        ];
    }
}