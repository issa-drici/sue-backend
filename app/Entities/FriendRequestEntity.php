<?php

namespace App\Entities;

class FriendRequestEntity
{
    private string $id;
    private string $senderId;
    private string $receiverId;
    private string $status;
    private ?string $cancelledAt;

    public function __construct(
        string $id,
        string $senderId,
        string $receiverId,
        string $status = 'pending',
        ?string $cancelledAt = null
    ) {
        $this->id = $id;
        $this->senderId = $senderId;
        $this->receiverId = $receiverId;
        $this->status = $status;
        $this->cancelledAt = $cancelledAt;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getSenderId(): string
    {
        return $this->senderId;
    }

    public function getReceiverId(): string
    {
        return $this->receiverId;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getCancelledAt(): ?string
    {
        return $this->cancelledAt;
    }

    public function isPending(): bool
    {
        return $this->status === 'pending' && $this->cancelledAt === null;
    }

    public function isCancelled(): bool
    {
        return $this->cancelledAt !== null;
    }

    public function isAccepted(): bool
    {
        return $this->status === 'accepted';
    }

    public function isDeclined(): bool
    {
        return $this->status === 'declined';
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'senderId' => $this->senderId,
            'receiverId' => $this->receiverId,
            'status' => $this->status,
            'cancelledAt' => $this->cancelledAt,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            senderId: $data['sender_id'],
            receiverId: $data['receiver_id'],
            status: $data['status'] ?? 'pending',
            cancelledAt: $data['cancelled_at'] ?? null
        );
    }
}
