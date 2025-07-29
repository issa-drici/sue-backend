<?php

namespace App\Entities;

class Friend
{
    private string $id;
    private string $firstname;
    private string $lastname;
    private ?string $avatar;
    private string $status;
    private ?string $lastSeen;

    public function __construct(
        string $id,
        string $firstname,
        string $lastname,
        ?string $avatar = null,
        string $status = 'offline',
        ?string $lastSeen = null
    ) {
        $this->id = $id;
        $this->firstname = $firstname;
        $this->lastname = $lastname;
        $this->avatar = $avatar;
        $this->status = $status;
        $this->lastSeen = $lastSeen;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getFirstname(): string
    {
        return $this->firstname;
    }

    public function getLastname(): string
    {
        return $this->lastname;
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getLastSeen(): ?string
    {
        return $this->lastSeen;
    }

    public function getFullName(): string
    {
        return $this->firstname . ' ' . $this->lastname;
    }

    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'firstname' => $this->firstname,
            'lastname' => $this->lastname,
            'avatar' => $this->avatar,
            'status' => $this->status,
        ];

        if ($this->lastSeen) {
            $data['lastSeen'] = $this->lastSeen;
        }

        return $data;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            firstname: $data['firstname'],
            lastname: $data['lastname'],
            avatar: $data['avatar'] ?? null,
            status: $data['status'] ?? 'offline',
            lastSeen: $data['lastSeen'] ?? null
        );
    }
}
