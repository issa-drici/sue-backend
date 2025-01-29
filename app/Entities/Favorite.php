<?php

namespace App\Entities;

class Favorite
{
    private ?string $id;
    private string $userId;
    private string $exerciseId;

    public function __construct(
        ?string $id,
        string $userId,
        string $exerciseId,
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->exerciseId = $exerciseId;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): void
    {
        $this->id = $id;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function setUserId(string $userId): void
    {
        $this->userId = $userId;
    }

    public function getExerciseId(): string
    {
        return $this->exerciseId;
    }

    public function setExerciseId(string $exerciseId): void
    {
        $this->exerciseId = $exerciseId;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'exercise_id' => $this->exerciseId,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            userId: $data['user_id'],
            exerciseId: $data['exercise_id'],
        );
    }
} 