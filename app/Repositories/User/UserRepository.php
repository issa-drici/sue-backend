<?php

namespace App\Repositories\User;

use App\Models\UserModel;
use App\Models\FileModel;
use App\Models\SupportRequestModel;
use App\Models\FavoriteModel;
use App\Models\UserExerciseModel;
use Illuminate\Support\Facades\DB;

class UserRepository implements UserRepositoryInterface
{
    public function findById(string $id): ?array
    {
        return UserModel::where('id', $id)
            ->select(['id', 'full_name', 'email'])
            ->first()?->toArray();
    }

    public function findAll(): array
    {
        return UserModel::select(['id', 'full_name', 'email'])
            ->get()
            ->toArray();
    }

    public function deleteUserData(string $userId): bool
    {
        try {
            DB::beginTransaction();

            // Suppression des fichiers (avatars)
            FileModel::where('user_id', $userId)->delete();

            // Suppression des requêtes de support
            SupportRequestModel::where('user_id', $userId)->delete();

            // Suppression des favoris
            FavoriteModel::where('user_id', $userId)->delete();

            // Suppression des exercices utilisateur
            UserExerciseModel::where('user_id', $userId)->delete();

            // Suppression du profil utilisateur (cascade delete configuré)
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