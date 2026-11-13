<?php

namespace Tests\Unit;

use App\Entities\SportSession;
use App\Entities\User;
use App\Services\SlotProposalValidator;
use PHPUnit\Framework\TestCase;

/**
 * Règles portées par l'entité SportSession et le validateur de créneaux.
 */
class SportSessionEntityTest extends TestCase
{
    private function session(array $participants = [], array $slots = [], array $overrides = []): SportSession
    {
        $o = $overrides + [
            'start' => new \DateTime('+2 days'),
            'end' => new \DateTime('+2 days +1 hour'),
            'status' => 'active',
            'scheduling' => $slots ? 'finding_slot' : 'scheduled',
            'max' => null,
        ];
        return new SportSession(
            'session-id', 'tennis', $o['start'], $o['end'], 'Club', $o['max'], null, $o['status'],
            new User('orga', 'Orga', 'Test', 'orga@test', '+33600000000'),
            $participants, 'token', $o['scheduling'], $slots
        );
    }

    private function slot(string $id, int $count, string $startDate): array
    {
        return ['id' => $id, 'date' => substr($startDate, 0, 10), 'startTime' => '18:00', 'endTime' => '19:00', 'startDate' => $startDate, 'availabilityCount' => $count, 'availableUserIds' => []];
    }

    public function test_meilleur_creneau_le_plus_de_dispos_puis_le_plus_tot(): void
    {
        $session = $this->session([], [
            $this->slot('late', 2, '2026-10-29T17:00:00+00:00'),
            $this->slot('early', 2, '2026-10-27T17:00:00+00:00'),
            $this->slot('few', 1, '2026-10-26T17:00:00+00:00'),
        ]);
        $this->assertSame('early', $session->getBestSlotProposal()['id']);
        $this->assertNull($this->session()->getBestSlotProposal());
    }

    public function test_statut_et_invites_a_consulter(): void
    {
        $session = $this->session([
            ['id' => 'orga', 'status' => 'accepted'],
            ['id' => 'paul', 'status' => 'pending'],
            ['id' => 'dina', 'status' => 'declined'],
            ['id' => 'zoe', 'status' => 'accepted', 'fullName' => 'Zoe Z', 'avatarUrl' => 'https://a/z.png'],
        ]);

        $this->assertSame('organizer', $session->getUserStatus('orga'));
        $this->assertSame('pending', $session->getUserStatus('paul'));
        $this->assertNull($session->getUserStatus('inconnu'));
        $this->assertSame(['paul', 'zoe'], array_column($session->getSlotInvitees(), 'id'));
        $this->assertSame(2, $session->getAcceptedParticipantsCount());
        $this->assertTrue($session->isAcceptedParticipant('zoe'));
        $this->assertFalse($session->isAcceptedParticipant('paul'));
        $this->assertTrue($session->canUserAccess('dina'));
        $this->assertFalse($session->canUserAccess('inconnu'));
        $this->assertSame(['fullName' => 'Zoe Z', 'avatarUrl' => 'https://a/z.png'], $session->findParticipantPublicInfo('zoe'));
        $this->assertNull($session->findParticipantPublicInfo('inconnu'));
    }

    public function test_lien_de_partage_actif(): void
    {
        $this->assertTrue($this->session()->isShareLinkActive());
        $this->assertFalse($this->session([], [], ['status' => 'cancelled'])->isShareLinkActive());
        $this->assertFalse($this->session([], [], ['start' => new \DateTime('-3 hours'), 'end' => new \DateTime('-1 hour')])->isShareLinkActive());
        // Session commencée mais pas terminée : lien encore valide
        $this->assertTrue($this->session([], [], ['start' => new \DateTime('-1 hour'), 'end' => new \DateTime('+1 hour')])->isShareLinkActive());
    }

    public function test_apercu_public_sans_identifiants(): void
    {
        $preview = $this->session([['id' => 'orga', 'status' => 'accepted', 'fullName' => 'Orga Test'], ['id' => 'paul', 'status' => 'pending', 'fullName' => 'Paul P']])->toPublicPreview();

        $this->assertArrayNotHasKey('id', $preview);
        $this->assertSame([true, false], array_column($preview['participants'], 'isOrganizer'));
        foreach ($preview['participants'] as $participant) {
            $this->assertArrayNotHasKey('id', $participant);
        }
        $this->assertSame(1, $preview['participantsCount']);
        $this->assertSame('Tennis', $preview['sportName']);
    }

    public function test_validateur_de_creneau(): void
    {
        $future = (new \DateTime('+3 days', new \DateTimeZone('Europe/Paris')))->format('Y-m-d');
        SlotProposalValidator::validate(['date' => $future, 'startTime' => '23:00', 'endTime' => '00:30']); // fin le lendemain : OK

        foreach ([
            [['date' => 'x', 'startTime' => '10:00', 'endTime' => '11:00'], 'Créneau invalide'],
            [['date' => $future, 'startTime' => '10:00'], 'Créneau invalide'],
            [['date' => $future, 'startTime' => '10:00', 'endTime' => '10:00'], "L'heure de fin doit être différente de l'heure de début"],
            [['date' => '2020-01-01', 'startTime' => '10:00', 'endTime' => '11:00'], 'Un créneau proposé est déjà passé'],
        ] as [$slot, $message]) {
            try {
                SlotProposalValidator::validate($slot);
                $this->fail('Exception attendue pour ' . json_encode($slot));
            } catch (\Exception $e) {
                $this->assertSame($message, $e->getMessage());
            }
        }

        $this->assertSame(
            SlotProposalValidator::startKey(['date' => $future, 'startTime' => '10:00', 'endTime' => '11:00']),
            SlotProposalValidator::startKey(['date' => $future, 'startTime' => '10:00', 'endTime' => '12:00'])
        );
    }
}
