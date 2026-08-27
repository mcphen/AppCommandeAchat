<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('database:backup-email')
    ->dailyAt('00:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/database-backup.log'));

Schedule::command('validation:send-reminders')
    ->weekdays()
    ->dailyAt('08:00')
    ->timezone('Africa/Dakar')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/validation-reminders.log'));
