<?php

namespace App\UseCases\Profile;

use App\Repositories\UserProfile\UserProfileRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use App\Entities\UserProfile;
use Illuminate\Support\Str;

class UpdateUserGoalsUseCase
{
    public function __construct(
        private UserProfileRepositoryInterface $userProfileRepository
    ) {}

    public function execute(?string $currentGoals): array
    {
        // Vérification de l'authentification
        $user = Auth::user();
        if (!$user) {
            throw ValidationException::withMessages([
                'auth' => ['Utilisateur non authentifié']
            ]);
        }

        // Si la chaîne est vide, on la convertit en null
        $currentGoals = $currentGoals === '' ? null : $currentGoals;

        // Récupération ou création du profil
        $userProfile = $this->userProfileRepository->findByUserId($user->id);
        if (!$userProfile) {
            $userProfile = new UserProfile(
                id: Str::uuid()->toString(),
                userId: $user->id,
                avatarFileId: null,
                totalXp: 0,
                completedVideos: 0,
                completedDays: 0,
                totalTrainingTime: 0,
                currentGoals: $currentGoals
            );
        } else {
            $userProfile->setCurrentGoals($currentGoals);
        }

        // Sauvegarde du profil
        $updatedProfile = $this->userProfileRepository->save($userProfile);

        return [
            'current_goals' => $updatedProfile->getCurrentGoals()
        ];
    }
}
