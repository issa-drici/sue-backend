<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Envoi de SMS via SMS Gateway for Android (https://docs.sms-gate.app/), mode cloud :
 * le SMS part de la carte SIM du téléphone Android sur lequel l'app SMS Gateway tourne.
 * POST {url}/3rdparty/v1/messages, authentification Basic (identifiant/mot de passe affichés
 * dans l'app, onglet Cloud).
 *
 * L'API met le message en file d'attente : une réponse 2xx veut dire « accepté », pas « reçu ».
 * Si le téléphone est éteint ou hors réseau, le SMS part à son retour, ou jamais s'il a expiré.
 *
 * En l'absence d'identifiants (dev/local), le message est journalisé au lieu d'être
 * envoyé, ce qui permet de tester le flux OTP sans téléphone.
 */
class SmsGateSmsService
{
    // Au-delà, le code OTP a expiré (cf. PhoneOtpService::CODE_TTL_MINUTES) : inutile de l'envoyer en retard
    private const TTL_SECONDS = 300;

    // >= 100 : traité en priorité, sans les délais anti-spam de l'app (un OTP est attendu à l'écran)
    private const PRIORITY = 100;

    public function isConfigured(): bool
    {
        return !empty(config('services.smsgate.username'))
            && !empty(config('services.smsgate.password'));
    }

    /**
     * @param string $to numéro au format E.164 (+33612345678)
     * @throws RuntimeException si l'envoi est refusé
     */
    public function send(string $to, string $message): void
    {
        if (!$this->isConfigured()) {
            Log::warning('SMS Gateway non configuré — SMS non envoyé (mode dev)', [
                'to' => $to,
                'message' => $message,
            ]);
            return;
        }

        $response = Http::withBasicAuth(
            config('services.smsgate.username'),
            config('services.smsgate.password')
        )->acceptJson()->timeout(10)->post(rtrim(config('services.smsgate.url'), '/') . '/3rdparty/v1/messages', [
            'textMessage' => ['text' => $message],
            'phoneNumbers' => [$to],
            'ttl' => self::TTL_SECONDS,
            'priority' => self::PRIORITY,
        ]);

        if (!$response->successful()) {
            Log::error('Échec envoi SMS SMS Gateway', [
                'to' => $to,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new RuntimeException('Échec de l\'envoi du SMS');
        }
    }
}
