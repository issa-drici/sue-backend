<?php

namespace App\UseCases\Profile;

use App\Repositories\User\UserRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class FindUserProfileUseCase
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {}

    public function execute(): array
    {
        $user = Auth::user();
        if (!$user) {
            throw ValidationException::withMessages([
                'auth' => ['Utilisateur non authentifié']
            ]);
        }

        // Récupérer le profil utilisateur
        $profile = $this->userRepository->getUserProfile($user->id);

        if (!$profile) {
            throw ValidationException::withMessages([
                'profile' => ['Profil utilisateur non trouvé']
            ]);
        }

        return [
            'user' => [
                'id' => $profile->getId(),
                'firstname' => $profile->getFirstname(),
                'lastname' => $profile->getLastname(),
                'email' => $profile->getEmail(),
                'avatar_url' => $profile->getAvatar()
            ],
            'stats' => $profile->getStats()
        ];
    }
}
