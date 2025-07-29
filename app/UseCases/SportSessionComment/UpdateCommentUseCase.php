<?php

namespace App\UseCases\SportSessionComment;

use App\Events\CommentUpdated;
use App\Repositories\SportSessionComment\SportSessionCommentRepositoryInterface;
use Illuminate\Support\Facades\Validator;

class UpdateCommentUseCase
{
    public function __construct(
        private SportSessionCommentRepositoryInterface $commentRepository
    ) {}

    public function execute(string $commentId, string $userId, string $content, ?array $mentions = null): array
    {
        // Validation des données
        $validator = Validator::make([
            'commentId' => $commentId,
            'userId' => $userId,
            'content' => $content,
            'mentions' => $mentions,
        ], [
            'commentId' => 'required|string',
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

        // Vérifier que le commentaire existe
        $existingComment = $this->commentRepository->findCommentById($commentId);
        if (!$existingComment) {
            return [
                'success' => false,
                'error' => [
                    'code' => 'COMMENT_NOT_FOUND',
                    'message' => 'Commentaire non trouvé',
                ],
            ];
        }

        // Vérifier que l'utilisateur peut modifier le commentaire
        if (!$this->commentRepository->userCanEditComment($commentId, $userId)) {
            return [
                'success' => false,
                'error' => [
                    'code' => 'FORBIDDEN',
                    'message' => 'Vous n\'êtes pas autorisé à modifier ce commentaire',
                ],
            ];
        }

        // Modifier le commentaire
        $comment = $this->commentRepository->updateComment($commentId, $content, $mentions);

        if (!$comment) {
            return [
                'success' => false,
                'error' => [
                    'code' => 'UPDATE_FAILED',
                    'message' => 'Échec de la modification du commentaire',
                ],
            ];
        }

        // Émettre l'événement WebSocket directement via SocketIOService
        try {
            $socketService = app(\App\Services\SocketIOService::class);
            $socketService->emitLaravelEvent(
                'comment.updated',
                'sport-session.' . $existingComment->sessionId,
                ['comment' => $comment->toArray()]
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Failed to emit WebSocket event", [
                'sessionId' => $existingComment->sessionId,
                'error' => $e->getMessage()
            ]);
        }

        return [
            'success' => true,
            'data' => $comment->toArray(),
            'message' => 'Commentaire modifié avec succès',
        ];
    }
}
