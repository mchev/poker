<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('participants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('token', 64)->unique();
            $table->timestamps();
        });

        Schema::create('scheduling_rounds', function (Blueprint $table) {
            $table->id();
            $table->string('status');
            $table->timestamps();
        });

        Schema::create('proposed_dates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scheduling_round_id')->constrained()->cascadeOnDelete();
            $table->dateTime('starts_at');
            $table->foreignId('proposed_by_participant_id')->nullable()->constrained('participants')->nullOnDelete();
            $table->timestamps();

            $table->unique(['scheduling_round_id', 'starts_at']);
        });

        Schema::table('scheduling_rounds', function (Blueprint $table) {
            $table->foreignId('confirmed_proposed_date_id')
                ->nullable()
                ->after('status')
                ->constrained('proposed_dates')
                ->nullOnDelete();
        });

        Schema::create('votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('participant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('proposed_date_id')->constrained()->cascadeOnDelete();
            $table->string('availability');
            $table->timestamps();

            $table->unique(['participant_id', 'proposed_date_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('votes');
        Schema::table('scheduling_rounds', function (Blueprint $table) {
            $table->dropConstrainedForeignId('confirmed_proposed_date_id');
        });
        Schema::dropIfExists('proposed_dates');
        Schema::dropIfExists('scheduling_rounds');
        Schema::dropIfExists('participants');
    }
};
