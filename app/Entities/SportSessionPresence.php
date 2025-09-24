<?php

namespace App\Entities;

class SportSessionPresence
{
    public function __construct(
        public readonly string $id,
        public readonly string $sportSessionId,
        public readonly string $userId,
        public readonly bool $isOnline,
        public readonly bool $isTyping,
        public readonly ?\DateTime $lastSeenAt,
        public readonly ?\DateTime $typingStartedAt,
        public readonly \DateTime $createdAt,
        public readonly \DateTime $updatedAt,
        public readonly ?User $user = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            sportSessionId: $data['sport_session_id'],
            userId: $data['user_id'],
            isOnline: $data['is_online'],
            isTyping: $data['is_typing'],
            lastSeenAt: isset($data['last_seen_at']) ? new \DateTime($data['last_seen_at']) : null,
            typingStartedAt: isset($data['typing_started_at']) ? new \DateTime($data['typing_started_at']) : null,
            createdAt: new \DateTime($data['created_at']),
            updatedAt: new \DateTime($data['updated_at']),
            user: isset($data['user']) ? User::fromArray($data['user']) : null
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'sport_session_id' => $this->sportSessionId,
            'user_id' => $this->userId,
            'is_online' => $this->isOnline,
            'is_typing' => $this->isTyping,
            'last_seen_at' => $this->lastSeenAt?->format('c'),
            'typing_started_at' => $this->typingStartedAt?->format('c'),
            'created_at' => $this->createdAt->format('c'),
            'updated_at' => $this->updatedAt->format('c'),
            'user' => $this->user?->toArray(),
        ];
    }
}
