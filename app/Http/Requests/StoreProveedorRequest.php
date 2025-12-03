<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProveedorRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado a realizar esta solicitud.
     */
    public function authorize(): bool
    {
        // solo usuarios autenticados pueden crear proveedores
        return auth()->check();
    }

    /**
     * Obtiene las reglas de validación que se aplican a la solicitud.
     */
    public function rules(): array
    {
        return [
            'nombre_comercial' => ['required', 'string', 'max:150', 'unique:proveedores,nombre_comercial'],
            'nombre_contacto' => ['nullable', 'string', 'max:100'],
            'identificacion' => ['nullable', 'string', 'max:20', 'unique:proveedores,identificacion'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:100', 'unique:proveedores,email'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'ciudad' => ['nullable', 'string', 'max:100'],
            'notas' => ['nullable', 'string'],
            'activo' => ['boolean'],
        ];
    }
}