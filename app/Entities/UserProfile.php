<?php

namespace App\Entities;

class UserProfile
{
    private ?string $id;
    private string $userId;
    private ?string $avatarFileId;
    private int $totalXp;
    private int $totalTrainingTime;
    private int $completedVideos;
    private int $completedDays;
    private ?string $currentGoals;

    public function __construct(
        ?string $id,
        string $userId,
        ?string $avatarFileId,
        int $totalXp,
        int $totalTrainingTime,
        int $completedVideos,
        int $completedDays,
        ?string $currentGoals,
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->avatarFileId = $avatarFileId;
        $this->totalXp = $totalXp;
        $this->totalTrainingTime = $totalTrainingTime;
        $this->completedVideos = $completedVideos;
        $this->completedDays = $completedDays;
        $this->currentGoals = $currentGoals;
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

    public function getAvatarFileId(): ?string
    {
        return $this->avatarFileId;
    }

    public function setAvatarFileId(?string $avatarFileId): void
    {
        $this->avatarFileId = $avatarFileId;
    }

    public function getTotalXp(): int
    {
        return $this->totalXp;
    }

    public function setTotalXp(int $totalXp): void
    {
        $this->totalXp = $totalXp;
    }

    public function getTotalTrainingTime(): int
    {
        return $this->totalTrainingTime;
    }

    public function setTotalTrainingTime(int $totalTrainingTime): void
    {
        $this->totalTrainingTime = $totalTrainingTime;
    }

    public function getCompletedVideos(): int
    {
        return $this->completedVideos;
    }

    public function setCompletedVideos(int $completedVideos): void
    {
        $this->completedVideos = $completedVideos;
    }

    public function getCompletedDays(): int
    {
        return $this->completedDays;
    }

    public function setCompletedDays(int $completedDays): void
    {
        $this->completedDays = $completedDays;
    }

    public function getCurrentGoals(): ?string
    {
        return $this->currentGoals;
    }

    public function setCurrentGoals(?string $currentGoals): void
    {
        $this->currentGoals = $currentGoals;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'avatar_file_id' => $this->avatarFileId,
            'total_xp' => $this->totalXp,
            'total_training_time' => $this->totalTrainingTime,
            'completed_videos' => $this->completedVideos,
            'completed_days' => $this->completedDays,
            'current_goals' => $this->currentGoals,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            userId: $data['user_id'],
            avatarFileId: $data['avatar_file_id'] ?? null,
            totalXp: $data['total_xp'],
            totalTrainingTime: $data['total_training_time'],
            completedVideos: $data['completed_videos'],
            completedDays: $data['completed_days'],
            currentGoals: $data['current_goals'] ?? null,
        );
    }
} 