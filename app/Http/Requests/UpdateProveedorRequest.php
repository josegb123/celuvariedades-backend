<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProveedorRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado a realizar esta solicitud.
     */
    public function authorize(): bool
    {
        // Asume que solo usuarios autenticados pueden actualizar proveedores
        return auth()->check();
    }

    /**
     * Obtiene las reglas de validación que se aplican a la solicitud.
     */
    public function rules(): array
    {
        $proveedorId = $this->route('proveedor');


        return [
            'nombre_comercial' => [
                'required',
                'string',
                'max:150',
                // Excluye el registro actual de la comprobación de unicidad
                Rule::unique('proveedores', 'nombre_comercial')->ignore($proveedorId)
            ],
            'nombre_contacto' => ['nullable', 'string', 'max:100'],
            'identificacion' => [
                'nullable',
                'string',
                'max:20',
                // Excluye el registro actual de la comprobación de unicidad
                Rule::unique('proveedores', 'identificacion')->ignore($proveedorId)
            ],
            'telefono' => ['nullable', 'string', 'max:20'],
            'email' => [
                'nullable',
                'email',
                'max:100',
                // Excluye el registro actual de la comprobación de unicidad
                Rule::unique('proveedores', 'email')->ignore($proveedorId)
            ],
            'direccion' => ['nullable', 'string', 'max:255'],
            'ciudad' => ['nullable', 'string', 'max:100'],
            'notas' => ['nullable', 'string'],
            'activo' => ['boolean'],
        ];
    }
}