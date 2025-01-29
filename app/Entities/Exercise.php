<?php

namespace App\Entities;

class Exercise
{
    private ?string $id;
    private int $level;
    private ?string $bannerUrl;
    private string $title;
    private ?string $description;
    private int $duration;
    private int $xpValue;

    public function __construct(
        ?string $id,
        int $level,
        ?string $bannerUrl,
        string $title,
        ?string $description,
        int $duration,
        int $xpValue,
    ) {
        $this->id = $id;
        $this->level = $level;
        $this->bannerUrl = $bannerUrl;
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

    public function getBannerUrl(): ?string
    {
        return $this->bannerUrl;
    }

    public function setBannerUrl(?string $bannerUrl): void
    {
        $this->bannerUrl = $bannerUrl;
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
            'banner_url' => $this->bannerUrl,
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
            bannerUrl: $data['banner_url'] ?? null,
            title: $data['title'],
            description: $data['description'] ?? null,
            duration: $data['duration'],
            xpValue: $data['xp_value'],
        );
    }
} 