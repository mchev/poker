<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_proposed_date', function (Blueprint $table) {
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->foreignId('proposed_date_id')->constrained()->cascadeOnDelete();
            $table->primary(['game_id', 'proposed_date_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_proposed_date');
    }
};
