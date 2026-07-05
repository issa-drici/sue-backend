<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Envoi de SMS via l'API REST Twilio (pas besoin du SDK : on utilise le client HTTP).
 *
 * En l'absence d'identifiants Twilio configurés (dev/local), le message est simplement
 * journalisé au lieu d'être envoyé, ce qui permet de tester le flux OTP sans Twilio.
 */
class TwilioSmsService
{
    public function isConfigured(): bool
    {
        return !empty(config('services.twilio.sid'))
            && !empty(config('services.twilio.token'))
            && !empty(config('services.twilio.from'));
    }

    /**
     * @throws RuntimeException si l'envoi Twilio échoue
     */
    public function send(string $to, string $message): void
    {
        if (!$this->isConfigured()) {
            // Mode dev : on ne bloque pas, on logue le message (et le code) pour pouvoir tester.
            Log::warning('Twilio non configuré — SMS non envoyé (mode dev)', [
                'to' => $to,
                'message' => $message,
            ]);
            return;
        }

        $sid = config('services.twilio.sid');
        $from = config('services.twilio.from');

        $payload = [
            'To' => $to,
            'Body' => $message,
        ];

        // Un Messaging Service SID commence par "MG", sinon c'est un numéro expéditeur.
        if (str_starts_with((string) $from, 'MG')) {
            $payload['MessagingServiceSid'] = $from;
        } else {
            $payload['From'] = $from;
        }

        $response = Http::asForm()
            ->withBasicAuth($sid, config('services.twilio.token'))
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", $payload);

        if ($response->failed()) {
            Log::error('Échec envoi SMS Twilio', [
                'to' => $to,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new RuntimeException('Échec de l\'envoi du SMS');
        }
    }
}
