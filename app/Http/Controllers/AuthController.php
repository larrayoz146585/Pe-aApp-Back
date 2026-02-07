<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AuthController extends Controller
{
    // LOGIN: Ahora usamos 'name' en vez de 'email'
    public function login(Request $request){
        
        // 1. Validamos que nos envíen nombre y pass
        $request->validate([
            'name' => 'required|string',
            'password' => 'required|string'
        ]);

        // 2. Intentamos entrar buscando por columna 'name'
        // Auth::attempt es listo: si le pasas un array ['columna' => valor], busca por esa columna
        if(!Auth::attempt(['name' => $request->name, 'password' => $request->password])){
            return response()->json([
                'message' => 'Nombre o contraseña incorrectos'
            ], 401);
        }

        // 3. Si pasa, recuperamos el usuario
        $user = User::where('name', $request->name)->firstOrFail();

        // 4. Token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user
        ]);
    }

    // REGISTRO (Opcional, por si alguien nuevo se une en la fiesta)
    public function register(Request $request){
        
        $validatedData = $request->validate([
            'name' => 'required|string|max:255|unique:users', // El nombre no se puede repetir
            'password' => 'required|string|min:4' // Ponemos min 4 para que sea fácil (ej: 1234)
        ]);

        // TRUCO: Generamos un email falso automáticamente
        // Así el usuario no tiene que escribirlo, pero la Base de Datos se queda contenta

        $user = User::create([
            'name' => $validatedData['name'],
            'password' => Hash::make($validatedData['password']),
            'role' => 'cliente', // Por defecto cliente
            'saldo' => 0         // Empieza a cero
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => '¡Bienvenido a la fiesta!',
            'user' => $user,
            'access_token' => $token
        ], 201);
    }

    // ME y LOGOUT se quedan igual
    public function me(Request $request){
        return $request->user()->fresh();
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Agur!']);
    }
}