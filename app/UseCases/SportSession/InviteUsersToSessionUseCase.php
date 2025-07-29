<?php

namespace App\UseCases\SportSession;

use App\Repositories\SportSession\SportSessionRepositoryInterface;
use App\Repositories\User\UserRepositoryInterface;
use App\Repositories\Notification\NotificationRepositoryInterface;
use App\Repositories\PushToken\PushTokenRepositoryInterface;
use App\Services\ExpoPushNotificationService;

class InviteUsersToSessionUseCase
{
    public function __construct(
        private SportSessionRepositoryInterface $sportSessionRepository,
        private UserRepositoryInterface $userRepository,
        private NotificationRepositoryInterface $notificationRepository,
        private PushTokenRepositoryInterface $pushTokenRepository,
        private ExpoPushNotificationService $expoPushService
    ) {}

    public function execute(string $sessionId, string $inviterId, array $userIds): array
    {
        // Vérifier que la session existe
        $session = $this->sportSessionRepository->findById($sessionId);
        if (!$session) {
            return [
                'success' => false,
                'error' => [
                    'code' => 'SESSION_NOT_FOUND',
                    'message' => 'Session non trouvée'
                ]
            ];
        }

        // Vérifier que l'inviteur est le créateur de la session
        // if ($session->getOrganizer()->getId() !== $inviterId) {
        //     return [
        //         'success' => false,
        //         'error' => [
        //             'code' => 'FORBIDDEN',
        //             'message' => 'Seul le créateur de la session peut inviter des utilisateurs'
        //         ]
        //     ];
        // }

        $invitedUsers = [];
        $errors = [];

        foreach ($userIds as $userId) {
            // Vérifier que l'utilisateur existe
            $user = $this->userRepository->findById($userId);
            if (!$user) {
                $errors[] = "Utilisateur $userId non trouvé";
                continue;
            }

            // Vérifier que l'utilisateur n'est pas déjà invité ou participant
            if (
                $this->sportSessionRepository->isUserInvited($sessionId, $userId) ||
                $this->sportSessionRepository->isUserParticipant($sessionId, $userId)
            ) {
                $errors[] = "L'utilisateur {$user->getFirstname()} {$user->getLastname()} est déjà invité ou participe déjà";
                continue;
            }

            // Ajouter l'invitation
            try {
                $invited = $this->sportSessionRepository->inviteUser($sessionId, $userId);
                if ($invited) {
                    $invitedUsers[] = [
                        'id' => $user->getId(),
                        'firstname' => $user->getFirstname(),
                        'lastname' => $user->getLastname(),
                        'email' => $user->getEmail()
                    ];

                    // Créer une notification pour l'utilisateur invité
                    $notification = $this->notificationRepository->create([
                        'user_id' => $userId,
                        'type' => 'invitation',
                        'title' => 'Invitation à une session sportive',
                        'message' => "Vous avez été invité à participer à une session de {$session->getSport()} le {$session->getDate()} à {$session->getTime()}",
                        'session_id' => $sessionId
                    ]);

                    // Envoyer une notification push
                    $this->sendPushNotification($userId, $session, $notification);
                }
            } catch (\Exception $e) {
                $errors[] = "Erreur lors de l'invitation de {$user->getFirstname()} {$user->getLastname()}: " . $e->getMessage();
            }
        }

        if (empty($invitedUsers)) {
            return [
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Aucun utilisateur n\'a pu être invité',
                    'details' => $errors
                ]
            ];
        }

        return [
            'success' => true,
            'data' => [
                'sessionId' => $sessionId,
                'invitedUsers' => $invitedUsers,
                'errors' => $errors
            ],
            'message' => count($invitedUsers) . ' utilisateur(s) invité(s) avec succès'
        ];
    }

    /**
     * Envoie une notification push à l'utilisateur invité
     */
    private function sendPushNotification(string $userId, $session, $notification): void
    {
        try {
            // Récupérer les tokens push de l'utilisateur
            $pushTokens = $this->pushTokenRepository->getTokensForUser($userId);

            if (empty($pushTokens)) {
                \Illuminate\Support\Facades\Log::info("Aucun token push trouvé pour l'utilisateur", [
                    'userId' => $userId
                ]);
                return;
            }

            // Préparer le message de notification
            $title = '🏃‍♂️ Invitation à une session sportive';
            $body = "Vous avez été invité à participer à une session de {$session->getSport()} le {$session->getDate()} à {$session->getTime()}";

            // Données supplémentaires pour l'app mobile
            $data = [
                'type' => 'session_invitation',
                'session_id' => $session->getId(),
                'notification_id' => $notification->getId(),
                'sport' => $session->getSport(),
                'date' => $session->getDate(),
                'time' => $session->getTime(),
                'location' => $session->getLocation()
            ];

            // Envoyer la notification push
            $result = $this->expoPushService->sendNotification(
                $pushTokens,
                $title,
                $body,
                $data
            );

            // Marquer la notification comme envoyée par push
            $this->notificationRepository->markAsPushSent($notification->getId(), $result);

            \Illuminate\Support\Facades\Log::info("Notification push envoyée pour invitation", [
                'userId' => $userId,
                'sessionId' => $session->getId(),
                'tokensCount' => count($pushTokens),
                'result' => $result
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Erreur lors de l'envoi de notification push", [
                'userId' => $userId,
                'sessionId' => $session->getId(),
                'error' => $e->getMessage()
            ]);
        }
    }
}
