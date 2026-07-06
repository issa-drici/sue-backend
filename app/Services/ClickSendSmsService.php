<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Envoi de SMS via ClickSend (https://developers.clicksend.com/docs/rest/v3/#send-sms).
 * POST https://rest.clicksend.com/v3/sms/send en Basic Auth (username + clé API).
 *
 * En l'absence d'identifiants (dev/local), le message est journalisé au lieu d'être
 * envoyé, ce qui permet de tester le flux OTP sans consommer de crédit.
 */
class ClickSendSmsService
{
    private const ENDPOINT = 'https://rest.clicksend.com/v3/sms/send';

    public function isConfigured(): bool
    {
        return !empty(config('services.clicksend.username'))
            && !empty(config('services.clicksend.key'));
    }

    /**
     * @throws RuntimeException si l'envoi échoue
     */
    public function send(string $to, string $message): void
    {
        if (!$this->isConfigured()) {
            // Mode dev : on ne bloque pas, on logue le message (et le code) pour pouvoir tester.
            Log::warning('ClickSend non configuré — SMS non envoyé (mode dev)', [
                'to' => $to,
                'message' => $message,
            ]);
            return;
        }

        $sms = [
            'body' => $message,
            'to' => $to,
            'source' => 'php',
        ];

        // Sender ID alphanumérique (ex: "SUE") ou numéro expéditeur — optionnel.
        if ($from = config('services.clicksend.from')) {
            $sms['from'] = $from;
        }

        $response = Http::withBasicAuth(
            config('services.clicksend.username'),
            config('services.clicksend.key')
        )->acceptJson()->post(self::ENDPOINT, [
            'messages' => [$sms],
        ]);

        $data = $response->json();
        $responseCode = $data['response_code'] ?? null;
        $messageStatus = $data['data']['messages'][0]['status'] ?? null;

        // ClickSend renvoie HTTP 200 + response_code SUCCESS quand la requête est acceptée,
        // et un status par message ("SUCCESS" si mis en file d'envoi).
        $ok = $response->successful()
            && $responseCode === 'SUCCESS'
            && ($messageStatus === null || $messageStatus === 'SUCCESS');

        if (!$ok) {
            Log::error('Échec envoi SMS ClickSend', [
                'to' => $to,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new RuntimeException('Échec de l\'envoi du SMS');
        }
    }
}
