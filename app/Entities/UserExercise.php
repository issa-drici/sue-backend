<?php

namespace App\Entities;

use DateTime;

class UserExercise
{
    private ?string $id;
    private string $userId;
    private string $exerciseId;
    private ?DateTime $completedAt;
    private int $watchTime;
    private DateTime $createdAt;

    public function __construct(
        ?string $id,
        string $userId,
        string $exerciseId,
        ?DateTime $completedAt,
        int $watchTime,
        DateTime $createdAt
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->exerciseId = $exerciseId;
        $this->completedAt = $completedAt;
        $this->watchTime = $watchTime;
        $this->createdAt = $createdAt;
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

    public function getCompletedAt(): ?DateTime
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?DateTime $completedAt): void
    {
        $this->completedAt = $completedAt;
    }

    public function getWatchTime(): int
    {
        return $this->watchTime;
    }

    public function setWatchTime(int $watchTime): void
    {
        $this->watchTime = $watchTime;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'exercise_id' => $this->exerciseId,
            'completed_at' => $this->completedAt?->format('Y-m-d H:i:s'),
            'watch_time' => $this->watchTime,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            userId: $data['user_id'],
            exerciseId: $data['exercise_id'],
            completedAt: isset($data['completed_at']) ? new DateTime($data['completed_at']) : null,
            watchTime: $data['watch_time'],
            createdAt: new DateTime($data['created_at']),
        );
    }
} 