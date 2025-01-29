<?php

namespace App\Entities;

class File
{
    private ?string $id;
    private ?string $userId;
    private string $path;
    private string $url;
    private string $mimeType;

    public function __construct(
        ?string $id,
        ?string $userId,
        string $path,
        string $url,
        string $mimeType,
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->path = $path;
        $this->url = $url;
        $this->mimeType = $mimeType;
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

    public function getPath(): string
    {
        return $this->path;
    }

    public function setPath(string $path): void
    {
        $this->path = $path;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    public function setMimeType(string $mimeType): void
    {
        $this->mimeType = $mimeType;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'path' => $this->path,
            'url' => $this->url,
            'mime_type' => $this->mimeType,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            userId: $data['user_id'] ?? null,
            path: $data['path'],
            url: $data['url'],
            mimeType: $data['mime_type'],
        );
    }
} 