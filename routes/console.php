<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Planifier l'envoi des rappels de session toutes les minutes
Schedule::command('sessions:send-reminders')
    ->everyMinute()
    ->withoutOverlapping();
    // ->runInBackground();
