<?php

namespace App\UseCases\User;

use App\Repositories\User\UserRepositoryInterface;
use App\Entities\UserProfile;

class UpdateUserProfileUseCase
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {}

    public function execute(string $userId, array $data): ?UserProfile
    {
        // Validation des données
        $validatedData = $this->validateData($data);

        return $this->userRepository->updateUserProfile($userId, $validatedData);
    }

    private function validateData(array $data): array
    {
        $validated = [];

        if (isset($data['firstname'])) {
            $firstname = trim($data['firstname']);
            if (strlen($firstname) >= 2 && strlen($firstname) <= 100) {
                $validated['firstname'] = $firstname;
            }
        }

        if (isset($data['lastname'])) {
            $lastname = trim($data['lastname']);
            if (strlen($lastname) >= 2 && strlen($lastname) <= 100) {
                $validated['lastname'] = $lastname;
            }
        }

        if (isset($data['avatar'])) {
            $avatar = trim($data['avatar']);
            if (filter_var($avatar, FILTER_VALIDATE_URL)) {
                $validated['avatar'] = $avatar;
            }
        }

        return $validated;
    }
}
