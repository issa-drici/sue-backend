<?php

namespace App\UseCases\SportSessionComment;

use App\Events\CommentDeleted;
use App\Repositories\SportSessionComment\SportSessionCommentRepositoryInterface;
use Illuminate\Support\Facades\Validator;

class DeleteCommentUseCase
{
    public function __construct(
        private SportSessionCommentRepositoryInterface $commentRepository
    ) {}

    public function execute(string $commentId, string $userId): array
    {
        // Validation des données
        $validator = Validator::make([
            'commentId' => $commentId,
            'userId' => $userId,
        ], [
            'commentId' => 'required|uuid',
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

        // Vérifier que l'utilisateur peut supprimer le commentaire
        if (!$this->commentRepository->userCanDeleteComment($commentId, $userId)) {
            return [
                'success' => false,
                'error' => [
                    'code' => 'FORBIDDEN',
                    'message' => 'Vous n\'êtes pas autorisé à supprimer ce commentaire',
                ],
            ];
        }

        // Supprimer le commentaire
        $deleted = $this->commentRepository->deleteComment($commentId);

        if (!$deleted) {
            return [
                'success' => false,
                'error' => [
                    'code' => 'DELETE_FAILED',
                    'message' => 'Échec de la suppression du commentaire',
                ],
            ];
        }

        // Émettre l'événement WebSocket directement via SocketIOService
        try {
            $socketService = app(\App\Services\SocketIOService::class);
            $socketService->emitLaravelEvent(
                'comment.deleted',
                'sport-session.' . $existingComment->sessionId,
                [
                    'commentId' => $commentId,
                    'deletedAt' => now()->toISOString(),
                ]
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Failed to emit WebSocket event", [
                'sessionId' => $existingComment->sessionId,
                'error' => $e->getMessage()
            ]);
        }

        return [
            'success' => true,
            'message' => 'Commentaire supprimé avec succès',
        ];
    }
}
