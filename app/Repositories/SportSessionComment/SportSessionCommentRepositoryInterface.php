<?php

namespace App\Repositories\SportSessionComment;

use App\Entities\SportSessionComment;
use Illuminate\Pagination\LengthAwarePaginator;

interface SportSessionCommentRepositoryInterface
{
    public function createComment(string $sessionId, string $userId, string $content, ?array $mentions = null): SportSessionComment;

    public function updateComment(string $commentId, string $content, ?array $mentions = null): ?SportSessionComment;

    public function deleteComment(string $commentId): bool;

    public function findCommentById(string $commentId): ?SportSessionComment;

    public function findCommentsBySession(string $sessionId, int $page = 1, int $limit = 20): LengthAwarePaginator;

    public function findAllCommentsBySession(string $sessionId): array;

    public function findCommentsByUser(string $userId, int $page = 1, int $limit = 20): LengthAwarePaginator;

    public function userCanEditComment(string $commentId, string $userId): bool;

    public function userCanDeleteComment(string $commentId, string $userId): bool;
}
