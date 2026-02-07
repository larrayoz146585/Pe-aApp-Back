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
    // 5. MIS ESTADÍSTICAS (Solo lo que YO he servido)
    public function stats(Request $request)
    {
        $user = $request->user(); // Este soy YO (el admin conectado)
        $rol = $user->role;

        if ($rol !== 'admin' && $rol !== 'superadmin') {
            return response()->json(['message' => 'Acceso denegado'], 403);
        }

        // A. Calcular TOTALES por bebida (SOLO LO QUE HE SERVIDO YO)
        $totales = DB::table('detalle_pedidos')
            ->join('pedidos', 'detalle_pedidos.pedido_id', '=', 'pedidos.id')
            ->join('bebidas', 'detalle_pedidos.bebida_id', '=', 'bebidas.id')
            ->where('pedidos.status', 'servido')
            ->where('pedidos.camarero_id', $user->id) // <--- FILTRO PERSONAL 🛑
            ->select('bebidas.nombre', DB::raw('sum(detalle_pedidos.cantidad) as total_vendido'))
            ->groupBy('bebidas.nombre')
            ->orderByDesc('total_vendido')
            ->get();

        // B. Historial detallado (SOLO LO QUE HE SERVIDO YO)
        $historial = Pedido::with(['user', 'camarero', 'detalles.bebida'])
            ->where('status', 'servido')
            ->where('camarero_id', $user->id) // <--- FILTRO PERSONAL 🛑
            ->orderBy('updated_at', 'desc')
            ->take(50)
            ->get();

        return response()->json([
            'resumen' => $totales,
            'historial' => $historial
        ]);
    }
    // 6. BORRAR SOLO MI HISTORIAL (RESET PERSONAL)
    public function reset(Request $request)
    {
        $user = $request->user();
        
        // 1. Buscamos los IDs de los pedidos que has servido TÚ
        $misPedidosIds = DB::table('pedidos')
                            ->where('camarero_id', $user->id)
                            ->pluck('id'); // Esto nos da una lista: [1, 5, 8...]

        if ($misPedidosIds->isEmpty()) {
            return response()->json(['message' => 'No tienes nada que borrar 🤷‍♂️']);
        }

        // 2. Borramos los detalles (las líneas de bebida) de ESOS pedidos
        DB::table('detalle_pedidos')
            ->whereIn('pedido_id', $misPedidosIds)
            ->delete();

        // 3. Borramos los pedidos (las cabeceras)
        DB::table('pedidos')
            ->whereIn('id', $misPedidosIds) // Solo los tuyos
            ->delete();

        return response()->json(['message' => 'Tu historial ha sido borrado. El de los demás sigue intacto.']);
    }
    
}