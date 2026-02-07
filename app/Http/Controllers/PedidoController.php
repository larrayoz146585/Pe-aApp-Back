<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use App\Models\DetallePedido;
use App\Models\Bebida;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class PedidoController extends Controller
{
    // 1. HACER PEDIDO (Cliente)
    public function store(Request $request)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.bebida_id' => 'required|exists:bebidas,id',
            'items.*.cantidad' => 'required|integer|min:1',
        ]);

        try {
            return DB::transaction(function () use ($request) {
                
                $user = $request->user();
                $totalCalculado = 0;
                $detallesParaGuardar = [];

                // 1. Calcular total y preparar datos
                foreach ($request->items as $item) {
                    $bebida = Bebida::findOrFail($item['bebida_id']);
                    $subtotal = $bebida->precio * $item['cantidad'];
                    $totalCalculado += $subtotal;

                    $detallesParaGuardar[] = [
                        'bebida_id' => $bebida->id,
                        'cantidad' => $item['cantidad'],
                        'precio_unitario' => $bebida->precio
                    ];
                }

                // 🚨 AQUÍ ESTÁ EL CAMBIO NUCLEAR 🚨
                // No usamos $user->save(). Usamos decrement directo a la tabla.
                // Esto resta el dinero SÍ O SÍ.
                DB::table('users')->where('id', $user->id)->decrement('saldo', $totalCalculado);

                // 2. Crear pedido
                $pedido = Pedido::create([
                    'user_id' => $user->id,
                    'status' => 'pendiente',
                    'total' => $totalCalculado
                ]);

                // 3. Guardar detalles
                foreach ($detallesParaGuardar as $detalle) {
                    DetallePedido::create([
                        'pedido_id' => $pedido->id,
                        'bebida_id' => $detalle['bebida_id'],
                        'cantidad' => $detalle['cantidad'],
                        'precio_unitario' => $detalle['precio_unitario'],
                    ]);
                }

                // Recuperamos el usuario actualizado para enviarlo al móvil
                $user->refresh(); 

                return response()->json([
                    'message' => '¡Pedido en marcha! 🍻', 
                    'pedido' => $pedido,
                    'debug_total' => $totalCalculado, 
                    'nuevo_saldo' => $user->saldo
                ], 201);
            });

        } catch (\Exception $e) {
            return response()->json(['error' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    // 2. VER MIS PEDIDOS (Cliente)
    public function misPedidos(Request $request)
    {
        $pedidos = Pedido::with('detalles.bebida') // Traemos también los detalles y nombres de bebidas
                    ->where('user_id', $request->user()->id)
                    ->orderBy('created_at', 'desc')
                    ->get();

        return response()->json($pedidos);
    }

    // 3. VER COMANDAS PENDIENTES (Camarero/Admin)
    public function index(Request $request)
    {
        $rol = $request->user()->role;
        // Solo el admin puede ver esto
        if ($rol !== 'admin' && $rol !== 'superadmin') {
            return response()->json(['message' => 'No eres camarero 👮‍♂️'], 403);
        }

        // Traer pedidos pendientes, ordenados del más antiguo al más nuevo
        $pendientes = Pedido::with(['user', 'detalles.bebida'])
                        ->where('status', 'pendiente')
                        ->orderBy('created_at', 'asc') 
                        ->get();

        return response()->json($pendientes);
    }

    // 4. MARCAR COMO SERVIDO
    public function marcarServido(Request $request, $id) // <--- Añadimos Request $request
    {
        $pedido = Pedido::findOrFail($id);

        // Actualizamos estado Y guardamos quién lo hizo
        $pedido->update([
            'status' => 'servido',
            'camarero_id' => $request->user()->id  // <--- AQUÍ GUARDAMOS TU FIRMA
        ]);

        return response()->json(['message' => 'Pedido servido por ' . $request->user()->name]);
    }
    // 5. ESTADÍSTICAS AGRUPADAS POR CLIENTE (MODO CAMARERO PRO)
    public function stats(Request $request)
    {
        $user = $request->user(); // Tú (el camarero)

        // A. RANKING GLOBAL (Esto lo dejamos igual, mola ver qué se vende más)
        $ranking = DB::table('detalle_pedidos')
            ->join('pedidos', 'detalle_pedidos.pedido_id', '=', 'pedidos.id')
            ->join('bebidas', 'detalle_pedidos.bebida_id', '=', 'bebidas.id')
            ->where('pedidos.status', 'servido')
            ->where('pedidos.camarero_id', $user->id)
            ->select('bebidas.nombre', DB::raw('sum(detalle_pedidos.cantidad) as total_vendido'))
            ->groupBy('bebidas.nombre')
            ->orderByDesc('total_vendido')
            ->get();

        // B. HISTORIAL AGRUPADO POR PERSONA
        // 1. Sacamos todos tus pedidos servidos
        $pedidosRaw = Pedido::with(['user', 'detalles.bebida'])
            ->where('status', 'servido')
            ->where('camarero_id', $user->id)
            ->get();

        // 2. Los agrupamos por Cliente
        $porCliente = $pedidosRaw->groupBy('user_id')->map(function ($pedidosDelCliente) {
            $cliente = $pedidosDelCliente->first()->user;

            // Sumamos todo lo que ha gastado este señor contigo
            $gastoTotal = $pedidosDelCliente->sum('total');

            // Juntamos las bebidas (Ej: 2 cañas antes + 1 caña ahora = 3 cañas)
            $bebidasResumen = [];
            foreach ($pedidosDelCliente as $pedido) {
                foreach ($pedido->detalles as $detalle) {
                    $nombre = $detalle->bebida->nombre;
                    if (!isset($bebidasResumen[$nombre])) {
                        $bebidasResumen[$nombre] = 0;
                    }
                    $bebidasResumen[$nombre] += $detalle->cantidad;
                }
            }

            return [
                'id' => $cliente->id,
                'nombre' => $cliente->name,
                'total_gastado' => number_format($gastoTotal, 2),
                'bebidas' => $bebidasResumen // Array tipo ['Kalimotxo' => 3, 'Cerveza' => 1]
            ];
        })->values(); // Re-indexar para enviar JSON limpio

        return response()->json([
            'resumen' => $ranking,
            'historial' => $porCliente
        ]);
    }
    // 6. BORRAR MI HISTORIAL Y DEVOLVER EL DINERO 💰
    public function reset(Request $request)
    {
        $user = $request->user(); // El camarero (Tú)

        // 1. Buscamos los pedidos COMPLETOS que has servido TÚ
        // Usamos 'get()' para tener los datos del precio y el cliente
        $misPedidos = Pedido::where('camarero_id', $user->id)->get();

        if ($misPedidos->isEmpty()) {
            return response()->json(['message' => 'No tienes nada que borrar 🤷‍♂️']);
        }

        // 2. DEVOLVER EL DINERO (REFUND)
        // Recorremos cada pedido tuyo antes de borrarlo
        foreach ($misPedidos as $pedido) {
            // Al usuario que hizo el pedido ($pedido->user_id)...
            // ...le SUMAMOS el total al saldo ($pedido->total)
            DB::table('users')
                ->where('id', $pedido->user_id)
                ->increment('saldo', $pedido->total); 
        }

        // 3. Ahora que hemos devuelto el dinero, borramos los registros
        $idsParaBorrar = $misPedidos->pluck('id');

        // Borramos detalles
        DB::table('detalle_pedidos')
            ->whereIn('pedido_id', $idsParaBorrar)
            ->delete();

        // Borramos cabeceras
        DB::table('pedidos')
            ->whereIn('id', $idsParaBorrar)
            ->delete();

        return response()->json(['message' => 'Historial borrado y dinero devuelto a los clientes.']);
    }
    
}