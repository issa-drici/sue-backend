<?php

namespace App\Console\Commands;

use App\Models\PhoneVerificationCodeModel;
use App\Models\UserModel;
use App\Services\TelegramAlertService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * Repère les personnes bloquées à la connexion et prévient l'admin sur Telegram :
 *  - code SMS demandé mais jamais validé (SMS pas reçu, ou abandon) ;
 *  - code validé mais inscription (prénom/nom) jamais terminée ;
 *  - SMS refusés par le téléphone SMS Gateway (éteint, permission manquante...).
 *
 * Planifiée toutes les minutes (routes/console.php). Chaque cas n'est signalé qu'une fois.
 */
class CheckSignupAlertsCommand extends Command
{
    protected $signature = 'alerts:check-signups';

    protected $description = 'Alerte Telegram pour les codes SMS non validés et les SMS en échec';

    // Délai laissé à la personne pour saisir son code avant de la considérer bloquée : avant
    // l'expiration du code (5 min), pour pouvoir l'aider pendant qu'il est encore valable
    private const STUCK_AFTER_MINUTES = 3;

    // Au-delà, le cas est trop ancien pour être signalé (évite de tout ressortir au premier lancement)
    private const LOOKBACK_MINUTES = 60;

    public function handle(TelegramAlertService $alerts): int
    {
        if (!$alerts->isConfigured()) {
            return self::SUCCESS;
        }

        $this->checkStuckCodes($alerts);
        $this->checkFailedSms($alerts);

        return self::SUCCESS;
    }

    private function checkStuckCodes(TelegramAlertService $alerts): void
    {
        $reviewPhone = config('services.review_account.phone');

        // Un code validé puis utilisé (connexion, inscription) est supprimé : ce qui reste est bloqué
        $records = PhoneVerificationCodeModel::query()
            ->whereBetween('last_sent_at', [
                now()->subMinutes(self::LOOKBACK_MINUTES),
                now()->subMinutes(self::STUCK_AFTER_MINUTES),
            ])
            ->get();

        foreach ($records as $record) {
            if ($reviewPhone && $record->phone === $reviewPhone) {
                continue;
            }

            $existingUser = UserModel::where('phone', $record->phone)->exists();
            $sentAt = $record->last_sent_at->copy()->setTimezone('Europe/Paris')->format('H:i');

            if ($record->verified_at) {
                if ($existingUser) {
                    continue; // changement de numéro / suppression de compte entamés, pas une inscription
                }
                $text = "🟠 Inscription pas terminée\n{$record->phone} a validé son code à {$sentAt} mais n'a pas rempli prénom/nom.";
            } else {
                $who = $existingUser ? 'Utilisateur existant' : 'Nouveau numéro';
                $text = "🔴 Code SMS pas encore validé\n{$record->phone} ({$who}) a demandé un code à {$sentAt}, sans le saisir. "
                    . "SMS pas reçu ou abandon : à recontacter.";
                if ($record->expires_at->isFuture()) {
                    $text .= "\nLe code expire à " . $record->expires_at->copy()->setTimezone('Europe/Paris')->format('H:i') . '.';
                }
                if ($record->attempts > 0) {
                    $text .= "\n{$record->attempts} code(s) faux saisi(s).";
                }
            }

            $alerts->sendOnce('stuck:' . $record->phone . ':' . $record->last_sent_at->timestamp, $text, 24 * 60);
        }
    }

    private function checkFailedSms(TelegramAlertService $alerts): void
    {
        if (empty(config('services.smsgate.username')) || empty(config('services.smsgate.password'))) {
            return;
        }

        try {
            $response = Http::withBasicAuth(config('services.smsgate.username'), config('services.smsgate.password'))
                ->acceptJson()->timeout(10)
                ->get(rtrim(config('services.smsgate.url'), '/') . '/3rdparty/v1/messages', ['limit' => 50]);
        } catch (\Throwable $e) {
            $alerts->sendOnce('smsgate:unreachable', "⚠️ API SMS Gateway injoignable : {$e->getMessage()}", 60);
            return;
        }

        if (!$response->successful()) {
            $alerts->sendOnce('smsgate:http', "⚠️ API SMS Gateway en erreur (HTTP {$response->status()}). Identifiants à vérifier.", 60);
            return;
        }

        $messages = $response->json();
        $messages = is_array($messages) && array_is_list($messages) ? $messages : ($messages['items'] ?? []);

        foreach ($messages as $message) {
            $failedAt = $message['states']['Failed'] ?? null;
            if (($message['state'] ?? null) !== 'Failed' || empty($message['id']) || !$failedAt) {
                continue;
            }
            if (\Carbon\Carbon::parse($failedAt)->lt(now()->subMinutes(self::LOOKBACK_MINUTES))) {
                continue;
            }
            $errors = collect($message['recipients'] ?? [])->pluck('error')->filter()->unique()->implode("\n");

            // SMS Gateway ne renvoie pas le numéro en clair : l'alerte « code jamais validé » le donne
            $alerts->sendOnce(
                'smsgate:failed:' . $message['id'],
                "🔴 SMS non envoyé par le téléphone\n" . ($errors ?: 'Raison inconnue') . "\nVérifie le téléphone Android (allumé, réseau, autorisation SMS).",
                7 * 24 * 60
            );
        }
    }
}
