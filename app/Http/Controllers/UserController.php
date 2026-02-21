<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        // Solo el superadmin puede ver la lista de usuarios
        if ($request->user()->role !== 'superadmin') {
            abort(403, 'Acceso Denegado: Solo Asier puede entrar');
        }
        $usuarios = User::all();
        return response()->json($usuarios);
    }
    
    //actualizar usuario
    public function update(Request $request, $id)
    {
        //actualizar usuario solo si es superadmin
        if ($request->user()->role !== 'superadmin' && $request->user()->id != $id) {
            abort(403, 'Acceso Denegado: Solo Asier puede entrar');
        }

        $usuario = User::findOrFail($id);

        $validated = $request->validate([
            // 'sometimes' significa que solo valida si el campo viene en la petición
            'name' => 'sometimes|string|max:255|unique:users,name,' . $usuario->id,
            'password' => 'sometimes|string|min:4',
            'role' => 'sometimes|in:superadmin,admin,cliente', // Solo permite estos roles
            'saldo' => 'sometimes|numeric'
        ]);

        // Si se envía una nueva contraseña, la hasheamos antes de guardarla
        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $usuario->update($validated);

        return response()->json(['message' => 'Usuario actualizado correctamente', 'user' => $usuario->fresh()]);
    }

    //borrar usuario
    public function destroy(Request $request, $id)
    {
        // Un usuario no se puede borrar a sí mismo
        if ($request->user()->id == $id) {
            abort(403, 'No te puedes borrar a ti mismo.');
        }
        //borrar usuario solo si es superadmin (o en el futuro, un admin a un cliente)
        if ($request->user()->role !== 'superadmin') {
            abort(403, 'Acceso Denegado: Solo Asier puede entrar');
        }
        $usuario = User::findOrFail($id);
        $usuario->delete();
        return response()->json(['message' => 'Usuario eliminado correctamente.']);
    }
}
