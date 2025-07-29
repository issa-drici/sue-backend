<?php

namespace App\UseCases\SportSessionComment;

use App\Repositories\SportSession\SportSessionRepositoryInterface;
use App\Repositories\SportSessionComment\SportSessionCommentRepositoryInterface;

class GetCommentsUseCase
{
    public function __construct(
        private SportSessionCommentRepositoryInterface $commentRepository,
        private SportSessionRepositoryInterface $sessionRepository
    ) {}

    public function execute(string $sessionId): array
    {
        // Vérifier que la session existe
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

        // Récupérer tous les commentaires
        $comments = $this->commentRepository->findAllCommentsBySession($sessionId);

        // Convertir les entités en tableaux
        $commentsArray = array_map(fn($comment) => $comment->toArray(), $comments);

        return [
            'success' => true,
            'data' => $commentsArray,
            'pagination' => null,
        ];
    }
}
