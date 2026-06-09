<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proposed_dates', function (Blueprint $table) {
            $table->timestamp('vote_reminder_sent_at')->nullable()->after('confirmed_at');
        });
    }

    public function down(): void
    {
        Schema::table('proposed_dates', function (Blueprint $table) {
            $table->dropColumn('vote_reminder_sent_at');
        });
    }
};
