<?php

namespace App\Repositories\User;

use App\Entities\User;
use App\Entities\UserProfile;
use App\Models\UserModel;
use App\Models\UserProfileModel;
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
            $userModel->role,
            $userModel->sports_preferences
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
            $userModel->role,
            $userModel->sports_preferences
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
            $userModel->role,
            $userModel->sports_preferences
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
            $userModel->role,
            $userModel->sports_preferences
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

        // Résoudre l'URL de l'avatar (user_profiles.avatar_file_id -> files.url)
        $avatarUrl = null;
        $profile = \App\Models\UserProfileModel::where('user_id', $userId)->first();
        if ($profile && $profile->avatar_file_id) {
            $avatarUrl = FileModel::find($profile->avatar_file_id)?->url;
        }

        return new UserProfile(
            $userModel->id,
            $userModel->firstname,
            $userModel->lastname,
            $userModel->email,
            $avatarUrl,
            [],
            $userModel->sports_preferences
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
                $this->buildFlexibleSearchQuery($q, $cleanQuery);
            })
            ->paginate($limit, ['*'], 'page', $page);
    }

    /**
     * Construit une requête de recherche flexible selon les spécifications FR-20250122-007
     */
    private function buildFlexibleSearchQuery($query, string $searchTerm): void
    {
        // Diviser la requête en mots-clés
        $keywords = explode(' ', trim($searchTerm));
        $keywords = array_filter($keywords); // Supprimer les espaces vides

        if (count($keywords) === 1) {
            // Recherche par un seul mot : prénom OU nom OU email
            $keyword = strtolower($keywords[0]);
            $query->where(function($q) use ($keyword) {
                $q->whereRaw('LOWER(firstname) LIKE ?', ["%{$keyword}%"])
                  ->orWhereRaw('LOWER(lastname) LIKE ?', ["%{$keyword}%"])
                  ->orWhereRaw('LOWER(email) LIKE ?', ["%{$keyword}%"]);
            });
        } else {
            // Recherche par plusieurs mots : combinaisons possibles
            $query->where(function($q) use ($keywords, $searchTerm) {
                // Prénom + Nom (ordre normal)
                $q->where(function($subQ) use ($keywords) {
                    $subQ->whereRaw('LOWER(firstname) LIKE ?', ['%' . strtolower($keywords[0]) . '%'])
                         ->whereRaw('LOWER(lastname) LIKE ?', ['%' . strtolower($keywords[1]) . '%']);
                })
                // Nom + Prénom (ordre inversé)
                ->orWhere(function($subQ) use ($keywords) {
                    $subQ->whereRaw('LOWER(firstname) LIKE ?', ['%' . strtolower($keywords[1]) . '%'])
                         ->whereRaw('LOWER(lastname) LIKE ?', ['%' . strtolower($keywords[0]) . '%']);
                })
                // Recherche par email (pour les cas comme "jean.dupont@email.com")
                ->orWhere(function($subQ) use ($searchTerm) {
                    $subQ->whereRaw('LOWER(email) LIKE ?', ['%' . strtolower($searchTerm) . '%']);
                });
            });
        }
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

    /**
     * Résout les URLs d'avatar pour un lot d'utilisateurs en une seule requête.
     * Retourne un tableau [userId => url] (uniquement ceux qui ont un avatar).
     */
    public function getAvatarUrls(array $userIds): array
    {
        if (empty($userIds)) {
            return [];
        }

        return UserProfileModel::whereIn('user_id', $userIds)
            ->whereNotNull('avatar_file_id')
            ->with('avatarFile')
            ->get()
            ->mapWithKeys(fn ($profile) => [$profile->user_id => $profile->avatarFile?->url])
            ->filter()
            ->toArray();
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

    public function findByPhoneNumbers(array $phoneNumbers): array
    {
        if (empty($phoneNumbers)) {
            return [];
        }

        // Normaliser les numéros de téléphone pour la recherche
        $normalizedPhones = array_map([$this, 'normalizePhoneNumber'], $phoneNumbers);

        // Rechercher les utilisateurs avec ces numéros
        // On doit normaliser aussi les numéros en base pour la comparaison
        $users = UserModel::all()->filter(function ($userModel) use ($normalizedPhones) {
            $userNormalizedPhone = $this->normalizePhoneNumber($userModel->phone);
            return in_array($userNormalizedPhone, $normalizedPhones);
        });

        // Créer un mapping phone => User pour faciliter la recherche
        $result = [];
        foreach ($users as $userModel) {
            $normalizedPhone = $this->normalizePhoneNumber($userModel->phone);
            $result[$normalizedPhone] = new User(
                $userModel->id,
                $userModel->firstname,
                $userModel->lastname,
                $userModel->email,
                $userModel->phone,
                $userModel->role,
                $userModel->sports_preferences
            );
        }

        return $result;
    }

    /**
     * Normalise un numéro de téléphone pour la comparaison
     * Supprime les espaces, tirets et autres caractères de formatage
     * Gère les formats internationaux et nationaux
     */
    private function normalizePhoneNumber(string $phone): string
    {
        // Supprimer tous les caractères non numériques sauf le +
        $phone = preg_replace('/[^\d+]/', '', $phone);

        // Si le numéro commence par 0 et n'a pas de préfixe international, le remplacer par +33
        if (preg_match('/^0(\d{9})$/', $phone, $matches)) {
            $phone = '+33' . $matches[1];
        }

        // S'assurer que le numéro commence par +
        if (!str_starts_with($phone, '+')) {
            // Si c'est un numéro français sans préfixe, ajouter +33
            if (preg_match('/^33(\d{9})$/', $phone, $matches)) {
                $phone = '+33' . $matches[1];
            } elseif (preg_match('/^(\d{9})$/', $phone)) {
                $phone = '+33' . $phone;
            }
        }

        return $phone;
    }
}
