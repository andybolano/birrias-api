<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Support\Str;

class TournamentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first();

        Tournament::create([
            'id' => Str::uuid(),
            'name' => 'Liga Birrias 2024',
            'start_date' => '2024-03-01',
            'inscription_fee_money' => 150.00,
            'currency' => 'USD',
            'owner' => $admin->id,
            'status' => 'active',
            'format' => 'league',
            'rounds' => 2,
            'home_away' => true,
        ]);

        Tournament::create([
            'id' => Str::uuid(),
            'name' => 'Copa Birrias Amateur',
            'start_date' => '2024-05-15',
            'inscription_fee_money' => 75.00,
            'currency' => 'USD',
            'owner' => $admin->id,
            'status' => 'inactive',
            'format' => 'groups_knockout',
            'groups' => 4,
            'teams_per_group' => 4,
            'playoff_size' => 8,
        ]);

        Tournament::create([
            'id' => Str::uuid(),
            'name' => 'Torneo Verano 2024',
            'start_date' => '2024-06-01',
            'inscription_fee_money' => 100.00,
            'currency' => 'USD',
            'owner' => $admin->id,
            'status' => 'active',
            'format' => 'league_playoffs',
            'rounds' => 1,
            'playoff_size' => 4,
            'home_away' => false,
        ]);
    }
}