<?php

namespace App\Repositories\User;

use App\Entities\User;
use App\Entities\UserProfile;
use App\Models\UserModel;
use App\Models\FileModel;
use App\Models\SupportRequestModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Pagination\LengthAwarePaginator;

class UserRepository implements UserRepositoryInterface
{
    public function findById(string $id): ?User
    {
        $userModel = UserModel::where('id', $id)->first();
        if (!$userModel) {
            return null;
        }

        return new User(
            $userModel->id,
            $userModel->firstname,
            $userModel->lastname,
            $userModel->email,
            $userModel->phone,
            $userModel->role
        );
    }

    public function findByEmail(string $email): ?User
    {
        $userModel = UserModel::where('email', $email)->first();
        if (!$userModel) {
            return null;
        }

        return new User(
            $userModel->id,
            $userModel->firstname,
            $userModel->lastname,
            $userModel->email,
            $userModel->phone,
            $userModel->role
        );
    }

    public function create(array $data): User
    {
        $userModel = UserModel::create($data);
        return new User(
            $userModel->id,
            $userModel->firstname,
            $userModel->lastname,
            $userModel->email,
            $userModel->phone,
            $userModel->role
        );
    }

    public function update(string $id, array $data): ?User
    {
        $userModel = UserModel::where('id', $id)->first();
        if (!$userModel) {
            return null;
        }

        $userModel->update($data);
        return new User(
            $userModel->id,
            $userModel->firstname,
            $userModel->lastname,
            $userModel->email,
            $userModel->phone,
            $userModel->role
        );
    }

    public function delete(string $id): bool
    {
        return UserModel::where('id', $id)->delete() > 0;
    }

    public function getUserProfile(string $userId): ?UserProfile
    {
        $userModel = UserModel::where('id', $userId)->first();
        if (!$userModel) {
            return null;
        }

        return new UserProfile(
            $userModel->id,
            $userModel->firstname,
            $userModel->lastname,
            $userModel->email,
            $userModel->avatar ?? null,
            []
        );
    }

    public function updateUserProfile(string $userId, array $data): ?UserProfile
    {
        $userModel = UserModel::where('id', $userId)->first();
        if (!$userModel) {
            return null;
        }

        $userModel->update($data);
        return $this->getUserProfile($userId);
    }

    public function searchUsers(string $query, string $currentUserId, int $page = 1, int $limit = 20): LengthAwarePaginator
    {
        // Nettoyer et normaliser la requête de recherche
        $cleanQuery = $this->normalizeSearchQuery($query);

        // Si la requête est vide après nettoyage, retourner un résultat vide
        if (empty($cleanQuery)) {
            return UserModel::where('id', '!=', $currentUserId)
                ->whereRaw('1 = 0') // Condition impossible pour retourner 0 résultats
                ->paginate($limit, ['*'], 'page', $page);
        }

        return UserModel::where('id', '!=', $currentUserId)
            ->where(function($q) use ($cleanQuery) {
                $q->whereRaw('LOWER(firstname) LIKE ?', ['%' . strtolower($cleanQuery) . '%'])
                  ->orWhereRaw('LOWER(lastname) LIKE ?', ['%' . strtolower($cleanQuery) . '%'])
                  ->orWhereRaw('LOWER(email) LIKE ?', ['%' . strtolower($cleanQuery) . '%']);
            })
            ->paginate($limit, ['*'], 'page', $page);
    }

    /**
     * Normalise une requête de recherche pour améliorer les résultats
     */
    private function normalizeSearchQuery(string $query): string
    {
        // Supprimer les espaces en début et fin
        $query = trim($query);

        // Remplacer les espaces multiples par un seul espace
        $query = preg_replace('/\s+/', ' ', $query);

        // Supprimer les caractères spéciaux qui pourraient causer des problèmes
        $query = preg_replace('/[^\w\s@.-]/', '', $query);

        return $query;
    }

    public function updateEmail(string $userId, string $newEmail): bool
    {
        return UserModel::where('id', $userId)->update(['email' => $newEmail]) > 0;
    }

    public function updatePassword(string $userId, string $newPassword): bool
    {
        return UserModel::where('id', $userId)->update(['password' => Hash::make($newPassword)]) > 0;
    }

    public function verifyPassword(string $userId, string $password): bool
    {
        $userModel = UserModel::where('id', $userId)->first();
        if (!$userModel) {
            return false;
        }

        return Hash::check($password, $userModel->password);
    }

    public function emailExists(string $email, ?string $excludeUserId = null): bool
    {
        $query = UserModel::where('email', $email);
        if ($excludeUserId) {
            $query->where('id', '!=', $excludeUserId);
        }
        return $query->exists();
    }

    public function deleteUserData(string $userId): bool
    {
        try {
            DB::beginTransaction();

            // Suppression des fichiers (avatars)
            FileModel::where('user_id', $userId)->delete();

            // Suppression des requêtes de support
            SupportRequestModel::where('user_id', $userId)->delete();

            // Suppression de l'utilisateur (cascade delete configuré)
            UserModel::where('id', $userId)->delete();

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            return false;
        }
    }
}
