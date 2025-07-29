<?php

namespace App\Entities;

class FriendRequest
{
    private string $id;
    private string $firstname;
    private string $lastname;
    private ?string $avatar;
    private int $mutualFriends;

    public function __construct(
        string $id,
        string $firstname,
        string $lastname,
        ?string $avatar = null,
        int $mutualFriends = 0
    ) {
        $this->id = $id;
        $this->firstname = $firstname;
        $this->lastname = $lastname;
        $this->avatar = $avatar;
        $this->mutualFriends = $mutualFriends;
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

    public function getMutualFriends(): int
    {
        return $this->mutualFriends;
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
            'avatar' => $this->avatar,
            'mutualFriends' => $this->mutualFriends,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            firstname: $data['firstname'],
            lastname: $data['lastname'],
            avatar: $data['avatar'] ?? null,
            mutualFriends: $data['mutualFriends'] ?? 0
        );
    }
}
