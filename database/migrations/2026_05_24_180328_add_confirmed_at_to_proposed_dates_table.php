<?php

use App\Enums\SchedulingRoundStatus;
use App\Models\ProposedDate;
use App\Models\SchedulingRound;
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
        Schema::table('proposed_dates', function (Blueprint $table) {
            $table->timestamp('confirmed_at')->nullable()->after('theme');
        });

        SchedulingRound::query()
            ->where('status', SchedulingRoundStatus::Confirmed)
            ->whereNotNull('confirmed_proposed_date_id')
            ->each(function (SchedulingRound $round): void {
                ProposedDate::query()
                    ->whereKey($round->confirmed_proposed_date_id)
                    ->update(['confirmed_at' => $round->updated_at ?? now()]);

                $round->update(['status' => SchedulingRoundStatus::Polling]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proposed_dates', function (Blueprint $table) {
            $table->dropColumn('confirmed_at');
        });
    }
};
