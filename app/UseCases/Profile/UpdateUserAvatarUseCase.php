<?php

namespace App\UseCases\Profile;

use App\UseCases\File\CreateFileUsecase;
use App\UseCases\File\DeleteFileUsecase;
use App\Repositories\UserProfile\UserProfileRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UpdateUserAvatarUseCase
{
    public function __construct(
        private CreateFileUsecase $createFileUsecase,
        private DeleteFileUsecase $deleteFileUsecase,
        private UserProfileRepositoryInterface $userProfileRepository
    ) {}

    public function execute(UploadedFile $avatar): array
    {
        $user = Auth::user();
        if (!$user) {
            throw ValidationException::withMessages([
                'auth' => ['Utilisateur non authentifié']
            ]);
        }

        $newAvatarPath = null;

        try {
            // Générer un UUID unique pour le dossier
            $folderUuid = Str::uuid()->toString();
            $securePath = "users/avatars/{$folderUuid}";
            
            // 1. Créer le nouveau fichier dans le dossier sécurisé
            $file = $this->createFileUsecase->execute($avatar, $securePath);
            $newAvatarId = $file->getId();
            
            // 2. Récupérer le profil utilisateur et l'ancien avatar
            $userProfile = $this->userProfileRepository->findByUserId($user->id);
            $oldAvatarId = $userProfile ? $userProfile->getAvatarFileId() : null;
            
            // 3. Mettre à jour la référence dans le profil
            $userProfile->setAvatarFileId($newAvatarId);
            $this->userProfileRepository->save($userProfile);
            
            // 4. Une fois la référence mise à jour, supprimer l'ancien avatar
            if ($oldAvatarId) {
                $this->deleteFileUsecase->execute($oldAvatarId);
            }

            return [
                'avatar_url' => $file->getUrl()
            ];

        } catch (\Exception $e) {
            throw $e;
        }
    }
} 