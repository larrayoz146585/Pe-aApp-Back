<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pedido extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 
        'status',
        'total',
        'camarero_id'
    ];

    // Relación: El pedido pertenece a un usuario
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relación: El pedido tiene muchas líneas de detalle
    public function detalles()
    {
        return $this->hasMany(DetallePedido::class);
    }
    public function camarero()
    {
        return $this->belongsTo(User::class, 'camarero_id');
    }
}