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
        // 1. Tabla de Usuarios (La importante)
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            
            // Nombre único (actúa como el usuario para el login)
            $table->string('name')->unique();
            $table->string('password');

            // Tus roles y saldo
            $table->enum('role', ['superadmin', 'admin', 'cliente'])->default('cliente');
            $table->decimal('saldo', 8, 2)->default(0);

            $table->rememberToken();
            $table->timestamps();
        });

        // 2. Tabla de Sesiones (Laravel la usa internamente, mejor dejarla)
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
        
        // HE BORRADO 'password_reset_tokens' PORQUE REQUIERE EMAIL Y YA NO TENEMOS
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('sessions');
    }
};