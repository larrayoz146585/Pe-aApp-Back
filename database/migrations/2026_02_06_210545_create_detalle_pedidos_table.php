<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('detalle_pedidos', function (Blueprint $table) {
            $table->id();
            // Relación con el Pedido (Ticket)
            $table->foreignId('pedido_id')->constrained()->onDelete('cascade');
            
            // Relación con la Bebida (Producto)
            $table->foreignId('bebida_id')->constrained(); 
            
            $table->integer('cantidad'); // Ej: 2
            $table->decimal('precio_unitario', 8, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detalle_pedidos');
    }
};
