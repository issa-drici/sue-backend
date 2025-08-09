<?php

namespace App\UseCases\SportSessionComment;

use App\Entities\SportSessionComment;
use App\Events\CommentCreated;
use App\Repositories\SportSession\SportSessionRepositoryInterface;
use App\Repositories\SportSessionComment\SportSessionCommentRepositoryInterface;
use App\Repositories\User\UserRepositoryInterface;
use Illuminate\Support\Facades\Validator;

class CreateCommentUseCase
{
    public function __construct(
        private SportSessionCommentRepositoryInterface $commentRepository,
        private SportSessionRepositoryInterface $sessionRepository,
        private UserRepositoryInterface $userRepository
    ) {}

    public function execute(string $sessionId, string $userId, string $content, ?array $mentions = null): array
    {
        // Validation des données
        $validator = Validator::make([
            'sessionId' => $sessionId,
            'userId' => $userId,
            'content' => $content,
            'mentions' => $mentions,
        ], [
            'sessionId' => 'required|uuid|exists:sport_sessions,id',
            'userId' => 'required|uuid|exists:users,id',
            'content' => 'required|string|min:1|max:1000',
            'mentions' => 'nullable|array',
            'mentions.*' => 'uuid|exists:users,id',
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

        // Vérifier que l'utilisateur est participant de la session
        $session = $this->sessionRepository->findById($sessionId);
        if (!$session) {
            return [
                'success' => false,
                'error' => [
                    'code' => 'SESSION_NOT_FOUND',
                    'message' => 'Session non trouvée',
                ],
            ];
        }

        // Vérifier que l'utilisateur est participant
        // $isParticipant = $this->sessionRepository->isUserParticipant($sessionId, $userId);
        // if (!$isParticipant) {
        //     return [
        //         'success' => false,
        //         'error' => [
        //             'code' => 'NOT_PARTICIPANT',
        //             'message' => 'Vous devez être participant de cette session pour commenter',
        //         ],
        //     ];
        // }

        // Créer le commentaire
        $comment = $this->commentRepository->createComment($sessionId, $userId, $content, $mentions);

        // Émettre l'événement Laravel Broadcasting (Soketi)
        try {
            broadcast(new CommentCreated($comment, $sessionId));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Failed to broadcast event", [
                'sessionId' => $sessionId,
                'error' => $e->getMessage()
            ]);
        }

        return [
            'success' => true,
            'data' => $comment->toArray(),
            'message' => 'Commentaire créé avec succès',
        ];
    }
}
