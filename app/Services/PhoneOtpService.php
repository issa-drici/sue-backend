<?php

namespace App\Services;

use App\Exceptions\InvalidOtpException;
use App\Exceptions\OtpResendTooSoonException;
use App\Exceptions\PhoneNotVerifiedException;
use App\Models\PhoneVerificationCodeModel;
use Illuminate\Support\Facades\Hash;

/**
 * Logique de code de vérification par SMS (OTP) :
 * - génération d'un code à 6 chiffres
 * - expiration après 5 minutes
 * - 3 tentatives maximum
 * - délai de 45 s entre deux envois
 * - fenêtre de 10 min après vérification pour finaliser l'inscription
 */
class PhoneOtpService
{
    private const CODE_TTL_MINUTES = 5;
    private const MAX_ATTEMPTS = 3;
    private const RESEND_COOLDOWN_SECONDS = 45;
    private const VERIFIED_WINDOW_MINUTES = 10;

    public function __construct(
        private TextbeltSmsService $sms
    ) {}

    /**
     * Génère un nouveau code, le stocke (haché) et l'envoie par SMS.
     *
     * @throws OtpResendTooSoonException
     */
    public function sendCode(string $phone): void
    {
        $record = PhoneVerificationCodeModel::where('phone', $phone)->first();

        if ($record && $record->last_sent_at) {
            $elapsed = now()->getTimestamp() - $record->last_sent_at->getTimestamp();
            if ($elapsed < self::RESEND_COOLDOWN_SECONDS) {
                throw new OtpResendTooSoonException(self::RESEND_COOLDOWN_SECONDS - $elapsed);
            }
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        PhoneVerificationCodeModel::updateOrCreate(
            ['phone' => $phone],
            [
                'code_hash' => Hash::make($code),
                'expires_at' => now()->addMinutes(self::CODE_TTL_MINUTES),
                'attempts' => 0,
                'verified_at' => null,
                'last_sent_at' => now(),
            ]
        );

        $this->sms->send($phone, "Votre code de vérification SUE est : {$code}");
    }

    /**
     * Vérifie le code fourni pour un numéro. Marque le numéro comme vérifié si OK.
     *
     * @throws InvalidOtpException
     */
    public function verifyCode(string $phone, string $code): void
    {
        $record = PhoneVerificationCodeModel::where('phone', $phone)->first();

        if (!$record) {
            throw new InvalidOtpException('no_code');
        }

        if ($record->expires_at->isPast()) {
            throw new InvalidOtpException('expired');
        }

        if ($record->attempts >= self::MAX_ATTEMPTS) {
            throw new InvalidOtpException('too_many_attempts');
        }

        if (!Hash::check($code, $record->code_hash)) {
            $record->increment('attempts');
            throw new InvalidOtpException('mismatch');
        }

        $record->update(['verified_at' => now()]);
    }

    /**
     * Vérifie qu'une vérification OTP récente et valide existe pour ce numéro
     * (utilisé avant de créer un compte).
     *
     * @throws PhoneNotVerifiedException
     */
    public function assertRecentlyVerified(string $phone): void
    {
        $record = PhoneVerificationCodeModel::where('phone', $phone)->first();

        if (!$record || !$record->verified_at) {
            throw new PhoneNotVerifiedException();
        }

        $limit = now()->subMinutes(self::VERIFIED_WINDOW_MINUTES);
        if ($record->verified_at->lt($limit)) {
            throw new PhoneNotVerifiedException();
        }
    }

    /**
     * Supprime le code après un usage terminé (connexion ou inscription réussie).
     */
    public function consume(string $phone): void
    {
        PhoneVerificationCodeModel::where('phone', $phone)->delete();
    }
}
