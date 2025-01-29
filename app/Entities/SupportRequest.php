<?php

namespace App\Entities;

class SupportRequest
{
    private ?string $id;
    private ?string $userId;
    private string $message;

    public function __construct(
        ?string $id,
        ?string $userId,
        string $message,
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->message = $message;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): void
    {
        $this->id = $id;
    }

    public function getUserId(): ?string
    {
        return $this->userId;
    }

    public function setUserId(?string $userId): void
    {
        $this->userId = $userId;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'message' => $this->message,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            userId: $data['user_id'] ?? null,
            message: $data['message'],
        );
    }
} 