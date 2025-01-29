<?php

namespace App\Entities;

class User
{
    private ?string $id;
    private string $fullName;
    private string $email;
    private ?string $phone;
    private string $role;

    public function __construct(
        ?string $id,
        string $fullName,
        string $email,
        ?string $phone,
        string $role = 'player',
    ) {
        $this->id = $id;
        $this->fullName = $fullName;
        $this->email = $email;
        $this->phone = $phone;
        $this->role = $role;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): void
    {
        $this->id = $id;
    }

    public function getFullName(): string
    {
        return $this->fullName;
    }

    public function setFullName(string $fullName): void
    {
        $this->fullName = $fullName;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): void
    {
        $this->phone = $phone;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function setRole(string $role): void
    {
        $this->role = $role;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'full_name' => $this->fullName,
            'email' => $this->email,
            'phone' => $this->phone,
            'role' => $this->role,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            fullName: $data['full_name'],
            email: $data['email'],
            phone: $data['phone'] ?? null,
            role: $data['role'] ?? 'player',
        );
    }
} 