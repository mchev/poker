<?php

namespace App\Console\Commands;

use App\Services\PokerSchedulingService;
use Illuminate\Console\Command;

class CompletePastTournaments extends Command
{
    protected $signature = 'poker:complete-past-tournaments';

    protected $description = 'Close confirmed tournaments that have passed and open a new scheduling poll';

    public function handle(PokerSchedulingService $scheduling): int
    {
        $completed = $scheduling->completePastTournaments();

        $this->info("Completed {$completed} tournament(s).");

        return self::SUCCESS;
    }
}
