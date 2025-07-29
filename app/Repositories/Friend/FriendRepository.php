<?php

namespace App\Repositories\Friend;

use App\Entities\Friend;
use App\Models\FriendModel;
use App\Models\UserModel;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class FriendRepository implements FriendRepositoryInterface
{
    public function findById(string $id): ?Friend
    {
        $model = FriendModel::find($id);

        if (!$model) {
            return null;
        }

        return $this->mapToEntity($model);
    }

    public function getUserFriends(string $userId, int $page = 1, int $limit = 20): LengthAwarePaginator
    {
        $paginator = FriendModel::with('friend')
            ->byUser($userId)
            ->orderBy('created_at', 'desc')
            ->paginate($limit, ['*'], 'page', $page);

        $paginator->getCollection()->transform(function ($model) {
            return [
                'id' => $model->friend->id,
                'firstname' => $model->friend->firstname,
                'lastname' => $model->friend->lastname,
                'email' => $model->friend->email,
                'avatar' => null, // avatar null pour l'instant
                'status' => 'offline', // status par défaut
                'lastSeen' => null, // lastSeen null pour l'instant
                'friendship_id' => $model->id,
                'created_at' => $model->created_at->toISOString(),
                'updated_at' => $model->updated_at->toISOString()
            ];
        });

        return $paginator;
    }

    public function areFriends(string $userId1, string $userId2): bool
    {
        return FriendModel::where(function ($query) use ($userId1, $userId2) {
            $query->where('user_id', $userId1)
                  ->where('friend_id', $userId2);
        })->orWhere(function ($query) use ($userId1, $userId2) {
            $query->where('user_id', $userId2)
                  ->where('friend_id', $userId1);
        })->exists();
    }

    public function addFriend(string $userId1, string $userId2): bool
    {
        // Créer les deux relations d'amitié (bidirectionnelle)
        $friend1 = FriendModel::create([
            'id' => Str::uuid(),
            'user_id' => $userId1,
            'friend_id' => $userId2,
        ]);

        $friend2 = FriendModel::create([
            'id' => Str::uuid(),
            'user_id' => $userId2,
            'friend_id' => $userId1,
        ]);

        return $friend1 && $friend2;
    }

    public function removeFriend(string $userId1, string $userId2): bool
    {
        $deleted1 = FriendModel::where('user_id', $userId1)
            ->where('friend_id', $userId2)
            ->delete();

        $deleted2 = FriendModel::where('user_id', $userId2)
            ->where('friend_id', $userId1)
            ->delete();

        return $deleted1 > 0 && $deleted2 > 0;
    }

    public function getMutualFriendsCount(string $userId1, string $userId2): int
    {
        $friends1 = $this->getFriendsIds($userId1);
        $friends2 = $this->getFriendsIds($userId2);

        return count(array_intersect($friends1, $friends2));
    }

    public function getFriendsIds(string $userId): array
    {
        return FriendModel::byUser($userId)
            ->pluck('friend_id')
            ->toArray();
    }

    private function mapToEntity(FriendModel $model): Friend
    {
        $friend = $model->friend;

        return new Friend(
            $friend->id,
            $friend->firstname ?? '', // Utiliser firstname avec fallback
            $friend->lastname ?? '', // Utiliser lastname avec fallback
            null, // avatar
            'offline', // status par défaut
            null // lastSeen
        );
    }
}
