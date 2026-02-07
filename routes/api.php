<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
// Importamos los controladores que hemos creado
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BebidaController;
use App\Http\Controllers\PedidoController;

/*
|--------------------------------------------------------------------------
| RUTAS PÚBLICAS (No necesitas llave para entrar)
|--------------------------------------------------------------------------
*/

// Login y Registro
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']); // Por si acaso

/*
|--------------------------------------------------------------------------
| RUTAS PROTEGIDAS (Necesitas Token / Estar Logueado)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    
    // --- USUARIO (Logout y ver quién soy) ---
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // --- CARTA DE BEBIDAS ---
    Route::get('/bebidas', [BebidaController::class, 'index']); // Ver carta (Todos)
    
    // Gestión de la carta (El controlador verificará si eres SuperAdmin)
    Route::post('/bebidas', [BebidaController::class, 'store']);       // Crear bebida
    Route::put('/bebidas/{id}', [BebidaController::class, 'update']);  // Editar bebida
    Route::delete('/bebidas/{id}', [BebidaController::class, 'destroy']); // Borrar bebida

    // --- PEDIDOS (CLIENTE) ---
    Route::post('/pedidos', [PedidoController::class, 'store']);       // Hacer pedido
    Route::get('/mis-pedidos', [PedidoController::class, 'misPedidos']); // Ver mi cuenta

    // --- ZONA CAMARERO (ADMIN) ---
    Route::get('/admin/pedidos', [PedidoController::class, 'index']); // Ver comandas en cocina
    Route::put('/pedidos/{id}/servir', [PedidoController::class, 'marcarServido']); // Servir copa

    Route::get('/admin/estadisticas', [PedidoController::class, 'stats']);

    Route::delete('/admin/reset-pedidos', [PedidoController::class, 'reset']);
});