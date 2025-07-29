<?php

namespace App\Entities;

class PushToken
{
    public function __construct(
        public readonly string $id,
        public readonly string $userId,
        public readonly string $token,
        public readonly string $platform,
        public readonly bool $isActive,
        public readonly \DateTime $createdAt,
        public readonly \DateTime $updatedAt,
        public readonly ?User $user = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            userId: $data['user_id'],
            token: $data['token'],
            platform: $data['platform'],
            isActive: $data['is_active'],
            createdAt: new \DateTime($data['created_at']),
            updatedAt: new \DateTime($data['updated_at']),
            user: isset($data['user']) ? User::fromArray($data['user']) : null
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'token' => $this->token,
            'platform' => $this->platform,
            'is_active' => $this->isActive,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt->format('Y-m-d H:i:s'),
            'user' => $this->user?->toArray(),
        ];
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getPlatform(): string
    {
        return $this->platform;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }
}
