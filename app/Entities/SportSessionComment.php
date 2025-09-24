<?php

namespace App\Entities;

class SportSessionComment
{
    public function __construct(
        public readonly string $id,
        public readonly string $sessionId,
        public readonly string $userId,
        public readonly string $content,
        public readonly ?array $mentions,
        public readonly \DateTime $createdAt,
        public readonly \DateTime $updatedAt,
        public readonly ?User $user = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            sessionId: $data['session_id'],
            userId: $data['user_id'],
            content: $data['content'],
            mentions: $data['mentions'] ?? null,
            createdAt: new \DateTime($data['created_at']),
            updatedAt: new \DateTime($data['updated_at']),
            user: isset($data['user']) ? User::fromArray($data['user']) : null
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'session_id' => $this->sessionId,
            'user_id' => $this->userId,
            'content' => $this->content,
            'mentions' => $this->mentions,
            'created_at' => $this->createdAt->format('c'),
            'updated_at' => $this->updatedAt->format('c'),
            'user' => $this->user?->toArray(),
        ];
    }
}
