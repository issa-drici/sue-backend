<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Envoi de SMS via InfiniReach (serveur SMS auto-hébergé).
 * POST https://api.infinireach.io/api/v1/messages, authentification par header X-API-Key.
 *
 * En l'absence de clé API (dev/local), le message est journalisé au lieu d'être
 * envoyé, ce qui permet de tester le flux OTP sans consommer de crédit.
 */
class InfiniReachSmsService
{
    private const ENDPOINT = 'https://api.infinireach.io/api/v1/messages';

    public function isConfigured(): bool
    {
        return !empty(config('services.infinireach.api_key'))
            && !empty(config('services.infinireach.from'));
    }

    /**
     * @throws RuntimeException si l'envoi échoue
     */
    public function send(string $to, string $message): void
    {
        if (!$this->isConfigured()) {
            Log::warning('InfiniReach non configuré — SMS non envoyé (mode dev)', [
                'to' => $to,
                'message' => $message,
            ]);
            return;
        }

        $response = Http::withHeaders([
            'X-API-Key' => config('services.infinireach.api_key'),
        ])->acceptJson()->post(self::ENDPOINT, [
            'to' => $to,
            'from' => config('services.infinireach.from'),
            'channel' => 'sms',
            'message' => $message,
        ]);

        // NOTE: la forme exacte de la réponse InfiniReach en cas d'échec logique
        // (ex: un 200 avec un statut d'erreur dans le corps) n'est pas documentée
        // ici — on se base pour l'instant uniquement sur le code HTTP. À ajuster
        // si l'API renvoie un statut dans le corps de la réponse.
        if (!$response->successful()) {
            Log::error('Échec envoi SMS InfiniReach', [
                'to' => $to,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new RuntimeException('Échec de l\'envoi du SMS');
        }
    }
}
