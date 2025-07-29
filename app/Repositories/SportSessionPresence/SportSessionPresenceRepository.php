<?php

namespace App\Repositories\SportSessionPresence;

use App\Entities\SportSessionPresence;
use App\Entities\User;
use App\Models\SportSessionPresenceModel;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class SportSessionPresenceRepository implements SportSessionPresenceRepositoryInterface
{
    public function joinSession(string $sessionId, string $userId): SportSessionPresence
    {
        $presence = SportSessionPresenceModel::updateOrCreate(
            [
                'sport_session_id' => $sessionId,
                'user_id' => $userId,
            ],
            [
                'id' => Str::uuid(),
                'is_online' => true,
                'is_typing' => false,
                'last_seen_at' => now(),
                'typing_started_at' => null,
            ]
        );

        return $this->loadPresenceWithUser($presence);
    }

    public function leaveSession(string $sessionId, string $userId): bool
    {
        $presence = SportSessionPresenceModel::where('sport_session_id', $sessionId)
            ->where('user_id', $userId)
            ->first();

        if (!$presence) {
            return false;
        }

        return $presence->update([
            'is_online' => false,
            'is_typing' => false,
            'last_seen_at' => now(),
            'typing_started_at' => null,
        ]);
    }

    public function updateTypingStatus(string $sessionId, string $userId, bool $isTyping): ?SportSessionPresence
    {
        $presence = SportSessionPresenceModel::where('sport_session_id', $sessionId)
            ->where('user_id', $userId)
            ->first();

        if (!$presence) {
            return null;
        }

        $presence->update([
            'is_typing' => $isTyping,
            'typing_started_at' => $isTyping ? now() : null,
            'last_seen_at' => now(),
        ]);

        return $this->loadPresenceWithUser($presence);
    }

    public function updateLastSeen(string $sessionId, string $userId): bool
    {
        $presence = SportSessionPresenceModel::where('sport_session_id', $sessionId)
            ->where('user_id', $userId)
            ->first();

        if (!$presence) {
            return false;
        }

        return $presence->update([
            'last_seen_at' => now(),
        ]);
    }

    public function findPresenceBySessionAndUser(string $sessionId, string $userId): ?SportSessionPresence
    {
        $presence = SportSessionPresenceModel::with('user')
            ->where('sport_session_id', $sessionId)
            ->where('user_id', $userId)
            ->first();

        if (!$presence) {
            return null;
        }

        return $this->loadPresenceWithUser($presence);
    }

    public function findOnlineUsersBySession(string $sessionId, int $page = 1, int $limit = 50): LengthAwarePaginator
    {
        $presences = SportSessionPresenceModel::with('user')
            ->bySession($sessionId)
            ->online()
            ->active()
            ->orderBy('last_seen_at', 'desc')
            ->paginate($limit, ['*'], 'page', $page);

        return $presences->through(function ($presence) {
            return $this->loadPresenceWithUser($presence);
        });
    }

    public function findTypingUsersBySession(string $sessionId): array
    {
        $presences = SportSessionPresenceModel::with('user')
            ->bySession($sessionId)
            ->typing()
            ->active()
            ->get();

        return $presences->map(function ($presence) {
            return $this->loadPresenceWithUser($presence);
        })->toArray();
    }

    public function cleanupInactiveUsers(): int
    {
        $inactiveThreshold = now()->subMinutes(10);

        return SportSessionPresenceModel::where('last_seen_at', '<', $inactiveThreshold)
            ->update([
                'is_online' => false,
                'is_typing' => false,
            ]);
    }

    private function loadPresenceWithUser(SportSessionPresenceModel $presence): SportSessionPresence
    {
        $user = null;
        if ($presence->user) {
            $user = User::fromArray([
                'id' => $presence->user->id,
                'firstname' => $presence->user->firstname,
                'lastname' => $presence->user->lastname,
                'email' => $presence->user->email,
                'avatar' => $presence->user->avatar,
            ]);
        }

        return SportSessionPresence::fromArray([
            'id' => $presence->id,
            'sport_session_id' => $presence->sport_session_id,
            'user_id' => $presence->user_id,
            'is_online' => $presence->is_online,
            'is_typing' => $presence->is_typing,
            'last_seen_at' => $presence->last_seen_at?->toDateTimeString(),
            'typing_started_at' => $presence->typing_started_at?->toDateTimeString(),
            'created_at' => $presence->created_at->toDateTimeString(),
            'updated_at' => $presence->updated_at->toDateTimeString(),
            'user' => $user?->toArray(),
        ]);
    }
}
