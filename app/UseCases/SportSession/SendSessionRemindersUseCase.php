<?php

namespace App\UseCases\SportSession;

use App\Repositories\SportSession\SportSessionRepositoryInterface;
use App\Repositories\Notification\NotificationRepositoryInterface;
use App\Repositories\PushToken\PushTokenRepositoryInterface;
use App\Services\ExpoPushNotificationService;
use App\Services\DateFormatterService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SendSessionRemindersUseCase
{
    public function __construct(
        private SportSessionRepositoryInterface $sportSessionRepository,
        private NotificationRepositoryInterface $notificationRepository,
        private PushTokenRepositoryInterface $pushTokenRepository,
        private ExpoPushNotificationService $expoPushService
    ) {}

    /**
     * Envoie les rappels pour toutes les sessions qui en ont besoin
     */
    public function execute(): array
    {
        $results = [
            'reminder_24h' => ['sent' => 0, 'skipped' => 0, 'errors' => []],
            'reminder_1h' => ['sent' => 0, 'skipped' => 0, 'errors' => []],
            'reminder_start' => ['sent' => 0, 'skipped' => 0, 'errors' => []],
        ];

        // Rappels 24h avant
        $this->sendReminders24h($results['reminder_24h']);

        // Rappels 1h avant
        $this->sendReminders1h($results['reminder_1h']);

        // Rappels au démarrage
        $this->sendRemindersStart($results['reminder_start']);

        return $results;
    }

    /**
     * Envoie les rappels 24h avant la session
     */
    private function sendReminders24h(array &$result): void
    {
        try {
            // Calculer la date/heure cible en Europe/Paris (24h avant le début de la session)
            $targetDateTime = Carbon::now('Europe/Paris')->addHours(24);
            $targetDate = $targetDateTime->format('Y-m-d');
            $targetTime = $targetDateTime->format('H:i');

            // Trouver les sessions qui commencent dans 24h (avec une marge de 1 minute)
            $sessions = $this->findSessionsForReminder($targetDate, $targetTime, 1);

            foreach ($sessions as $session) {
                $this->sendReminderToSessionParticipants(
                    $session,
                    'reminder_24h',
                    DateFormatterService::generateReminder24hTitle($session->getSport()),
                    DateFormatterService::generateReminder24hMessage(
                        $session->getSport(),
                        $session->getDate(),
                        $session->getStartTime(),
                        $session->getEndTime()
                    ),
                    $result
                );
            }
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'envoi des rappels 24h', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $result['errors'][] = $e->getMessage();
        }
    }

    /**
     * Envoie les rappels 1h avant la session
     */
    private function sendReminders1h(array &$result): void
    {
        try {
            // Calculer la date/heure cible en Europe/Paris (1h avant le début de la session)
            $targetDateTime = Carbon::now('Europe/Paris')->addHour();
            $targetDate = $targetDateTime->format('Y-m-d');
            $targetTime = $targetDateTime->format('H:i');

            // Trouver les sessions qui commencent dans 1h (avec une marge de 1 minute)
            $sessions = $this->findSessionsForReminder($targetDate, $targetTime, 1);

            foreach ($sessions as $session) {
                $this->sendReminderToSessionParticipants(
                    $session,
                    'reminder_1h',
                    DateFormatterService::generateReminder1hTitle($session->getSport()),
                    DateFormatterService::generateReminder1hMessage(
                        $session->getSport(),
                        $session->getDate(),
                        $session->getStartTime(),
                        $session->getEndTime()
                    ),
                    $result
                );
            }
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'envoi des rappels 1h', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $result['errors'][] = $e->getMessage();
        }
    }

    /**
     * Envoie les rappels au démarrage de la session
     */
    private function sendRemindersStart(array &$result): void
    {
        try {
            // Calculer la date/heure actuelle en Europe/Paris
            $now = Carbon::now('Europe/Paris');
            $targetDate = $now->format('Y-m-d');
            $targetTime = $now->format('H:i');

            // Trouver les sessions qui commencent maintenant (avec une marge de 1 minute)
            $sessions = $this->findSessionsForReminder($targetDate, $targetTime, 1);

            foreach ($sessions as $session) {
                $this->sendReminderToSessionParticipants(
                    $session,
                    'reminder_start',
                    DateFormatterService::generateReminderStartTitle($session->getSport()),
                    DateFormatterService::generateReminderStartMessage($session->getSport()),
                    $result
                );
            }
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'envoi des rappels au démarrage', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $result['errors'][] = $e->getMessage();
        }
    }

    /**
     * Trouve les sessions qui correspondent à la date/heure cible (avec une marge de tolérance)
     * Optimisé : utilise une requête SQL directe au lieu de parcourir toutes les sessions
     */
    private function findSessionsForReminder(string $targetDate, string $targetTime, int $marginMinutes = 1): array
    {
        // Le repository gère maintenant le parsing de l'heure avec Carbon
        // Pas besoin de formater ici, on passe directement la valeur
        return $this->sportSessionRepository->findByDateAndTime($targetDate, $targetTime, $marginMinutes);
    }

    /**
     * Envoie un rappel à tous les participants d'une session
     */
    private function sendReminderToSessionParticipants(
        $session,
        string $reminderType,
        string $title,
        string $message,
        array &$result
    ): void {
        try {
            // Récupérer tous les participants (y compris l'organisateur)
            $participants = $session->getParticipants();
            $organizer = $session->getOrganizer();

            // Créer une liste de tous les utilisateurs à notifier
            $usersToNotify = [];

            // Ajouter l'organisateur
            $usersToNotify[] = [
                'id' => $organizer->getId(),
                'fullName' => $organizer->getFirstname() . ' ' . $organizer->getLastname()
            ];

            // Ajouter les participants acceptés uniquement
            foreach ($participants as $participant) {
                if ($participant['status'] === 'accepted' && $participant['id'] !== $organizer->getId()) {
                    $usersToNotify[] = $participant;
                }
            }

            // Envoyer le rappel à chaque utilisateur
            foreach ($usersToNotify as $user) {
                $userId = $user['id'];

                // Vérifier si le rappel a déjà été envoyé
                if ($this->notificationRepository->hasReminderNotification($userId, $session->getId(), $reminderType)) {
                    $result['skipped']++;
                    continue;
                }

                // Vérifier d'abord si l'utilisateur a des tokens push avant de créer la notification
                $pushTokens = $this->pushTokenRepository->getTokensForUser($userId);

                if (empty($pushTokens)) {
                    $result['skipped']++;
                    continue;
                }

                // Créer la notification dans la base de données seulement si on peut envoyer le push
                $notification = $this->notificationRepository->create([
                    'user_id' => $userId,
                    'type' => 'reminder',
                    'title' => $title,
                    'message' => $message,
                    'session_id' => $session->getId(),
                ]);

                // Préparer les données pour la notification push
                $data = [
                    'type' => 'reminder',
                    'reminder_type' => $reminderType,
                    'session_id' => $session->getId(),
                    'notification_id' => $notification->getId(),
                    'sport' => $session->getSport(),
                    'date' => $session->getDate(),
                    'startTime' => $session->getStartTime(),
                    'endTime' => $session->getEndTime(),
                    'location' => $session->getLocation(),
                ];

                // Envoyer la notification push
                $pushResult = $this->expoPushService->sendNotification(
                    $pushTokens,
                    $title,
                    $message,
                    $data
                );

                // Marquer la notification comme envoyée par push avec le reminder_type dans push_data
                $pushDataWithReminderType = array_merge($pushResult, ['reminder_type' => $reminderType]);
                $this->notificationRepository->markAsPushSent($notification->getId(), $pushDataWithReminderType);

                $result['sent']++;
            }
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'envoi du rappel à une session', [
                'sessionId' => $session->getId(),
                'reminderType' => $reminderType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $result['errors'][] = "Session {$session->getId()}: " . $e->getMessage();
        }
    }
}

