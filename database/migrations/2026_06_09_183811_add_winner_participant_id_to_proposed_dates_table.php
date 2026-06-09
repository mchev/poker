<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proposed_dates', function (Blueprint $table) {
            $table->foreignId('winner_participant_id')
                ->nullable()
                ->after('proposed_by_participant_id')
                ->constrained('participants')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('proposed_dates', function (Blueprint $table) {
            $table->dropConstrainedForeignId('winner_participant_id');
        });
    }
};
