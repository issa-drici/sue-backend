<?php

namespace Tests\Unit;

use App\Entities\SportSession;
use App\Entities\User;
use App\Services\DateFormatterService;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

/**
 * Compléments à DateFormatterServiceTest : dates, heures, créneaux, rappels.
 */
class DateFormatterServiceExtraTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_format_date_en_francais(): void
    {
        $this->assertSame('mardi 5 août', DateFormatterService::formatDate('2025-08-05'));
        $this->assertSame('jeudi 1 janvier', DateFormatterService::formatDate('2026-01-01'));
        $this->assertSame('le pas-une-date', DateFormatterService::formatDate('pas-une-date'));
    }

    public function test_format_heure(): void
    {
        $this->assertSame('10h30', DateFormatterService::formatTime('10:30'));
        $this->assertSame('09h05', DateFormatterService::formatTime('09:05:59'));
        $this->assertSame('demain', DateFormatterService::formatTime('demain'));
    }

    public function test_libelle_de_creneau(): void
    {
        $this->assertSame('mardi 27 octobre à 19h00', DateFormatterService::formatSlotLabel(['date' => '2026-10-27', 'startTime' => '19:00']));
    }

    public function test_titres_et_messages_de_rappel(): void
    {
        $this->assertSame('⏰ Rappel Tennis', DateFormatterService::generateReminder24hTitle('tennis'));
        $this->assertSame('⏰ Rappel Football', DateFormatterService::generateReminder1hTitle('football'));
        $this->assertSame('🏃‍♂️ Session Basketball', DateFormatterService::generateReminderStartTitle('basketball'));
        $this->assertSame('Votre session de Basketball commence maintenant !', DateFormatterService::generateReminderStartMessage('basketball'));

        $this->assertSame(
            'Votre session de Football commence dans 1 heure de 18h00 à 20h00',
            DateFormatterService::generateReminder1hMessage('football', '2026-01-01', '18:00', '20:00')
        );
        $this->assertSame(
            'Votre session de Football commence dans 1 heure à 18h00',
            DateFormatterService::generateReminder1hMessage('football', '2026-01-01', '18:00')
        );
    }

    public function test_rappel_24h_dit_demain_seulement_si_c_est_demain(): void
    {
        $tomorrow = (new \DateTime('tomorrow', new \DateTimeZone('Europe/Paris')))->format('Y-m-d');
        $later = (new \DateTime('+5 days', new \DateTimeZone('Europe/Paris')))->format('Y-m-d');
        $label = DateFormatterService::formatDate($tomorrow);

        $this->assertSame(
            "Votre session de Tennis commence demain {$label} de 17h00 à 19h00",
            DateFormatterService::generateReminder24hMessage('tennis', $tomorrow, '17:00', '19:00')
        );
        $this->assertSame(
            "Votre session de Tennis commence demain {$label} à 17h00",
            DateFormatterService::generateReminder24hMessage('tennis', $tomorrow, '17:00')
        );
        $this->assertStringStartsWith('Votre session de Tennis commence ' . DateFormatterService::formatDate($later), DateFormatterService::generateReminder24hMessage('tennis', $later, '17:00', '19:00'));
        $this->assertStringNotContainsString('demain', DateFormatterService::generateReminder24hMessage('tennis', $later, '17:00'));
    }

    public function test_message_d_invitation_selon_le_mode_de_planification(): void
    {
        $orga = new User('o', 'Orga', 'Test', null, null);
        $slot = ['id' => 's1', 'date' => '2026-10-27', 'startTime' => '19:00', 'endTime' => '20:00', 'startDate' => '2026-10-27T18:00:00+00:00', 'availabilityCount' => 0];

        $findingOne = new SportSession('id', 'tennis', '2026-10-27 18:00', '2026-10-27 19:00', 'Club', null, null, 'active', $orga, [], null, 'finding_slot', [$slot]);
        $findingTwo = new SportSession('id', 'tennis', '2026-10-27 18:00', '2026-10-28 19:00', 'Club', null, null, 'active', $orga, [], null, 'finding_slot', [$slot, ['id' => 's2'] + $slot]);
        $scheduled = new SportSession('id', 'tennis', new \DateTime('2026-10-27 08:30', new \DateTimeZone('UTC')), new \DateTime('2026-10-27 10:30', new \DateTimeZone('UTC')), 'Club', null, null, 'active', $orga);

        $this->assertStringContainsString('date à définir (1 créneau proposé)', DateFormatterService::generateSessionInvitationMessage($findingOne));
        $this->assertStringContainsString('(2 créneaux proposés)', DateFormatterService::generateSessionInvitationMessage($findingTwo));
        // Heures converties en Europe/Paris (UTC+1 fin octobre)
        $this->assertSame(
            DateFormatterService::generateInvitationMessage('tennis', '2026-10-27', '09:30', '11:30'),
            DateFormatterService::generateSessionInvitationMessage($scheduled)
        );
    }
}
