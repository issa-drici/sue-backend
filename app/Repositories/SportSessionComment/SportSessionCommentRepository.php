<?php

namespace App\Repositories\SportSessionComment;

use App\Entities\SportSessionComment;
use App\Entities\User;
use App\Models\SportSessionCommentModel;
use App\Models\UserModel;
use Illuminate\Pagination\LengthAwarePaginator;

class SportSessionCommentRepository implements SportSessionCommentRepositoryInterface
{
    public function createComment(string $sessionId, string $userId, string $content, ?array $mentions = null): SportSessionComment
    {
        $comment = SportSessionCommentModel::create([
            'session_id' => $sessionId,
            'user_id' => $userId,
            'content' => $content,
            'mentions' => $mentions,
        ]);

        return $this->loadCommentWithUser($comment);
    }

    public function updateComment(string $commentId, string $content, ?array $mentions = null): ?SportSessionComment
    {
        $comment = SportSessionCommentModel::find($commentId);

        if (!$comment) {
            return null;
        }

        $comment->update([
            'content' => $content,
            'mentions' => $mentions,
        ]);

        return $this->loadCommentWithUser($comment);
    }

    public function deleteComment(string $commentId): bool
    {
        $comment = SportSessionCommentModel::find($commentId);

        if (!$comment) {
            return false;
        }

        return $comment->delete();
    }

    public function findCommentById(string $commentId): ?SportSessionComment
    {
        $comment = SportSessionCommentModel::with('user')->find($commentId);

        if (!$comment) {
            return null;
        }

        return $this->loadCommentWithUser($comment);
    }

    public function findCommentsBySession(string $sessionId, int $page = 1, int $limit = 20): LengthAwarePaginator
    {
        $comments = SportSessionCommentModel::with('user')
            ->bySession($sessionId)
            ->ordered()
            ->paginate($limit, ['*'], 'page', $page);

        return $comments->through(function ($comment) {
            return $this->loadCommentWithUser($comment);
        });
    }

    public function findAllCommentsBySession(string $sessionId): array
    {
        $comments = SportSessionCommentModel::with('user')
            ->bySession($sessionId)
            ->ordered()
            ->get();

        return $comments->map(function ($comment) {
            return $this->loadCommentWithUser($comment);
        })->toArray();
    }

    public function findCommentsByUser(string $userId, int $page = 1, int $limit = 20): LengthAwarePaginator
    {
        $comments = SportSessionCommentModel::with('user')
            ->byUser($userId)
            ->recent()
            ->paginate($limit, ['*'], 'page', $page);

        return $comments->through(function ($comment) {
            return $this->loadCommentWithUser($comment);
        });
    }

    public function userCanEditComment(string $commentId, string $userId): bool
    {
        $comment = SportSessionCommentModel::find($commentId);

        if (!$comment) {
            return false;
        }

        return $comment->user_id === $userId;
    }

    public function userCanDeleteComment(string $commentId, string $userId): bool
    {
        $comment = SportSessionCommentModel::find($commentId);

        if (!$comment) {
            return false;
        }

        // L'utilisateur peut supprimer son propre commentaire
        if ($comment->user_id === $userId) {
            return true;
        }

        // TODO: Ajouter la logique pour les modérateurs/admin si nécessaire
        return false;
    }

    private function loadCommentWithUser(SportSessionCommentModel $comment): SportSessionComment
    {
        // S'assurer que la relation user est chargée
        if (!$comment->relationLoaded('user')) {
            $comment->load('user');
        }

        $userArray = [];
        if ($comment->user) {
            $user = User::fromArray([
                'id' => $comment->user->id,
                'firstname' => $comment->user->firstname,
                'lastname' => $comment->user->lastname,
                'email' => $comment->user->email,
                'phone' => $comment->user->phone,
                'role' => $comment->user->role,
            ]);

            $userArray = $user->toArray();
        }

        return SportSessionComment::fromArray([
            'id' => $comment->id,
            'session_id' => $comment->session_id,
            'user_id' => $comment->user_id,
            'content' => $comment->content,
            'mentions' => $comment->mentions,
            'created_at' => $comment->created_at->toDateTimeString(),
            'updated_at' => $comment->updated_at->toDateTimeString(),
            'user' => $userArray,
        ]);
    }
}
