<?php

namespace Tests\Unit;

use App\Services\PhoneNumber;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PhoneNumberTest extends TestCase
{
    public static function normalisations(): array
    {
        return [
            'national' => ['0612345678', '+33612345678'],
            'national avec espaces' => ['06 12 34 56 78', '+33612345678'],
            'national avec points et tirets' => ['06.12-34.56.78', '+33612345678'],
            'international sans +' => ['33612345678', '+33612345678'],
            'déjà E.164' => ['+33612345678', '+33612345678'],
            '9 chiffres mobile (6)' => ['612345678', '+33612345678'],
            '9 chiffres mobile (7)' => ['712345678', '+33712345678'],
            '9 chiffres fixe : inchangé' => ['112345678', '112345678'],
            'étranger : inchangé' => ['+1 415 555 0100', '+14155550100'],
            'espaces autour' => ['  0612345678  ', '+33612345678'],
        ];
    }

    #[DataProvider('normalisations')]
    public function test_normalise(string $input, string $expected): void
    {
        $this->assertSame($expected, PhoneNumber::normalize($input));
    }

    public function test_seuls_les_mobiles_francais_recoivent_un_code(): void
    {
        $this->assertTrue(PhoneNumber::canReceiveOtp('+33612345678'));
        $this->assertTrue(PhoneNumber::canReceiveOtp('+33712345678'));
        $this->assertFalse(PhoneNumber::canReceiveOtp('+33112345678')); // fixe
        $this->assertFalse(PhoneNumber::canReceiveOtp('+33899123456')); // surtaxé
        $this->assertFalse(PhoneNumber::canReceiveOtp('+3361234567'));  // trop court
        $this->assertFalse(PhoneNumber::canReceiveOtp('+336123456789')); // trop long
        $this->assertFalse(PhoneNumber::canReceiveOtp('+32470123456')); // Belgique
        $this->assertFalse(PhoneNumber::canReceiveOtp('0612345678'));    // non normalisé
    }

    public function test_validite_e164(): void
    {
        $this->assertTrue(PhoneNumber::isValid('+33612345678'));
        $this->assertTrue(PhoneNumber::isValid('+14155550100'));
        $this->assertTrue(PhoneNumber::isValid('+12345678'));          // 8 chiffres minimum
        $this->assertFalse(PhoneNumber::isValid('+1234567'));
        $this->assertFalse(PhoneNumber::isValid('+1234567890123456')); // 16 chiffres
        $this->assertFalse(PhoneNumber::isValid('+0612345678'));       // indicatif 0
        $this->assertFalse(PhoneNumber::isValid('0612345678'));
        $this->assertFalse(PhoneNumber::isValid('+33 6 12'));
    }
}
