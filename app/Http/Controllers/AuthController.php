<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'device_name' => 'required|string', // El cliente debe enviar un nombre (ej. 'movil_juan')
        ]);

        $user = User::where('email', $request->email)->first();

        // Verificar si el usuario existe y si la contraseña es correcta
        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'credenciales' => ['Las credenciales son incorrectas.'],
            ]);
        }
        // Revocar cualquier token existente para este dispositivo
        $user->tokens()->where('name', $request->device_name)->delete();

        // Generar un token único para este dispositivo
        $token = $user->createToken($request->device_name)->plainTextToken;

        return response()->json([
            'message' => 'Login exitoso',
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 200);
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed', // 'confirmed' busca 'password_confirmation' en el body
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password), // ¡Siempre hashear la contraseña!
            'role' => 'user', // Asignar un rol por defecto
        ]);

        // Generar y devolver el token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Usuario registrado exitosamente',
            'user' => $user->name,
            'access_token' => $token, // 👈 El cliente debe guardar esto
            'token_type' => 'Bearer',
        ], 201);
    }

    public function logout(Request $request)
    {
        // Revocar el token que se está usando actualmente para la solicitud
        $token = $request->user()->currentAccessToken();
        if ($token) {
            // borrar usando la relación tokens() para garantizar que delete() se invoque en el query builder/model adecuado
            $request->user()->tokens()->where('id', $token->id)->delete();
        }

        return response()->json([
            'message' => 'Cierre de sesión exitoso. Token revocado.'
        ], 200);
    }

    public function user(Request $request)
    {
        return response()->json($request->user());
    }

    // TODO: Create new function for register user
}
