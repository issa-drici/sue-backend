<?php

namespace App\Entities;

use DateTime;

class Notification
{
    private string $id;
    private string $userId;
    private string $type;
    private string $title;
    private string $message;
    private ?string $sessionId;
    private DateTime $createdAt;
    private bool $read;
    private bool $pushSent;
    private ?DateTime $pushSentAt;
    private ?array $pushData;

    public function __construct(
        string $id,
        string $userId,
        string $type,
        string $title,
        string $message,
        ?string $sessionId = null,
        ?DateTime $createdAt = null,
        bool $read = false,
        bool $pushSent = false,
        ?DateTime $pushSentAt = null,
        ?array $pushData = null
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->type = $type;
        $this->title = $title;
        $this->message = $message;
        $this->sessionId = $sessionId;
        $this->createdAt = $createdAt ?? new DateTime();
        $this->read = $read;
        $this->pushSent = $pushSent;
        $this->pushSentAt = $pushSentAt;
        $this->pushData = $pushData;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getSessionId(): ?string
    {
        return $this->sessionId;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function isRead(): bool
    {
        return $this->read;
    }

    public function markAsRead(): void
    {
        $this->read = true;
    }

    public function markAsUnread(): void
    {
        $this->read = false;
    }

    public function isPushSent(): bool
    {
        return $this->pushSent;
    }

    public function getPushSentAt(): ?DateTime
    {
        return $this->pushSentAt;
    }

    public function getPushData(): ?array
    {
        return $this->pushData;
    }

    public function markAsPushSent(): void
    {
        $this->pushSent = true;
        $this->pushSentAt = new DateTime();
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'type' => $this->type,
            'title' => $this->title,
            'message' => $this->message,
            'session_id' => $this->sessionId,
            'created_at' => $this->createdAt->format('c'),
            'read' => $this->read,
            'push_sent' => $this->pushSent,
            'push_sent_at' => $this->pushSentAt?->format('c'),
            'push_data' => $this->pushData
        ];
    }

    public static function getSupportedTypes(): array
    {
        return ['invitation', 'reminder', 'update'];
    }

    public static function isValidType(string $type): bool
    {
        return in_array($type, self::getSupportedTypes());
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            userId: $data['user_id'],
            type: $data['type'],
            title: $data['title'],
            message: $data['message'],
            sessionId: $data['session_id'] ?? null,
            createdAt: isset($data['created_at']) ? new DateTime($data['created_at']) : null,
            read: $data['read'] ?? false,
            pushSent: $data['push_sent'] ?? false,
            pushSentAt: isset($data['push_sent_at']) ? new DateTime($data['push_sent_at']) : null,
            pushData: $data['push_data'] ?? null
        );
    }
}
