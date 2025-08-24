<?php

namespace App\UseCases\SportSession;

use App\Repositories\SportSession\SportSessionRepositoryInterface;
use App\Repositories\User\UserRepositoryInterface;
use App\Repositories\Notification\NotificationRepositoryInterface;
use App\Repositories\PushToken\PushTokenRepositoryInterface;
use App\Services\ExpoPushNotificationService;
use App\Services\DateFormatterService;

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
        $newInvitations = 0;
        $reinvitations = 0;

        foreach ($userIds as $userId) {
            // Vérifier que l'utilisateur existe
            $user = $this->userRepository->findById($userId);
            if (!$user) {
                $errors[] = "Utilisateur $userId non trouvé";
                continue;
            }

            // Vérifier que l'utilisateur n'est pas déjà participant (accepté)
            if ($this->sportSessionRepository->isUserParticipant($sessionId, $userId)) {
                $errors[] = "L'utilisateur {$user->getFirstname()} {$user->getLastname()} participe déjà à cette session";
                continue;
            }

            // Vérifier si l'utilisateur est déjà invité (pending)
            $isAlreadyInvited = $this->sportSessionRepository->isUserInvited($sessionId, $userId);

            // Vérifier si l'utilisateur a déjà été invité mais a décliné
            $existingParticipant = $this->sportSessionRepository->findParticipant($sessionId, $userId);
            $wasDeclined = $existingParticipant && $existingParticipant['status'] === 'declined';
            $isReinvitation = $wasDeclined;



            // Ajouter ou mettre à jour l'invitation
            try {
                $invited = false;

                if ($isAlreadyInvited) {
                    // L'utilisateur est déjà invité (pending), pas besoin de faire quoi que ce soit
                    $invited = true;
                } else {
                    if ($isReinvitation) {
                        // Mettre à jour le statut de 'declined' à 'pending'
                        $invited = $this->sportSessionRepository->updateParticipantStatus($sessionId, $userId, 'pending');
                    } else {
                        // Créer une nouvelle invitation
                        $invited = $this->sportSessionRepository->inviteUser($sessionId, $userId);
                    }
                }

                if ($invited) {
                    $invitedUsers[] = [
                        'id' => $user->getId(),
                        'firstname' => $user->getFirstname(),
                        'lastname' => $user->getLastname(),
                        'email' => $user->getEmail()
                    ];

                    // Déterminer si on doit créer une notification
                    $shouldCreateNotification = $wasDeclined || !$isAlreadyInvited;



                    if ($shouldCreateNotification) {
                        // Déterminer le type de message selon le cas
                        $notificationTitle = $wasDeclined
                            ? DateFormatterService::generateInvitationTitle($session->getSport()) . ' (Nouvelle)'
                            : DateFormatterService::generateInvitationTitle($session->getSport());
                        $notificationMessage = DateFormatterService::generateInvitationMessage($session->getSport(), $session->getDate(), $session->getTime());

                        // Créer une notification pour l'utilisateur invité
                        $notification = $this->notificationRepository->create([
                            'user_id' => $userId,
                            'type' => 'invitation',
                            'title' => $notificationTitle,
                            'message' => $notificationMessage,
                            'session_id' => $sessionId
                        ]);

                        // Envoyer une notification push
                        $this->sendPushNotification($userId, $session, $notification, $wasDeclined);
                    }

                    // Mettre à jour les compteurs
                    if ($wasDeclined) {
                        $reinvitations++;
                    } else {
                        $newInvitations++;
                    }
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

        // Construire le message de retour
        $message = '';
        if ($newInvitations > 0 && $reinvitations > 0) {
            $message = "{$newInvitations} nouvelle(s) invitation(s) et {$reinvitations} réinvitation(s) envoyées avec succès";
        } elseif ($newInvitations > 0) {
            $message = "{$newInvitations} utilisateur(s) invité(s) avec succès";
        } elseif ($reinvitations > 0) {
            $message = "{$reinvitations} utilisateur(s) réinvité(s) avec succès";
        } else {
            $message = "Aucune invitation envoyée";
        }

        return [
            'success' => true,
            'data' => [
                'sessionId' => $sessionId,
                'invitedUsers' => $invitedUsers,
                'errors' => $errors,
                'newInvitations' => $newInvitations,
                'reinvitations' => $reinvitations
            ],
            'message' => $message
        ];
    }

    /**
     * Envoie une notification push à l'utilisateur invité
     */
    private function sendPushNotification(string $userId, $session, $notification, bool $isReinvitation = false): void
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
            $title = $isReinvitation
                ? DateFormatterService::generatePushReinvitationTitle($session->getSport())
                : DateFormatterService::generatePushInvitationTitle($session->getSport());
            $body = DateFormatterService::generateInvitationMessage($session->getSport(), $session->getDate(), $session->getTime());

            // Données supplémentaires pour l'app mobile
            $data = [
                'type' => 'session_invitation',
                'session_id' => $session->getId(),
                'notification_id' => $notification->getId(),
                'sport' => $session->getSport(),
                'date' => $session->getDate(),
                'time' => $session->getTime(),
                'location' => $session->getLocation(),
                'is_reinvitation' => $isReinvitation
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
                'result' => $result,
                'isReinvitation' => $isReinvitation
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
