<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bebida extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre', 
        'precio', 
        'categoria', 
        'imagen_url', 
        'activo'
    ];

    // Convertimos datos automáticamente para que sean fáciles de usar
    protected $casts = [
        'precio' => 'decimal:2',
        'activo' => 'boolean',
    ];
}