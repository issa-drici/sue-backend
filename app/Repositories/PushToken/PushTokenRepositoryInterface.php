<?php

namespace App\Repositories\PushToken;

use App\Entities\PushToken;

interface PushTokenRepositoryInterface
{
    /**
     * Sauvegarder ou mettre à jour un token push
     */
    public function saveToken(string $userId, string $token, string $platform = 'expo'): bool;

    /**
     * Récupérer tous les tokens actifs d'un utilisateur
     */
    public function getTokensForUser(string $userId): array;

    /**
     * Récupérer tous les tokens actifs d'un utilisateur avec une plateforme spécifique
     */
    public function getTokensForUserByPlatform(string $userId, string $platform): array;

    /**
     * Désactiver un token
     */
    public function deactivateToken(string $token): bool;

    /**
     * Désactiver tous les tokens d'un utilisateur
     */
    public function deactivateAllTokensForUser(string $userId): bool;

    /**
     * Vérifier si un token existe et est actif
     */
    public function isTokenActive(string $token): bool;

    /**
     * Récupérer un token par son ID
     */
    public function findById(string $id): ?PushToken;

    /**
     * Récupérer un token par sa valeur
     */
    public function findByToken(string $token): ?PushToken;

    /**
     * Supprimer un token
     */
    public function deleteToken(string $token): bool;

    /**
     * Nettoyer les tokens inactifs
     */
    public function cleanInactiveTokens(): int;
}
