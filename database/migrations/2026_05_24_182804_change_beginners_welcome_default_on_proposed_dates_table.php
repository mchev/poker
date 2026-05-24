<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proposed_dates', function (Blueprint $table) {
            $table->boolean('beginners_welcome')->default(true)->change();
        });
    }

    public function down(): void
    {
        Schema::table('proposed_dates', function (Blueprint $table) {
            $table->boolean('beginners_welcome')->default(false)->change();
        });
    }
};
