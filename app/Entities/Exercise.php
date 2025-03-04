<?php

namespace App\Entities;

class Exercise
{
    private ?string $id;
    private int $level;
    private ?string $levelId;
    private ?string $bannerUrl;
    private ?string $videoUrl;
    private string $title;
    private ?string $description;
    private int $duration;
    private int $xpValue;

    public function __construct(
        ?string $id,
        int $level,
        ?string $levelId,
        ?string $bannerUrl,
        ?string $videoUrl,
        string $title,
        ?string $description,
        int $duration,
        int $xpValue,
    ) {
        $this->id = $id;
        $this->level = $level;
        $this->levelId = $levelId;
        $this->bannerUrl = $bannerUrl;
        $this->videoUrl = $videoUrl;
        $this->title = $title;
        $this->description = $description;
        $this->duration = $duration;
        $this->xpValue = $xpValue;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): void
    {
        $this->id = $id;
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function setLevel(int $level): void
    {
        $this->level = $level;
    }

    public function getLevelId(): ?string
    {
        return $this->levelId;
    }

    public function setLevelId(?string $levelId): void
    {
        $this->levelId = $levelId;
    }

    public function getBannerUrl(): ?string
    {
        return $this->bannerUrl;
    }

    public function setBannerUrl(?string $bannerUrl): void
    {
        $this->bannerUrl = $bannerUrl;
    }

    public function getVideoUrl(): ?string
    {
        return $this->videoUrl;
    }

    public function setVideoUrl(?string $videoUrl): void
    {
        $this->videoUrl = $videoUrl;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getDuration(): int
    {
        return $this->duration;
    }

    public function setDuration(int $duration): void
    {
        $this->duration = $duration;
    }

    public function getXpValue(): int
    {
        return $this->xpValue;
    }

    public function setXpValue(int $xpValue): void
    {
        $this->xpValue = $xpValue;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'level' => $this->level,
            'level_id' => $this->levelId,
            'banner_url' => $this->bannerUrl,
            'video_url' => $this->videoUrl,
            'title' => $this->title,
            'description' => $this->description,
            'duration' => $this->duration,
            'xp_value' => $this->xpValue,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            level: $data['level'],
            levelId: $data['level_id'] ?? null,
            bannerUrl: $data['banner_url'] ?? null,
            videoUrl: $data['video_url'] ?? null,
            title: $data['title'],
            description: $data['description'] ?? null,
            duration: $data['duration'],
            xpValue: $data['xp_value'],
        );
    }
}
