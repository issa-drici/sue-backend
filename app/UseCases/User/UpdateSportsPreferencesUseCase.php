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

    /**
     * Ajoute un sport aux préférences de l'utilisateur s'il n'est pas déjà présent
     */
    public function addSportToPreferences(string $userId, string $sport): ?array
    {
        // Validation du sport
        if (!SportSession::isValidSport($sport)) {
            return null;
        }

        // Récupérer l'utilisateur
        $user = $this->userRepository->findById($userId);
        if (!$user) {
            return null;
        }

        // Récupérer les préférences actuelles
        $currentPreferences = $user->getSportsPreferences() ?? [];

        // Vérifier si le sport est déjà dans les préférences
        if (in_array($sport, $currentPreferences)) {
            return $currentPreferences; // Pas de changement nécessaire
        }

        // Ajouter le sport à la fin de la liste
        $newPreferences = array_merge($currentPreferences, [$sport]);

        // Mettre à jour les préférences
        $user->setSportsPreferences($newPreferences);

        // Sauvegarder en base
        $this->userRepository->update($userId, [
            'sports_preferences' => $newPreferences
        ]);

        return $newPreferences;
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
