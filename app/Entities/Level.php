<?php

namespace App\Entities;

class Level
{
    private ?string $id;
    private string $name;
    private string $category;
    private int $levelNumber;
    private ?string $description;
    private ?string $bannerUrl;

    public function __construct(
        ?string $id,
        string $name,
        string $category,
        int $levelNumber,
        ?string $description,
        ?string $bannerUrl,
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->category = $category;
        $this->levelNumber = $levelNumber;
        $this->description = $description;
        $this->bannerUrl = $bannerUrl;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): void
    {
        $this->id = $id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function setCategory(string $category): void
    {
        $this->category = $category;
    }

    public function getLevelNumber(): int
    {
        return $this->levelNumber;
    }

    public function setLevelNumber(int $levelNumber): void
    {
        $this->levelNumber = $levelNumber;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getBannerUrl(): ?string
    {
        return $this->bannerUrl;
    }

    public function setBannerUrl(?string $bannerUrl): void
    {
        $this->bannerUrl = $bannerUrl;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'category' => $this->category,
            'level_number' => $this->levelNumber,
            'description' => $this->description,
            'banner_url' => $this->bannerUrl,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            name: $data['name'],
            category: $data['category'],
            levelNumber: $data['level_number'],
            description: $data['description'] ?? null,
            bannerUrl: $data['banner_url'] ?? null,
        );
    }
}
