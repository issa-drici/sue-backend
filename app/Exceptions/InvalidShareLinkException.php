<?php

namespace App\Exceptions;

use Exception;

/**
 * Levée lorsqu'un token de partage est inexistant, expiré ou lié à une
 * session annulée. La raison précise sert uniquement aux logs serveur :
 * la réponse renvoyée au client reste générique pour ne pas faciliter
 * l'énumération des tokens.
 */
class InvalidShareLinkException extends Exception
{
    public function __construct(
        public readonly string $reason = 'invalid_share_link'
    ) {
        parent::__construct('Lien de session invalide ou expiré');
    }
}
