<?php

namespace App\Services;

/**
 * Numéros de téléphone : format E.164 (+33XXXXXXXXX), identifiant des comptes.
 */
final class PhoneNumber
{
    /**
     * Normalise un numéro français vers le format E.164 (+33XXXXXXXXX).
     */
    public static function normalize(string $phone): string
    {
        $phone = preg_replace('/[^\d+]/', '', trim($phone));

        // 0XXXXXXXXX → +33XXXXXXXXX
        if (preg_match('/^0(\d{9})$/', $phone, $m)) {
            return '+33' . $m[1];
        }
        // 33XXXXXXXXX → +33XXXXXXXXX
        if (preg_match('/^33(\d{9})$/', $phone, $m)) {
            return '+33' . $m[1];
        }
        // XXXXXXXXX (9 chiffres, commence par 6/7) → +33XXXXXXXXX
        if (preg_match('/^([67]\d{8})$/', $phone, $m)) {
            return '+33' . $m[1];
        }

        return $phone;
    }

    /**
     * Numéros autorisés à recevoir un code SMS : mobiles français uniquement (+336, +337).
     * Bloque le « SMS pumping » : des robots demandent des codes vers des numéros étrangers
     * ou surtaxés pour faire payer les SMS. Élargir ici pour ouvrir un pays (ex: Belgique +324).
     */
    public static function canReceiveOtp(string $phone): bool
    {
        return (bool) preg_match('/^\+33[67]\d{8}$/', $phone);
    }

    public static function isValid(string $phone): bool
    {
        return (bool) preg_match('/^\+[1-9]\d{7,14}$/', $phone);
    }
}
