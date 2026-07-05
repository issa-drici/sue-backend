<?php

namespace App\Exceptions;

use Exception;

/**
 * Tentative de création de profil sans qu'une vérification OTP récente et valide
 * n'ait été effectuée pour ce numéro.
 */
class PhoneNotVerifiedException extends Exception
{
    public function __construct()
    {
        parent::__construct('Numéro non vérifié. Merci de recommencer la vérification.');
    }
}
