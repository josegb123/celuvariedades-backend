<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
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
        // NOTA: Para obtener el ID del usuario que se está actualizando, 
        // asumimos que el ID viene en la ruta como {user}.
        $userId = $this->route('user');

        return [
            // El nombre sigue siendo obligatorio.
            'name' => ['required', 'string', 'max:255'],

            // El email es obligatorio y único, PERO IGNORA al usuario actual.
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],

            // La contraseña es OPCIONAL. Se aplica la validación SÓLO si se proporciona el campo.
            'password' => [
                'nullable', // Permite que el campo esté vacío
                'string',
                Password::min(8)->letters()->numbers(),
                'confirmed',
            ],

            // El rol sigue siendo obligatorio.
            'role' => ['required', 'string', 'max:50'],
        ];
    }
}
