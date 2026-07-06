<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Envoi de SMS via Textbelt (https://docs.textbelt.com/#send-an-sms-using-http-post).
 * Un simple POST sur https://textbelt.com/text avec phone / message / key.
 *
 * En l'absence de clé configurée (dev/local), le message est journalisé au lieu
 * d'être envoyé, ce qui permet de tester le flux OTP sans consommer de crédit.
 */
class TextbeltSmsService
{
    private const ENDPOINT = 'https://textbelt.com/text';

    public function isConfigured(): bool
    {
        return !empty(config('services.textbelt.key'));
    }

    /**
     * @throws RuntimeException si l'envoi échoue
     */
    public function send(string $to, string $message): void
    {
        if (!$this->isConfigured()) {
            // Mode dev : on ne bloque pas, on logue le message (et le code) pour pouvoir tester.
            Log::warning('Textbelt non configuré — SMS non envoyé (mode dev)', [
                'to' => $to,
                'message' => $message,
            ]);
            return;
        }

        $response = Http::asForm()->post(self::ENDPOINT, [
            'phone' => $to,
            'message' => $message,
            'key' => config('services.textbelt.key'),
        ]);

        $data = $response->json();

        // Textbelt renvoie 200 avec { success: false, error: "..." } en cas de refus
        if ($response->failed() || !($data['success'] ?? false)) {
            Log::error('Échec envoi SMS Textbelt', [
                'to' => $to,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new RuntimeException('Échec de l\'envoi du SMS');
        }
    }
}
