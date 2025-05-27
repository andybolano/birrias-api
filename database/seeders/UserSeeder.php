<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin users
        User::create([
            'id' => Str::uuid(),
            'fullname' => 'Administrador Principal',
            'email' => 'admin@birrias.com',
            'username' => 'admin',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'status' => 'active',
            'phone' => '+1234567890',
        ]);

        User::create([
            'id' => Str::uuid(),
            'fullname' => 'Carlos Manager',
            'email' => 'carlos@birrias.com',
            'username' => 'carlos_mgr',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'status' => 'active',
            'phone' => '+1234567891',
        ]);

        // Create player users
        $playerNames = [
            'Juan Pérez',
            'Miguel Rodriguez',
            'Luis González',
            'Pedro Martínez',
            'Carlos Sánchez',
            'Diego López',
            'Fernando Torres',
            'Alejandro Silva',
            'Roberto Castro',
            'Mario Jiménez',
            'Andrés Morales',
            'David Herrera',
            'Sebastián Ruiz',
            'Mateo Vargas',
            'Gabriel Mendoza'
        ];

        foreach ($playerNames as $index => $name) {
            User::create([
                'id' => Str::uuid(),
                'fullname' => $name,
                'email' => strtolower(str_replace(' ', '.', $name)) . '@birrias.com',
                'username' => strtolower(str_replace(' ', '_', $name)),
                'password' => Hash::make('password'),
                'role' => 'player',
                'status' => 'active',
                'phone' => '+123456' . str_pad($index + 7900, 4, '0', STR_PAD_LEFT),
            ]);
        }
    }
}