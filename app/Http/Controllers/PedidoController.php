<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PedidoController extends Controller
{
    // 1. HACER UN PEDIDO (Cliente)
    public function store(Request $request)
    {
        $request->validate([
            'items'             => 'required|array|min:1',
            'items.*.bebida_id' => 'required|exists:bebidas,id',
            'items.*.cantidad'  => 'required|integer|min:1',
        ]);

        $user = $request->user();

        try {
            DB::transaction(function () use ($request, $user) {
                $totalCalculado = 0;

                foreach ($request->items as $item) {
                    $bebida = DB::table('bebidas')->where('id', $item['bebida_id'])->first();
                    if (!$bebida || !$bebida->is_active) {
                        throw new \Exception("La bebida con id {$item['bebida_id']} no esta disponible.");
                    }
                    $totalCalculado += $bebida->precio * $item['cantidad'];
                }

                $pedido = Pedido::create([
                    'user_id' => $user->id,
                    'status'  => 'pendiente',
                    'total'   => $totalCalculado,
                ]);

                foreach ($request->items as $item) {
                    $bebida = DB::table('bebidas')->where('id', $item['bebida_id'])->first();
                    DB::table('detalle_pedidos')->insert([
                        'pedido_id'       => $pedido->id,
                        'bebida_id'       => $item['bebida_id'],
                        'cantidad'        => $item['cantidad'],
                        'precio_unitario' => $bebida->precio,
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ]);
                }
            });

        } catch (\Exception $e) {
            return response()->json(['error' => 'Error: ' . $e->getMessage()], 500);
        }

        return response()->json(['message' => 'Pedido recibido!'], 201);
    }

    // 2. VER MIS PEDIDOS (Cliente)
    public function misPedidos(Request $request)
    {
        $pedidos = Pedido::with('detalles.bebida')
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($pedidos);
    }

    // 3. VER COMANDAS PENDIENTES (Camarero/Admin)
    // Si el cliente tiene camareros asignados: solo ellos ven sus comandas
    // Si el cliente NO tiene camareros: lo ven todos los admins
    public function index(Request $request)
    {
        $adminActual = $request->user();

        if (!in_array($adminActual->role, ['admin', 'superadmin'])) {
            return response()->json(['message' => 'No eres camarero'], 403);
        }

        $pendientes = Pedido::with(['user.camareros', 'detalles.bebida'])
            ->where('status', 'pendiente')
            ->orderBy('created_at', 'asc')
            ->get();

        $filtrados = $pendientes->filter(function ($pedido) use ($adminActual) {
            $cliente = $pedido->user;
            if (!$cliente) return false;

            $camareroIds = $cliente->camareros->pluck('id');

            // Sin camareros asignados: lo ven todos
            if ($camareroIds->isEmpty()) return true;

            // Con camareros asignados: solo los seleccionados
            return $camareroIds->contains($adminActual->id);
        });

        return response()->json($filtrados->values());
    }

    // 4. MARCAR COMO SERVIDO Y COBRAR
    public function marcarServido(Request $request, $id)
    {
        $pedido = Pedido::findOrFail($id);

        if ($pedido->status === 'servido') {
            return response()->json(['message' => 'Este pedido ya fue servido'], 400);
        }

        DB::table('users')->where('id', $pedido->user_id)->decrement('saldo', $pedido->total);

        $pedido->update([
            'status'      => 'servido',
            'camarero_id' => $request->user()->id,
        ]);

        return response()->json(['message' => 'Pedido servido y cobrado por ' . $request->user()->name]);
    }

    // 5. ESTADISTICAS
    public function stats(Request $request)
    {
        $user = $request->user();

        $ranking = DB::table('detalle_pedidos')
            ->join('pedidos', 'detalle_pedidos.pedido_id', '=', 'pedidos.id')
            ->join('bebidas', 'detalle_pedidos.bebida_id', '=', 'bebidas.id')
            ->where('pedidos.status', 'servido')
            ->where('pedidos.camarero_id', $user->id)
            ->select('bebidas.nombre', DB::raw('sum(detalle_pedidos.cantidad) as total_vendido'))
            ->groupBy('bebidas.nombre')
            ->orderByDesc('total_vendido')
            ->get();

        $pedidosRaw = Pedido::with(['user', 'detalles.bebida'])
            ->where('status', 'servido')
            ->where('camarero_id', $user->id)
            ->get();

        $porCliente = $pedidosRaw->groupBy('user_id')->map(function ($pedidosDelCliente) {
            $cliente    = $pedidosDelCliente->first()->user;
            $gastoTotal = $pedidosDelCliente->sum('total');

            $bebidasResumen = [];
            foreach ($pedidosDelCliente as $pedido) {
                foreach ($pedido->detalles as $detalle) {
                    $nombre = $detalle->bebida->nombre;
                    $bebidasResumen[$nombre] = ($bebidasResumen[$nombre] ?? 0) + $detalle->cantidad;
                }
            }

            return [
                'id'            => $cliente->id,
                'nombre'        => $cliente->name,
                'total_gastado' => number_format($gastoTotal, 2),
                'bebidas'       => $bebidasResumen,
            ];
        })->values();

        return response()->json(['resumen' => $ranking, 'historial' => $porCliente]);
    }

    // 6. BORRAR HISTORIAL Y DEVOLVER DINERO
    public function reset(Request $request)
    {
        $user       = $request->user();
        $misPedidos = Pedido::where('camarero_id', $user->id)->get();

        if ($misPedidos->isEmpty()) {
            return response()->json(['message' => 'No tienes nada que borrar']);
        }

        foreach ($misPedidos as $pedido) {
            DB::table('users')->where('id', $pedido->user_id)->increment('saldo', $pedido->total);
        }

        $ids = $misPedidos->pluck('id');
        DB::table('detalle_pedidos')->whereIn('pedido_id', $ids)->delete();
        DB::table('pedidos')->whereIn('id', $ids)->delete();

        return response()->json(['message' => 'Historial borrado y dinero devuelto.']);
    }

    // 7. CANCELAR PEDIDO PENDIENTE
    public function destroy(Request $request, $id)
    {
        if (!in_array($request->user()->role, ['admin', 'superadmin'])) {
            return response()->json(['message' => 'No tienes permiso'], 403);
        }

        $pedido = Pedido::findOrFail($id);

        if ($pedido->status !== 'pendiente') {
            return response()->json(['message' => 'No puedes cancelar un pedido ya servido'], 400);
        }

        DB::table('detalle_pedidos')->where('pedido_id', $pedido->id)->delete();
        $pedido->delete();

        return response()->json(['message' => 'Pedido cancelado correctamente']);
    }
}