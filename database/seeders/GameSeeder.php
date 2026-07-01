<?php

namespace Database\Seeders;

use App\Models\Game;
use Illuminate\Database\Seeder;

class GameSeeder extends Seeder
{
    public function run(): void
    {
        $games = [
            ['name' => 'Poker', 'slug' => 'poker', 'icon' => '🃏'],
            ['name' => 'Flechettes', 'slug' => 'flechettes', 'icon' => '🎯'],
            ['name' => 'Billard', 'slug' => 'billard', 'icon' => '🎱'],
            ['name' => 'Palet breton', 'slug' => 'palet-breton', 'icon' => '🎯'],
            ['name' => 'Échecs', 'slug' => 'echecs', 'icon' => '♟️'],
        ];

        foreach ($games as $game) {
            Game::query()->firstOrCreate(
                ['slug' => $game['slug']],
                $game,
            );
        }
    }
}
