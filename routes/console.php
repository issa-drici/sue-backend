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

// Personnes bloquées à la connexion (code SMS non validé, SMS en échec) → alerte Telegram
Schedule::command('alerts:check-signups')
    ->everyMinute()
    ->withoutOverlapping();

// "Trouver un créneau" : rappel aux organisateurs pour confirmer un créneau
Schedule::command('sessions:send-slot-reminders')
    ->everyFiveMinutes()
    ->withoutOverlapping();
