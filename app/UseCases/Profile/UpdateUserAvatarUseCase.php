<?php

namespace App\UseCases\Profile;

use App\Models\UserProfileModel;
use App\UseCases\File\CreateFileUsecase;
use App\UseCases\File\DeleteFileUsecase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UpdateUserAvatarUseCase
{
    public function __construct(
        private CreateFileUsecase $createFileUsecase,
        private DeleteFileUsecase $deleteFileUsecase
    ) {}

    public function execute(UploadedFile $avatar): array
    {
        $user = Auth::user();
        if (!$user) {
            throw ValidationException::withMessages([
                'auth' => ['Utilisateur non authentifié']
            ]);
        }

        // 1. Uploader le nouveau fichier (stockage objet R2)
        $folderUuid = Str::uuid()->toString();
        $file = $this->createFileUsecase->execute($avatar, "users/avatars/{$folderUuid}");
        $newAvatarId = $file->getId();

        // 2. Récupérer OU créer la ligne user_profiles (les comptes OTP n'en ont pas
        //    forcément), en mémorisant l'ancien avatar pour le nettoyer ensuite.
        $profile = UserProfileModel::firstOrNew(['user_id' => $user->id]);
        $oldAvatarId = $profile->avatar_file_id;
        $profile->avatar_file_id = $newAvatarId;
        $profile->save();

        // 3. Supprimer l'ancien avatar (fichier + objet R2), sans bloquer en cas d'échec
        if ($oldAvatarId && $oldAvatarId !== $newAvatarId) {
            try {
                $this->deleteFileUsecase->execute($oldAvatarId);
            } catch (\Throwable $e) {
                // Nettoyage best-effort : le nouvel avatar est déjà enregistré.
            }
        }

        return [
            'avatar_url' => $file->getUrl()
        ];
    }
}
