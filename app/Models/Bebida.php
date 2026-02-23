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
        'is_active',
    ];

    protected $casts = [
        'precio'    => 'decimal:2',
        'is_active' => 'boolean',
    ];
}