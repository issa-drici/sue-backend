<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Alertes de suivi envoyées à l'admin sur Telegram (bot @sue_app_alert_bot) :
 * inscriptions, échecs d'envoi de code SMS, codes jamais validés, erreurs serveur.
 *
 * Ne lève jamais d'exception : une alerte ratée ne doit pas casser la requête qui l'envoie.
 * Sans TELEGRAM_BOT_TOKEN / TELEGRAM_CHAT_ID (dev, tests), ne fait rien.
 */
class TelegramAlertService
{
    public function isConfigured(): bool
    {
        return !empty(config('services.telegram.bot_token'))
            && !empty(config('services.telegram.chat_id'));
    }

    public function send(string $text): void
    {
        if (!$this->isConfigured()) {
            return;
        }

        try {
            Http::timeout(5)->asJson()->post(
                'https://api.telegram.org/bot' . config('services.telegram.bot_token') . '/sendMessage',
                [
                    'chat_id' => config('services.telegram.chat_id'),
                    'text' => $text,
                    'disable_web_page_preview' => true,
                ]
            );
        } catch (\Throwable $e) {
            Log::warning('Alerte Telegram non envoyée', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Envoie l'alerte une seule fois par clé pendant $minutes (ex: la même erreur en boucle).
     */
    public function sendOnce(string $key, string $text, int $minutes = 30): void
    {
        if (!$this->isConfigured()) {
            return;
        }

        try {
            if (!Cache::add('telegram_alert:' . $key, true, now()->addMinutes($minutes))) {
                return;
            }
        } catch (\Throwable $e) {
            // Cache indisponible : mieux vaut un doublon qu'une alerte perdue
        }

        $this->send($text);
    }
}
