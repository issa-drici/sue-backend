<?php

namespace App\Entities;

class UserProfile
{
    private string $id;
    private string $firstname;
    private string $lastname;
    private string $email;
    private ?string $avatar;
    private array $stats;

    public function __construct(
        string $id,
        string $firstname,
        string $lastname,
        string $email,
        ?string $avatar = null,
        array $stats = []
    ) {
        $this->id = $id;
        $this->firstname = $firstname;
        $this->lastname = $lastname;
        $this->email = $email;
        $this->avatar = $avatar;
        $this->stats = $stats;
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

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function getStats(): array
    {
        return $this->stats;
    }

    public function getFullName(): string
    {
        return $this->firstname . ' ' . $this->lastname;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'firstname' => $this->firstname,
            'lastname' => $this->lastname,
            'email' => $this->email,
            'avatar' => $this->avatar,
            'stats' => $this->stats,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            firstname: $data['firstname'],
            lastname: $data['lastname'],
            email: $data['email'],
            avatar: $data['avatar'] ?? null,
            stats: $data['stats'] ?? []
        );
    }
}
