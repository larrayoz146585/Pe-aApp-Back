<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    // 1. LISTAR TODOS LOS USUARIOS (Solo SuperAdmin)
    public function index(Request $request)
    {
        if ($request->user()->role !== 'superadmin') {
            abort(403, 'Acceso Denegado');
        }

        $usuarios = User::select('id', 'name', 'role', 'saldo')->get();
        return response()->json($usuarios);
    }

    // 2. ACTUALIZAR USUARIO (rol, saldo, password)
    public function update(Request $request, $id)
    {
        if ($request->user()->role !== 'superadmin' && $request->user()->id != $id) {
            abort(403, 'Acceso Denegado');
        }

        $usuario = User::findOrFail($id);

        $validated = $request->validate([
            'name'     => 'sometimes|string|max:255|unique:users,name,' . $usuario->id,
            'password' => 'sometimes|string|min:4',
            'role'     => 'sometimes|in:superadmin,admin,cliente',
            'saldo'    => 'sometimes|numeric',
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $usuario->update($validated);

        return response()->json(['message' => 'Usuario actualizado correctamente', 'user' => $usuario->fresh()]);
    }

    // 3. ELIMINAR USUARIO (Solo SuperAdmin)
    public function destroy(Request $request, $id)
    {
        if ($request->user()->id == $id) {
            abort(403, 'No te puedes borrar a ti mismo.');
        }
        if ($request->user()->role !== 'superadmin') {
            abort(403, 'Acceso Denegado');
        }

        $usuario = User::findOrFail($id);
        $usuario->delete();

        return response()->json(['message' => 'Usuario eliminado correctamente.']);
    }

    // 4. LISTAR ADMINS DISPONIBLES (Para que el cliente elija sus camareros)
    public function admins(Request $request)
    {
        $admins = User::whereIn('role', ['admin', 'superadmin'])
            ->select('id', 'name', 'role')
            ->orderBy('name')
            ->get();

        return response()->json($admins);
    }

    // 5. VER MIS CAMAREROS ACTUALES (Cliente)
    public function misCamareros(Request $request)
    {
        $camareroIds = $request->user()->camareros()->pluck('users.id');
        return response()->json($camareroIds);
    }

    // 6. ACTUALIZAR MIS CAMAREROS (Cliente elige/deselecciona)
    public function actualizarCamareros(Request $request)
    {
        $request->validate([
            'camarero_ids'   => 'present|array',
            'camarero_ids.*' => 'integer|exists:users,id',
        ]);

        $user = $request->user();

        if (!empty($request->camarero_ids)) {
            $validos = User::whereIn('id', $request->camarero_ids)
                ->whereIn('role', ['admin', 'superadmin'])
                ->count();

            if ($validos !== count($request->camarero_ids)) {
                return response()->json(['message' => 'Algunos usuarios seleccionados no son camareros válidos.'], 422);
            }
        }

        $user->camareros()->sync($request->camarero_ids);

        return response()->json(['message' => 'Camareros actualizados correctamente.']);
    }
}