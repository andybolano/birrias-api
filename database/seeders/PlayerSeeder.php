<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Player;
use App\Models\User;
use App\Models\Team;
use Illuminate\Support\Str;

class PlayerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $playerUsers = User::where('role', 'player')->get();
        $teams = Team::all();
        $positions = ['Portero', 'Defensa', 'Mediocampista', 'Delantero'];

        foreach ($playerUsers as $index => $user) {
            $player = Player::create([
                'id' => Str::uuid(),
                'position' => $positions[array_rand($positions)],
                'jersey' => ($index % 30) + 1,
                'birthDay' => fake()->dateTimeBetween('-35 years', '-18 years')->format('Y-m-d'),
                'personId' => $user->id,
            ]);

            // Assign player to a random team
            $randomTeam = $teams->random();
            $player->teams()->attach($randomTeam->id);
        }

        // Create additional players without user accounts
        $additionalPlayers = [
            ['name' => 'Lionel Messi', 'position' => 'Delantero', 'jersey' => 10],
            ['name' => 'Cristiano Ronaldo', 'position' => 'Delantero', 'jersey' => 7],
            ['name' => 'Neymar Jr', 'position' => 'Delantero', 'jersey' => 11],
            ['name' => 'Kylian Mbappé', 'position' => 'Delantero', 'jersey' => 9],
            ['name' => 'Erling Haaland', 'position' => 'Delantero', 'jersey' => 9],
            ['name' => 'Kevin De Bruyne', 'position' => 'Mediocampista', 'jersey' => 17],
            ['name' => 'Virgil van Dijk', 'position' => 'Defensa', 'jersey' => 4],
            ['name' => 'Manuel Neuer', 'position' => 'Portero', 'jersey' => 1],
        ];

        foreach ($additionalPlayers as $playerData) {
            $player = Player::create([
                'id' => Str::uuid(),
                'position' => $playerData['position'],
                'jersey' => $playerData['jersey'],
                'birthDay' => fake()->dateTimeBetween('-35 years', '-18 years')->format('Y-m-d'),
                'personId' => null,
            ]);

            // Assign to random team
            $randomTeam = $teams->random();
            $player->teams()->attach($randomTeam->id);
        }
    }
}