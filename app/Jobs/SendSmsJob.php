<?php

namespace App\Jobs;

use App\Services\InfiniReachSmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Envoi d'un SMS en arrière-plan via la queue Laravel (driver "database",
 * cf. QUEUE_CONNECTION) - ne bloque pas la réponse HTTP de l'appelant.
 * Nécessite un worker actif (`php artisan queue:work`) pour être traité.
 *
 * NON UTILISÉ pour l'instant (PhoneOtpService envoie en synchrone : un OTP est
 * sensible à la latence et aucun worker ne tourne en prod). Gardé pour un futur
 * envoi de SMS moins urgent (ex: rappel, notification) où l'async a plus de sens.
 */
class SendSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 10;

    public function __construct(
        private readonly string $to,
        private readonly string $message
    ) {}

    public function handle(InfiniReachSmsService $sms): void
    {
        $sms->send($this->to, $this->message);
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Échec définitif envoi SMS (toutes les tentatives épuisées)', [
            'to' => $this->to,
            'error' => $exception->getMessage(),
        ]);
    }
}
