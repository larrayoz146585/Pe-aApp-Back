<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetallePedido extends Model
{
    use HasFactory;

    protected $fillable = [
        'pedido_id', 
        'bebida_id', 
        'cantidad', 
        'precio_unitario'
    ];

    // Relación: Esta línea pertenece a un pedido padre
    public function pedido()
    {
        return $this->belongsTo(Pedido::class);
    }

    // Relación: Esta línea se refiere a una bebida concreta
    public function bebida()
    {
        return $this->belongsTo(Bebida::class);
    }
}