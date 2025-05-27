<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Team;
use Illuminate\Support\Str;

class TeamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $teams = [
            'Real Madrid CF',
            'FC Barcelona',
            'Atlético Madrid',
            'Valencia CF',
            'Sevilla FC',
            'Athletic Bilbao',
            'Real Sociedad',
            'Villarreal CF',
            'Real Betis',
            'Celta de Vigo',
            'RC Deportivo',
            'Espanyol',
        ];

        foreach ($teams as $teamName) {
            Team::create([
                'id' => Str::uuid(),
                'name' => $teamName,
            ]);
        }
    }
}