<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BebidaController;
use App\Http\Controllers\PedidoController;
use App\Http\Controllers\UserController;

// --- RUTAS PÚBLICAS ---
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// --- RUTAS PROTEGIDAS ---
Route::middleware('auth:sanctum')->group(function () {

    // USUARIO
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // CARTA DE BEBIDAS
    Route::get('/bebidas', [BebidaController::class, 'index']);
    Route::get('/admin/bebidas', [BebidaController::class, 'adminIndex']);
    Route::post('/admin/bebidas/create', [BebidaController::class, 'store']);
    Route::put('/admin/bebidas/{id}/update', [BebidaController::class, 'update']);
    Route::delete('/admin/bebidas/{id}/delete', [BebidaController::class, 'destroy']);

    // PEDIDOS (CLIENTE)
    Route::post('/pedidos', [PedidoController::class, 'store']);
    Route::get('/mis-pedidos', [PedidoController::class, 'misPedidos']);

    // CAMAREROS (el cliente elige los suyos)
    Route::get('/camareros', [UserController::class, 'admins']);              // Lista de admins disponibles
    Route::get('/mis-camareros', [UserController::class, 'misCamareros']);   // Mis camareros actuales
    Route::post('/mis-camareros', [UserController::class, 'actualizarCamareros']); // Guardar selección

    // ZONA CAMARERO (ADMIN)
    Route::get('/admin/pedidos', [PedidoController::class, 'index']);
    Route::put('/pedidos/{id}/servir', [PedidoController::class, 'marcarServido']);
    Route::delete('/admin/pedidos/{id}', [PedidoController::class, 'destroy']);
    Route::get('/admin/estadisticas', [PedidoController::class, 'stats']);
    Route::delete('/admin/reset-pedidos', [PedidoController::class, 'reset']);

    // SUPERADMIN
    Route::get('/admin/usuarios', [UserController::class, 'index']);
    Route::put('/admin/usuarios/{id}', [UserController::class, 'update']);
    Route::delete('/admin/usuarios/{id}', [UserController::class, 'destroy']);
});