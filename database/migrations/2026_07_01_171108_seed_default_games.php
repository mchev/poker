<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('games')->insertOrIgnore([
            ['name' => 'Poker', 'slug' => 'poker', 'icon' => '🃏', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Flechettes', 'slug' => 'flechettes', 'icon' => '🎯', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Billard', 'slug' => 'billard', 'icon' => '🎱', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Palet breton', 'slug' => 'palet-breton', 'icon' => '🎯', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Échecs', 'slug' => 'echecs', 'icon' => '♟️', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        DB::table('games')->whereIn('slug', [
            'poker', 'flechettes', 'billard', 'palet-breton', 'echecs',
        ])->delete();
    }
};
