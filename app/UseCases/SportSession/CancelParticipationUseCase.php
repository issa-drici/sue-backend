<?php

namespace App\UseCases\SportSession;

use App\Entities\SportSession;
use App\Repositories\SportSession\SportSessionRepositoryInterface;
use App\Repositories\User\UserRepositoryInterface;
use App\Services\DateFormatterService;
use App\Services\SessionNotificationService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CancelParticipationUseCase
{
    public function __construct(
        private SportSessionRepositoryInterface $sportSessionRepository,
        private UserRepositoryInterface $userRepository,
        private SessionNotificationService $sessionNotificationService
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
        if ($session->getEndDate() < now()) {
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

        // $session est lue avant le désistement : elle dit si la session était complète
        $this->notifyCancellation($session, $userId);

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

    /**
     * Prévient les joueurs confirmés et l'organisateur, et les invités en attente si une place
     * vient de se libérer dans une session complète (sinon ils pouvaient déjà répondre : pas de
     * relance à chaque désistement). Les joueurs qui ont refusé ne sont pas relancés.
     */
    private function notifyCancellation(SportSession $session, string $userId): void
    {
        try {
            $user = $this->userRepository->findById($userId);
            $firstname = $user ? $user->getFirstname() : 'Un joueur';
            $sessionLabel = $this->sessionLabel($session);

            $max = $session->getMaxParticipants();
            $acceptedBefore = $session->getAcceptedParticipantsCount();
            $missing = $max !== null ? $max - ($acceptedBefore - 1) : 0;
            $placeFreed = $session->isScheduled() && $max !== null && $acceptedBefore >= $max;

            $confirmedMessage = $missing > 0
                ? "{$sessionLabel} · il manque {$missing} joueur" . ($missing > 1 ? 's' : '')
                : $sessionLabel;

            foreach ($session->getParticipants() as $participant) {
                $participantId = $participant['id'] ?? null;
                if (!$participantId || $participantId === $userId) {
                    continue;
                }

                $status = $participant['status'] ?? '';
                if ($status === 'accepted') {
                    $this->sessionNotificationService->notify(
                        $participantId,
                        $session->getId(),
                        "{$firstname} ne vient plus",
                        $confirmedMessage
                    );
                } elseif ($status === 'pending' && $placeFreed) {
                    $this->sessionNotificationService->notify(
                        $participantId,
                        $session->getId(),
                        'Une place vient de se libérer',
                        "{$sessionLabel}. Réponds vite pour la prendre."
                    );
                }
            }
        } catch (\Throwable $e) {
            Log::error("Erreur lors de l'envoi des notifications d'annulation", [
                'sessionId' => $session->getId(),
                'userId' => $userId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /** "Football, jeudi 5 novembre à 19h00" ; sans date tant que le créneau n'est pas choisi */
    private function sessionLabel(SportSession $session): string
    {
        $sportName = DateFormatterService::getSportName($session->getSport());

        return $session->isScheduled()
            ? $sportName . ', ' . DateFormatterService::formatDateAndTime($session->getDate(), $session->getStartTime())
            : $sportName;
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
