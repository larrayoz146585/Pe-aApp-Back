<?php

namespace App\Http\Controllers;

use App\Models\Bebida;
use Illuminate\Http\Request;

class BebidaController extends Controller
{
    // GET: Ver la carta (clientes) - agrupada por categoría, solo activas
    public function index()
    {
        $bebidasActivas = Bebida::where('is_active', true)->orderBy('nombre')->get();
        return response()->json($bebidasActivas->groupBy('categoria'));
    }

    // GET: Ver todas las bebidas (admin) - activas e inactivas, array plano
    public function adminIndex(Request $request)
    {
        $this->checkSuperAdmin($request);
        return response()->json(Bebida::orderBy('nombre')->get());
    }

    // POST: Crear nueva bebida
    public function store(Request $request)
    {
        $this->checkSuperAdmin($request);

        $validated = $request->validate([
            'nombre'    => 'required|string',
            'precio'    => 'required|numeric|min:0',
            'categoria' => 'required|string',
        ]);

        // Comprobamos si ya existe una bebida con el mismo nombre (incluso inactiva)
        // para evitar duplicados
        $existente = Bebida::where('nombre', $validated['nombre'])->first();
        if ($existente) {
            // Si existe pero estaba inactiva, la reactivamos en vez de duplicar
            $existente->update([
                'precio'    => $validated['precio'],
                'categoria' => $validated['categoria'],
                'is_active' => true,
            ]);
            return response()->json(['message' => 'Bebida reactivada correctamente', 'bebida' => $existente]);
        }

        $bebida = Bebida::create([
            'nombre'    => $validated['nombre'],
            'precio'    => $validated['precio'],
            'categoria' => $validated['categoria'],
            'is_active' => true,
        ]);

        return response()->json(['message' => 'Bebida añadida a la carta', 'bebida' => $bebida]);
    }

    // PUT: Editar bebida
    public function update(Request $request, $id)
    {
        $this->checkSuperAdmin($request);

        $bebida = Bebida::findOrFail($id);

        $validated = $request->validate([
            'nombre'    => 'sometimes|string',
            'precio'    => 'sometimes|numeric|min:0',
            'categoria' => 'sometimes|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $bebida->update($validated);

        return response()->json(['message' => 'Bebida actualizada', 'bebida' => $bebida]);
    }

    // DELETE: Eliminar bebida permanentemente
    public function destroy(Request $request, $id)
    {
        $this->checkSuperAdmin($request);

        $bebida = Bebida::findOrFail($id);
        $bebida->delete();

        return response()->json(['message' => 'Bebida eliminada correctamente']);
    }

    private function checkSuperAdmin($request)
    {
        if ($request->user()->role !== 'superadmin') {
            abort(403, 'Acceso Denegado');
        }
    }
}