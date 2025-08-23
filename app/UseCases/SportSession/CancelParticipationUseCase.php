<?php

namespace App\UseCases\SportSession;

use App\Entities\SportSession;
use App\Repositories\SportSession\SportSessionRepositoryInterface;
use App\Repositories\Notification\NotificationRepositoryInterface;
use App\Repositories\User\UserRepositoryInterface;
use App\Repositories\PushToken\PushTokenRepositoryInterface;
use App\Services\ExpoPushNotificationService;
use Exception;
use Illuminate\Support\Facades\Validator;

class CancelParticipationUseCase
{
    public function __construct(
        private SportSessionRepositoryInterface $sportSessionRepository,
        private NotificationRepositoryInterface $notificationRepository,
        private UserRepositoryInterface $userRepository,
        private PushTokenRepositoryInterface $pushTokenRepository,
        private ExpoPushNotificationService $expoPushService
    ) {}

    public function execute(string $sessionId, string $userId): array
    {
        // Validation des données de base
        $validator = Validator::make([
            'sessionId' => $sessionId,
            'userId' => $userId,
        ], [
            'sessionId' => 'required|uuid',
            'userId' => 'required|uuid|exists:users,id',
        ]);

        if ($validator->fails()) {
            return [
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Données invalides',
                    'details' => $validator->errors()->toArray(),
                ],
            ];
        }

        // Récupérer la session
        $session = $this->sportSessionRepository->findById($sessionId);

        if (!$session) {
            return [
                'success' => false,
                'error' => [
                    'code' => 'SESSION_NOT_FOUND',
                    'message' => 'Session non trouvée',
                ],
            ];
        }

        // Vérifier que l'utilisateur n'est pas l'organisateur
        if ($session->isOrganizer($userId)) {
            return [
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Vous n\'êtes pas autorisé à annuler votre participation à cette session',
                ],
            ];
        }

        // Vérifier que la session n'est pas terminée
        $sessionDate = new \DateTime($session->getDate());
        $today = new \DateTime();
        if ($sessionDate < $today) {
            return [
                'success' => false,
                'error' => [
                    'code' => 'SESSION_ENDED',
                    'message' => 'Impossible d\'annuler la participation à une session terminée',
                ],
            ];
        }

        // Vérifier que l'utilisateur est un participant avec le statut 'accepted'
        $participant = $this->sportSessionRepository->findParticipant($sessionId, $userId);

        if (!$participant) {
            return [
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Vous n\'êtes pas autorisé à annuler votre participation à cette session',
                ],
            ];
        }

        if ($participant['status'] !== 'accepted') {
            return [
                'success' => false,
                'error' => [
                    'code' => 'USER_NOT_ACCEPTED',
                    'message' => 'Vous n\'avez pas accepté l\'invitation à cette session',
                ],
            ];
        }

        // Mettre à jour le statut du participant de 'accepted' à 'declined'
        $success = $this->sportSessionRepository->updateParticipantStatus($sessionId, $userId, 'declined');

        if (!$success) {
            return [
                'success' => false,
                'error' => [
                    'code' => 'UPDATE_FAILED',
                    'message' => 'Erreur lors de la mise à jour du statut',
                ],
            ];
        }

        // Créer des notifications pour tous les participants actifs
        $this->createCancellationNotifications($session, $userId);

        // Envoyer des notifications push à tous les participants actifs
        $this->sendPushNotifications($session, $userId);

        // Ajouter un commentaire système pour informer de l'annulation
        $this->createCancellationComment($session, $userId);

        // Récupérer la session mise à jour
        $updatedSession = $this->sportSessionRepository->findById($sessionId);

        if (!$updatedSession) {
            return [
                'success' => false,
                'error' => [
                    'code' => 'SESSION_UPDATE_FAILED',
                    'message' => 'Erreur lors de la récupération de la session mise à jour',
                ],
            ];
        }

        return [
            'success' => true,
            'message' => 'Participation annulée avec succès',
            'data' => [
                'session' => $updatedSession->toArray(),
            ],
        ];
    }

    private function createCancellationNotifications(SportSession $session, string $userId): void
    {
        try {
            $user = $this->userRepository->findById($userId);
            $userName = $user ? ($user->getFirstname() . ' ' . $user->getLastname()) : 'Un participant';

            // Récupérer tous les participants avec le statut 'accepted'
            $participants = $this->sportSessionRepository->findById($session->getId())->getParticipants();

            foreach ($participants as $participant) {
                $participantId = $participant['id'] ?? null;

                // Exclure l'utilisateur qui annule sa participation
                if ($participantId === $userId) {
                    continue;
                }

                // Exclure les participants qui ont déjà décliné
                if (($participant['status'] ?? '') !== 'accepted') {
                    continue;
                }

                $notification = $this->notificationRepository->create([
                    'user_id' => $participantId,
                    'type' => 'update',
                    'title' => 'Participation annulée',
                    'message' => "{$userName} a annulé sa participation à la session de {$session->getSport()}",
                    'session_id' => $session->getId(),
                ]);

                // Marquer la notification comme envoyée par push
                $this->notificationRepository->markAsPushSent($notification->getId(), [
                    'type' => 'session_update',
                    'session_id' => $session->getId(),
                    'user_id' => $userId,
                    'action' => 'participation_cancelled',
                    'previous_status' => 'accepted',
                    'new_status' => 'declined',
                ]);

                \Illuminate\Support\Facades\Log::info("Notification créée pour participant", [
                    'sessionId' => $session->getId(),
                    'userId' => $userId,
                    'recipientId' => $participantId,
                ]);
            }

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Erreur lors de la création des notifications d\'annulation', [
                'sessionId' => $session->getId(),
                'userId' => $userId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function sendPushNotifications(SportSession $session, string $userId): void
    {
        try {
            $user = $this->userRepository->findById($userId);
            $userName = $user ? ($user->getFirstname() . ' ' . $user->getLastname()) : 'Un participant';

            // Récupérer tous les participants avec le statut 'accepted'
            $participants = $this->sportSessionRepository->findById($session->getId())->getParticipants();

            foreach ($participants as $participant) {
                $participantId = $participant['id'] ?? null;

                // Exclure l'utilisateur qui annule sa participation
                if ($participantId === $userId) {
                    continue;
                }

                // Exclure les participants qui ont déjà décliné
                if (($participant['status'] ?? '') !== 'accepted') {
                    continue;
                }

                try {
                    // Récupérer les tokens push du participant
                    $pushTokens = $this->pushTokenRepository->getTokensForUser($participantId);

                    if (empty($pushTokens)) {
                        \Illuminate\Support\Facades\Log::info("Aucun token push trouvé pour le participant", [
                            'participantId' => $participantId,
                        ]);
                        continue;
                    }

                    // Préparer le message de notification
                    $title = '❌ Participation annulée';
                    $body = "{$userName} a annulé sa participation à la session de {$session->getSport()}";

                    // Données supplémentaires pour l'app mobile
                    $data = [
                        'type' => 'session_update',
                        'session_id' => $session->getId(),
                        'user_id' => $userId,
                        'action' => 'participation_cancelled',
                        'previous_status' => 'accepted',
                        'new_status' => 'declined',
                        'sport' => $session->getSport(),
                        'date' => $session->getDate(),
                        'time' => $session->getTime(),
                        'location' => $session->getLocation(),
                    ];

                    // Envoyer la notification push
                    $result = $this->expoPushService->sendNotification(
                        $pushTokens,
                        $title,
                        $body,
                        $data
                    );

                    \Illuminate\Support\Facades\Log::info("Notification push envoyée pour annulation de participation", [
                        'participantId' => $participantId,
                        'sessionId' => $session->getId(),
                        'userId' => $userId,
                        'tokensCount' => count($pushTokens),
                        'result' => $result,
                    ]);

                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Erreur lors de l\'envoi de notification push pour annulation', [
                        'sessionId' => $session->getId(),
                        'userId' => $userId,
                        'participantId' => $participantId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Erreur lors de l\'envoi des notifications push pour annulation', [
                'sessionId' => $session->getId(),
                'userId' => $userId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function createCancellationComment(SportSession $session, string $userId): void
    {
        try {
            $user = $this->userRepository->findById($userId);
            $userName = $user ? ($user->getFirstname() . ' ' . $user->getLastname()) : 'Un participant';

            // Créer un commentaire système
            $commentContent = "{$userName} a annulé sa participation à cette session.";

            $success = $this->sportSessionRepository->addComment($session->getId(), $userId, $commentContent);

            if ($success) {
                \Illuminate\Support\Facades\Log::info("Commentaire système créé pour annulation de participation", [
                    'sessionId' => $session->getId(),
                    'userId' => $userId,
                    'commentContent' => $commentContent,
                ]);
            } else {
                \Illuminate\Support\Facades\Log::error("Échec de la création du commentaire système", [
                    'sessionId' => $session->getId(),
                    'userId' => $userId,
                ]);
            }

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Erreur lors de la création du commentaire système', [
                'sessionId' => $session->getId(),
                'userId' => $userId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
