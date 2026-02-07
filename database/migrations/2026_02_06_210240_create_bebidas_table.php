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
        Schema::create('bebidas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->decimal('precio', 8, 2); // Ej: 1.50
            $table->string('categoria'); // Ej: "Cerveza", "Combinado", "Sin Alcohol"
            $table->string('image_url')->nullable(); // Para la foto bonita
            $table->boolean('is_active')->default(true); // Por si se acaba el barril, la ocultas
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bebidas');
    }
};
