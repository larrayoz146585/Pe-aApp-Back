<?php

namespace App\Http\Controllers;

use App\Models\Bebida;
use Illuminate\Http\Request;

class BebidaController extends Controller
{
    // GET: Ver la carta (Público para todos los logueados)
    public function index()
    {
        // Mostramos las activas. 
        // TRUCO: Si eres SuperAdmin, quizás quieras ver TODAS (incluso las ocultas) para editarlas.
        // Pero de momento lo dejamos así simple.
        
        return response()->json(Bebida::where('is_active', true)->get());
    }

    // POST: Crear nueva bebida (SOLO SUPERADMIN)
    public function store(Request $request)
    {
        $this->checkSuperAdmin($request); // Verificación de seguridad

        $validated = $request->validate([
            'nombre' => 'required|string',
            'precio' => 'required|numeric|min:0',
            'categoria' => 'required|string',
            'imagen_url' => 'nullable|string'
        ]);

        $bebida = Bebida::create($validated);

        return response()->json(['message' => 'Bebida añadida a la carta', 'bebida' => $bebida]);
    }

    // PUT: Editar precio o nombre (SOLO SUPERADMIN)
    public function update(Request $request, $id)
    {
        $this->checkSuperAdmin($request);

        $bebida = Bebida::findOrFail($id);

        $request->validate([
            'nombre' => 'sometimes|string',
            'precio' => 'sometimes|numeric|min:0',
            'activo' => 'sometimes|boolean' // Para ocultarla si se acaba el barril
        ]);

        $bebida->update($request->all());

        return response()->json(['message' => 'Bebida actualizada ', 'bebida' => $bebida]);
    }

    // DELETE: Borrar bebida (SOLO SUPERADMIN) 
    public function destroy(Request $request, $id)
    {
        $this->checkSuperAdmin($request);

        // OJO: Si borras una bebida que está en pedidos antiguos, puede dar error de clave foránea.
        // Lo mejor es "Desactivarla" en vez de borrarla.
        // Pero si insistes en borrar, usa delete().
        $bebida = Bebida::findOrFail($id);
        
        $bebida->update(['activo' => false]);
        // Opción B: Borrado total -> $bebida->delete();
        
        return response()->json(['message' => 'Bebida retirada de la carta 🚫']);
    }

    // Función auxiliar para proteger las rutas
    private function checkSuperAdmin($request)
    {
        if ($request->user()->role !== 'superadmin') {
            abort(403, 'Acceso Denegado: Solo el Jefe Supremo puede tocar los precios 👮‍♂️');
        }
    }
}