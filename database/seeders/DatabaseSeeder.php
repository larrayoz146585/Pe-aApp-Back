<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Bebida;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();
        User::factory()->create([
            'name' => 'Asier',
            'password' => bcrypt('31012'),
            'role' => 'superadmin',
        ]);
        User::factory()->create([
            'name' => 'Ainhoa',
            'password' => bcrypt('1234'),
            'role' => 'admin',
        ]);
        User::factory()->create([
            'name' => 'Aroa', 
            'password' => bcrypt('1234'),
            'role' => 'cliente'
        ]);
        $carta = [
            ['nombre' => 'Caña', 'precio' => 1.10, 'categoria' => 'Cervezas'],
            ['nombre' => 'Cañon', 'precio' => 2.00, 'categoria' => 'Cervezas'],
            ['nombre' => 'Kalimotxo', 'precio' => 2.00, 'categoria' => 'Vinos'],
            ['nombre' => 'Brugal Cola', 'precio' => 4.00, 'categoria' => 'Cubatas'],
            ['nombre' => 'Vodka Bisolan', 'precio' => 4.00, 'categoria' => 'Cubatas'],
            ['nombre' => 'Patxran Naranja', 'precio' => 4.00, 'categoria' => 'Cubatas'],
            ['nombre' => 'Coca Cola', 'precio' => 1.20, 'categoria' => 'Refrescos'],
            ['nombre' => 'Chupito', 'precio' => 1.00, 'categoria' => 'Chupitos'],
        ];
        foreach ($carta as $item) {
            Bebida::create($item);
        }

        
    }
}
