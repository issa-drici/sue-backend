<?php

namespace App\Entities;

use DateTime;

class SportSession
{
    private string $id;
    private string $sport;
    private DateTime $startDate;
    private DateTime $endDate;
    private string $location;
    private ?int $maxParticipants;
    private ?float $pricePerPerson;
    private string $status;
    private User $organizer;
    private array $participants;
    private array $comments;

    public function __construct(
        string $id,
        string $sport,
        DateTime|string $startDate,
        DateTime|string $endDate,
        string $location,
        ?int $maxParticipants,
        ?float $pricePerPerson,
        string $status,
        User $organizer,
        array $participants = [],
        array $comments = []
    ) {
        $this->id = $id;
        $this->sport = $sport;
        $this->startDate = $startDate instanceof DateTime ? $startDate : new DateTime($startDate);
        $this->endDate = $endDate instanceof DateTime ? $endDate : new DateTime($endDate);
        $this->location = $location;
        $this->maxParticipants = $maxParticipants;
        $this->pricePerPerson = $pricePerPerson;
        $this->status = $status;
        $this->organizer = $organizer;
        $this->participants = $participants;
        $this->comments = $comments;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getSport(): string
    {
        return $this->sport;
    }

    public function getDate(): string
    {
        return $this->startDate->format('Y-m-d');
    }

    public function getStartTime(): string
    {
        return $this->startDate->format('H:i');
    }

    public function getEndTime(): string
    {
        return $this->endDate->format('H:i');
    }

    public function getStartDate(): DateTime
    {
        return $this->startDate;
    }

    public function getEndDate(): DateTime
    {
        return $this->endDate;
    }

    public function getLocation(): string
    {
        return $this->location;
    }

    public function getMaxParticipants(): ?int
    {
        return $this->maxParticipants;
    }

    public function getPricePerPerson(): ?float
    {
        return $this->pricePerPerson;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getOrganizer(): User
    {
        return $this->organizer;
    }

    public function getParticipants(): array
    {
        return $this->participants;
    }

    public function getComments(): array
    {
        return $this->comments;
    }

    public function addParticipant(User $user, string $status = 'pending'): void
    {
        $this->participants[] = [
            'id' => $user->getId(),
            'fullName' => $user->getFirstname() . ' ' . $user->getLastname(),
            'status' => $status
        ];
    }

    public function addComment(User $user, string $content): void
    {
        $this->comments[] = [
            'id' => uniqid(),
            'userId' => $user->getId(),
            'fullName' => $user->getFirstname() . ' ' . $user->getLastname(),
            'content' => $content,
            'createdAt' => (new DateTime())->format('c')
        ];
    }

    public function isParticipant(string $userId): bool
    {
        foreach ($this->participants as $participant) {
            if ($participant['id'] === $userId) {
                return true;
            }
        }
        return false;
    }

    public function isOrganizer(string $userId): bool
    {
        return $this->organizer->getId() === $userId;
    }

    public function canUserAccess(string $userId): bool
    {
        return $this->isOrganizer($userId) || $this->isParticipant($userId);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'sport' => $this->sport,
            'date' => $this->getDate(),
            'startTime' => $this->getStartTime(),
            'endTime' => $this->getEndTime(),
            'startDate' => $this->startDate->format('c'),
            'endDate' => $this->endDate->format('c'),
            'location' => $this->location,
            'maxParticipants' => $this->maxParticipants,
            'pricePerPerson' => $this->pricePerPerson,
            'status' => $this->status,
            'organizer' => [
                'id' => $this->organizer->getId(),
                'fullName' => $this->organizer->getFirstname() . ' ' . $this->organizer->getLastname()
            ],
            'participants' => $this->participants,
            'comments' => $this->comments
        ];
    }

    public static function getSupportedSports(): array
    {
        return \App\Services\SportService::getSupportedSports();
    }

    public static function isValidSport(string $sport): bool
    {
        return \App\Services\SportService::isValidSport($sport);
    }
}
