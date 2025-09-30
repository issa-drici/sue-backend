<?php

namespace App\UseCases\User;

use App\Repositories\User\UserRepositoryInterface;
use App\Entities\SportSession;

class UpdateSportsPreferencesUseCase
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {}

    public function execute(string $userId, array $sportsPreferences): ?array
    {
        // Validation des sports
        if (!$this->validateSports($sportsPreferences)) {
            return null;
        }

        // Mettre à jour les sports préférés
        $user = $this->userRepository->findById($userId);
        if (!$user) {
            return null;
        }

        $user->setSportsPreferences($sportsPreferences);

        // Sauvegarder en base
        $this->userRepository->update($userId, [
            'sports_preferences' => $sportsPreferences
        ]);

        return $sportsPreferences;
    }

    private function validateSports(array $sports): bool
    {
        $validSports = SportSession::getSupportedSports();

        // Vérifier que tous les sports sont valides
        foreach ($sports as $sport) {
            if (!in_array($sport, $validSports)) {
                return false;
            }
        }

        // Limiter à 5 sports maximum
        if (count($sports) > 5) {
            return false;
        }

        return true;
    }
}
