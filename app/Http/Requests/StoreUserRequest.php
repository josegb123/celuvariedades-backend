<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = Auth::user();
        return $user && ($user->role === 'administrador' || $user->role === 'admin' || $user->role === 'vendedor');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // El nombre es obligatorio y debe ser una cadena.
            'name' => ['required', 'string', 'max:255'],

            // El email es obligatorio, debe ser un email válido y único en la tabla 'users'.
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],

            // La contraseña es obligatoria en la creación y debe cumplir con una robustez mínima.
            'password' => [
                'required',
                'string',
                // Puedes ajustar las reglas de robustez aquí:
                Password::min(8)->letters()->numbers(),
                'confirmed'
            ], // Requiere el campo password_confirmation

            // El rol es obligatorio y debe ser una cadena (ej. 'admin', 'seller').
            'role' => ['required', 'string', 'max:50'],
        ];
    }
}
