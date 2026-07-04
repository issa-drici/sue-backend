<?php

namespace App\UseCases\SportSession;

use App\Entities\SportSession;
use App\Exceptions\InvalidShareLinkException;
use App\Repositories\SportSession\SportSessionRepositoryInterface;

/**
 * Résout un token de partage vers une session, pour l'aperçu public
 * (landing web + écran de preview de l'app). Ne nécessite pas d'authentification.
 *
 * Toute erreur (token inexistant, expiré, session annulée) est remontée via
 * InvalidShareLinkException afin que le contrôleur renvoie une réponse générique.
 */
class ResolveSessionByShareTokenUseCase
{
    public function __construct(
        private SportSessionRepositoryInterface $sportSessionRepository
    ) {}

    public function execute(string $shareToken): SportSession
    {
        $session = $this->sportSessionRepository->findByShareToken($shareToken);

        if (!$session) {
            throw new InvalidShareLinkException('token_not_found');
        }

        if (!$session->isShareLinkActive()) {
            throw new InvalidShareLinkException('link_expired_or_cancelled');
        }

        return $session;
    }
}
