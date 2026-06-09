<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('poker:complete-past-tournaments')->hourly();
Schedule::command('poker:send-vote-reminders')
    ->dailyAt('10:00')
    ->timezone(config('app.timezone'));
