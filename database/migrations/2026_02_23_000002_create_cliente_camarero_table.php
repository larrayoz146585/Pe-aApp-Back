<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClienteCamareroTable extends Migration
{
    public function up(): void
    {
        // Primero eliminamos la columna camarero_id de users si existe (del intento anterior)
        if (Schema::hasColumn('users', 'camarero_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['camarero_id']);
                $table->dropColumn('camarero_id');
            });
        }

        // Tabla pivote: un cliente puede tener varios camareros y viceversa
        Schema::create('cliente_camarero', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('camarero_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            // Evitar duplicados: un cliente no puede tener el mismo camarero dos veces
            $table->unique(['cliente_id', 'camarero_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cliente_camarero');
    }
}