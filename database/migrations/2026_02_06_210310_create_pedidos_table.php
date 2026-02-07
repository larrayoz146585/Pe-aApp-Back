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
        Schema::create('pedidos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Quién pide
            // Estados: pendiente (acaba de pedir), servido (ya tiene la copa), pagado (ya has cobrado)
            $table->enum('status', ['pendiente', 'servido', 'pagado'])->default('pendiente'); 
            $table->foreignId('camarero_id')->nullable()->constrained('users');
            $table->decimal('total', 8, 2); // Total del pedido (suma de items)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pedidos');
    }
};
