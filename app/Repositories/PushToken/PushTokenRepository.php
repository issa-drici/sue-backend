<?php

namespace App\Repositories\PushToken;

use App\Entities\PushToken;
use App\Entities\User;
use App\Models\PushTokenModel;
use App\Models\UserModel;
use Illuminate\Support\Str;

class PushTokenRepository implements PushTokenRepositoryInterface
{
    public function saveToken(string $userId, string $token, string $platform = 'expo', ?string $deviceId = null): bool
    {
        try {
            $pushToken = PushTokenModel::updateOrCreate(
                ['token' => $token],
                [
                    'id' => Str::uuid(),
                    'user_id' => $userId,
                    'platform' => $platform,
                    'device_id' => $deviceId,
                    'last_seen_at' => now(),
                    'is_active' => true
                ]
            );

            return $pushToken->exists;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error saving push token', [
                'user_id' => $userId,
                'token' => $token,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function getTokensForUser(string $userId): array
    {
        try {
            return PushTokenModel::active()
                ->where('user_id', $userId)
                ->pluck('token')
                ->toArray();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error getting tokens for user', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    public function getTokensForUserByPlatform(string $userId, string $platform): array
    {
        try {
            return PushTokenModel::active()
                ->byPlatform($platform)
                ->where('user_id', $userId)
                ->pluck('token')
                ->toArray();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error getting tokens for user by platform', [
                'user_id' => $userId,
                'platform' => $platform,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    public function deactivateToken(string $token): bool
    {
        try {
            return PushTokenModel::where('token', $token)
                ->update(['is_active' => false]) > 0;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error deactivating token', [
                'token' => $token,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function deactivateAllTokensForUser(string $userId): bool
    {
        try {
            return PushTokenModel::where('user_id', $userId)
                ->update(['is_active' => false]) > 0;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error deactivating all tokens for user', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function isTokenActive(string $token): bool
    {
        try {
            return PushTokenModel::where('token', $token)
                ->where('is_active', true)
                ->exists();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error checking token status', [
                'token' => $token,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function findById(string $id): ?PushToken
    {
        try {
            $model = PushTokenModel::with('user')->find($id);

            if (!$model) {
                return null;
            }

            return $this->mapToEntity($model);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error finding push token by ID', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    public function findByToken(string $token): ?PushToken
    {
        try {
            $model = PushTokenModel::with('user')->where('token', $token)->first();

            if (!$model) {
                return null;
            }

            return $this->mapToEntity($model);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error finding push token by token', [
                'token' => $token,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    public function deleteToken(string $token): bool
    {
        try {
            return PushTokenModel::where('token', $token)->delete() > 0;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error deleting push token', [
                'token' => $token,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function cleanInactiveTokens(): int
    {
        try {
            return PushTokenModel::where('is_active', false)->delete();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error cleaning inactive tokens', [
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Récupérer tous les tokens actifs pour une liste d'utilisateurs
     */
    public function getTokensForUsers(array $userIds): array
    {
        try {
            return PushTokenModel::active()
                ->whereIn('user_id', $userIds)
                ->pluck('token')
                ->toArray();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error getting tokens for users', [
                'user_ids' => $userIds,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Récupérer tous les tokens actifs pour les participants d'une session
     */
    public function getTokensForSessionParticipants(array $participants): array
    {
        try {
            $userIds = array_column($participants, 'id');
            return $this->getTokensForUsers($userIds);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error getting tokens for session participants', [
                'participants' => $participants,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Mapper le modèle Eloquent vers l'entité
     */
    private function mapToEntity(PushTokenModel $model): PushToken
    {
        $user = null;
        if ($model->user) {
            $user = new User(
                id: $model->user->id,
                firstname: $model->user->firstname,
                lastname: $model->user->lastname,
                email: $model->user->email,
                phone: $model->user->phone ?? null,
                role: $model->user->role ?? 'player'
            );
        }

        return new PushToken(
            id: $model->id,
            userId: $model->user_id,
            token: $model->token,
            platform: $model->platform,
            isActive: $model->is_active,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
            user: $user
        );
    }
}
