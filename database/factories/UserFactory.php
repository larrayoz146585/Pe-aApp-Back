<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            // 'email' => fake()->unique()->safeEmail(),  <-- BORRADO
            // 'email_verified_at' => now(),              <-- BORRADO
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role' => 'cliente', // Importante: Valor por defecto
            'saldo' => 0,        // Importante: Valor por defecto
        ];
    }

    public function unverified(): static
    {
        // Esta función entera sobraba porque servía para quitar la verificación del email
        return $this->state(fn (array $attributes) => []);
    }
}