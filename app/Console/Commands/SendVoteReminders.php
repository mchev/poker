<?php

namespace App\Console\Commands;

use App\Services\PokerSchedulingService;
use Illuminate\Console\Command;

class SendVoteReminders extends Command
{
    protected $signature = 'poker:send-vote-reminders';

    protected $description = 'Remind participants who have not voted on dates happening tomorrow that still need more yes votes';

    public function handle(PokerSchedulingService $scheduling): int
    {
        $sent = $scheduling->sendVoteReminders();

        $this->info("Sent {$sent} vote reminder(s).");

        return self::SUCCESS;
    }
}
