<?php

namespace App\Entities;

class User
{
    private ?string $id;
    private string $firstname;
    private string $lastname;
    private string $email;
    private ?string $phone;
    private string $role;
    private ?array $sportsPreferences;

    public function __construct(
        ?string $id,
        string $firstname,
        string $lastname,
        string $email,
        ?string $phone,
        string $role = 'player',
        ?array $sportsPreferences = null,
    ) {
        $this->id = $id;
        $this->firstname = $firstname;
        $this->lastname = $lastname;
        $this->email = $email;
        $this->phone = $phone;
        $this->role = $role;
        $this->sportsPreferences = $sportsPreferences;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): void
    {
        $this->id = $id;
    }

    public function getFirstname(): string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): void
    {
        $this->firstname = $firstname;
    }

    public function getLastname(): string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): void
    {
        $this->lastname = $lastname;
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

    public function getSportsPreferences(): ?array
    {
        return $this->sportsPreferences;
    }

    public function setSportsPreferences(?array $sportsPreferences): void
    {
        $this->sportsPreferences = $sportsPreferences;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'firstname' => $this->firstname,
            'lastname' => $this->lastname,
            'email' => $this->email,
            'phone' => $this->phone,
            'role' => $this->role,
            'sports_preferences' => $this->sportsPreferences,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            firstname: $data['firstname'],
            lastname: $data['lastname'],
            email: $data['email'],
            phone: $data['phone'] ?? null,
            role: $data['role'] ?? 'player',
            sportsPreferences: $data['sports_preferences'] ?? null,
        );
    }
}
