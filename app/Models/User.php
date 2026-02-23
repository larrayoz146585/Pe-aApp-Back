<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'password',
        'role',
        'saldo',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'password' => 'hashed',
    ];

    // Un usuario tiene muchos pedidos
    public function pedidos()
    {
        return $this->hasMany(Pedido::class);
    }

    // Los camareros que tiene asignados este cliente (muchos a muchos)
    public function camareros()
    {
        return $this->belongsToMany(
            User::class,
            'cliente_camarero',
            'cliente_id',
            'camarero_id'
        );
    }

    // Los clientes que tiene asignados este camarero (inverso)
    public function clientes()
    {
        return $this->belongsToMany(
            User::class,
            'cliente_camarero',
            'camarero_id',
            'cliente_id'
        );
    }
}