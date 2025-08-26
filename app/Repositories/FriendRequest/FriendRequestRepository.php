<?php

namespace App\Repositories\FriendRequest;

use App\Entities\FriendRequest;
use App\Entities\FriendRequestEntity;
use App\Models\FriendRequestModel;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class FriendRequestRepository implements FriendRequestRepositoryInterface
{
    public function findById(string $id): ?FriendRequest
    {
        $model = FriendRequestModel::with('sender')->find($id);

        if (!$model) {
            return null;
        }

        return $this->mapToEntity($model);
    }

    public function getUserFriendRequests(string $userId, int $page = 1, int $limit = 20): LengthAwarePaginator
    {
        $paginator = FriendRequestModel::with('sender')
            ->byReceiver($userId)
            ->pending()
            ->orderBy('created_at', 'desc')
            ->paginate($limit, ['*'], 'page', $page);

        $paginator->getCollection()->transform(function ($model) {
            return [
                'id' => $model->id,
                'sender' => [
                    'id' => $model->sender->id,
                    'firstname' => $model->sender->firstname,
                    'lastname' => $model->sender->lastname,
                    'email' => $model->sender->email,
                    'avatar' => null
                ],
                'status' => $model->status,
                'created_at' => $model->created_at->toISOString(),
                'updated_at' => $model->updated_at->toISOString(),
                'cancelled_at' => $model->cancelled_at?->toISOString()
            ];
        });

        return $paginator;
    }

    public function createRequest(string $senderId, string $receiverId): array
    {
        try {
            // Vérifier s'il existe déjà une demande entre ces utilisateurs
            $existingRequest = FriendRequestModel::where(function ($query) use ($senderId, $receiverId) {
                $query->where('sender_id', $senderId)
                      ->where('receiver_id', $receiverId);
            })->orWhere(function ($query) use ($senderId, $receiverId) {
                $query->where('sender_id', $receiverId)
                      ->where('receiver_id', $senderId);
            })->first();

            if ($existingRequest) {
                // Si la demande est active (non annulée), retourner une erreur
                if ($existingRequest->status !== 'cancelled' && $existingRequest->cancelled_at === null) {
                    return [
                        'success' => false,
                        'error' => [
                            'code' => 'FRIEND_REQUEST_EXISTS',
                            'message' => 'Une demande d\'ami existe déjà'
                        ]
                    ];
                }

                // Si la demande est annulée, la réactiver
                $updated = $existingRequest->update([
                    'sender_id' => $senderId,
                    'receiver_id' => $receiverId,
                    'status' => 'pending',
                    'cancelled_at' => null, // Réinitialiser l'annulation
                    'updated_at' => now()
                ]);

                return [
                    'success' => $updated,
                    'message' => $updated ? 'Demande d\'ami réactivée' : 'Erreur lors de la réactivation'
                ];
            }

            // Sinon, créer une nouvelle demande
            $friendRequest = FriendRequestModel::create([
                'id' => Str::uuid(),
                'sender_id' => $senderId,
                'receiver_id' => $receiverId,
                'status' => 'pending',
            ]);

            return [
                'success' => $friendRequest !== null,
                'message' => $friendRequest ? 'Demande d\'ami créée' : 'Erreur lors de la création'
            ];

        } catch (\Illuminate\Database\QueryException $e) {
            // Gérer spécifiquement les erreurs de contrainte unique
            if ($e->getCode() === '23000' || str_contains($e->getMessage(), 'UNIQUE constraint failed')) {
                return [
                    'success' => false,
                    'error' => [
                        'code' => 'FRIEND_REQUEST_EXISTS',
                        'message' => 'Une demande d\'ami existe déjà entre ces utilisateurs'
                    ]
                ];
            }

            // Autres erreurs de base de données
            \Illuminate\Support\Facades\Log::error('Database error in createRequest', [
                'senderId' => $senderId,
                'receiverId' => $receiverId,
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ]);

            return [
                'success' => false,
                'error' => [
                    'code' => 'DATABASE_ERROR',
                    'message' => 'Erreur lors de la création de la demande d\'ami'
                ]
            ];
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Unexpected error in createRequest', [
                'senderId' => $senderId,
                'receiverId' => $receiverId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => [
                    'code' => 'UNEXPECTED_ERROR',
                    'message' => 'Erreur inattendue lors de la création de la demande d\'ami'
                ]
            ];
        }
    }

    public function updateRequestStatus(string $requestId, string $status): bool
    {
        $model = FriendRequestModel::find($requestId);

        if (!$model) {
            return false;
        }

        return $model->update(['status' => $status]);
    }

    public function requestExists(string $senderId, string $receiverId): bool
    {
        return FriendRequestModel::where(function ($query) use ($senderId, $receiverId) {
            $query->where('sender_id', $senderId)
                  ->where('receiver_id', $receiverId);
        })->orWhere(function ($query) use ($senderId, $receiverId) {
            $query->where('sender_id', $receiverId)
                  ->where('receiver_id', $senderId);
        })
        ->where('status', '!=', 'cancelled') // Exclure les demandes annulées
        ->whereNull('cancelled_at') // Double vérification pour les demandes annulées
        ->exists();
    }

    public function getPendingRequest(string $senderId, string $receiverId): ?FriendRequest
    {
        $model = FriendRequestModel::with('sender')
            ->where('sender_id', $senderId)
            ->where('receiver_id', $receiverId)
            ->pending()
            ->first();

        if (!$model) {
            return null;
        }

        return $this->mapToEntity($model);
    }

    public function deleteRequest(string $requestId): bool
    {
        $model = FriendRequestModel::find($requestId);

        if (!$model) {
            return false;
        }

        return $model->delete();
    }

    public function findEntityById(string $id): ?FriendRequestEntity
    {
        $model = FriendRequestModel::find($id);

        if (!$model) {
            return null;
        }

        return $this->mapToEntityEntity($model);
    }

    public function findRequestByUsers(string $senderId, string $receiverId): ?FriendRequestEntity
    {
        $model = FriendRequestModel::where('sender_id', $senderId)
            ->where('receiver_id', $receiverId)
            ->first();

        if (!$model) {
            return null;
        }

        return $this->mapToEntityEntity($model);
    }

    public function getRelationshipStatus(string $currentUserId, string $otherUserId): string
    {
        // Chercher une demande d'ami entre les deux utilisateurs (dans les deux sens)
        $request = FriendRequestModel::where(function ($query) use ($currentUserId, $otherUserId) {
            $query->where('sender_id', $currentUserId)
                  ->where('receiver_id', $otherUserId);
        })->orWhere(function ($query) use ($currentUserId, $otherUserId) {
            $query->where('sender_id', $otherUserId)
                  ->where('receiver_id', $currentUserId);
        })->first();

        if (!$request) {
            return 'none';
        }

        // Retourner le statut approprié selon l'état de la demande
        switch ($request->status) {
            case 'pending':
                // Si la demande est en attente, vérifier si elle a été annulée
                if ($request->cancelled_at) {
                    return 'cancelled';
                }
                // Vérifier qui a envoyé la demande
                if ($request->sender_id === $currentUserId) {
                    return 'pending_sent';
                } else {
                    return 'pending_received';
                }
            case 'accepted':
                return 'accepted';
            case 'declined':
                return 'declined';
            case 'cancelled':
                return 'cancelled';

            default:
                return 'none';
        }
    }

    public function cancelRequest(string $requestId): bool
    {
        $model = FriendRequestModel::find($requestId);

        if (!$model) {
            return false;
        }

        return $model->update([
            'cancelled_at' => now(),
            'status' => 'cancelled'
        ]);
    }

    private function mapToEntity(FriendRequestModel $model): FriendRequest
    {
        $sender = $model->sender;

        return new FriendRequest(
            $sender->id,
            $sender->firstname ?? '', // Utiliser firstname avec fallback
            $sender->lastname ?? '', // Utiliser lastname avec fallback
            null, // avatar
            0 // mutualFriends par défaut
        );
    }

    private function mapToEntityEntity(FriendRequestModel $model): FriendRequestEntity
    {
        return new FriendRequestEntity(
            $model->id,
            $model->sender_id,
            $model->receiver_id,
            $model->status,
            $model->cancelled_at?->toISOString()
        );
    }
}
