<?php

namespace Tests\Unit;

use App\Services\DateFormatterService;
use PHPUnit\Framework\TestCase;

class DateFormatterServiceTest extends TestCase
{
    public function testFormatDateAndTime()
    {
        // Test avec une date spécifique
        $formatted = DateFormatterService::formatDateAndTime('2024-08-05', '10:30');

        // Vérifier que le format est correct (jour de la semaine + date + heure)
        $this->assertStringContainsString('lundi', strtolower($formatted));
        $this->assertStringContainsString('5', $formatted);
        $this->assertStringContainsString('août', strtolower($formatted));
        $this->assertStringContainsString('10h30', $formatted);
    }

    public function testGetSportName()
    {
        $this->assertEquals('Tennis', DateFormatterService::getSportName('tennis'));
        $this->assertEquals('Golf', DateFormatterService::getSportName('golf'));
        $this->assertEquals('Musculation', DateFormatterService::getSportName('musculation'));
        $this->assertEquals('Football', DateFormatterService::getSportName('football'));
        $this->assertEquals('Basketball', DateFormatterService::getSportName('basketball'));

        // Test avec un sport non défini
        $this->assertEquals('Volleyball', DateFormatterService::getSportName('volleyball'));
    }

    public function testGenerateInvitationTitle()
    {
        $title = DateFormatterService::generateInvitationTitle('tennis');
        $this->assertEquals('Invitation Tennis', $title);

        $title = DateFormatterService::generateInvitationTitle('football');
        $this->assertEquals('Invitation Football', $title);
    }

    public function testGenerateInvitationMessage()
    {
        $message = DateFormatterService::generateInvitationMessage('tennis', '2024-08-05', '10:30');

        // Vérifier que le message contient les éléments attendus
        $this->assertStringContainsString('Vous avez été invité à une session de Tennis', $message);
        $this->assertStringContainsString('lundi', strtolower($message));
        $this->assertStringContainsString('5', $message);
        $this->assertStringContainsString('août', strtolower($message));
        $this->assertStringContainsString('10h30', $message);
    }

    public function testGeneratePushInvitationTitle()
    {
        $title = DateFormatterService::generatePushInvitationTitle('tennis');
        $this->assertEquals('🏃‍♂️ Invitation Tennis', $title);

        $title = DateFormatterService::generatePushInvitationTitle('golf');
        $this->assertEquals('🏃‍♂️ Invitation Golf', $title);
    }

    public function testGeneratePushReinvitationTitle()
    {
        $title = DateFormatterService::generatePushReinvitationTitle('tennis');
        $this->assertEquals('🏃‍♂️ Nouvelle invitation Tennis', $title);

        $title = DateFormatterService::generatePushReinvitationTitle('football');
        $this->assertEquals('🏃‍♂️ Nouvelle invitation Football', $title);
    }

    public function testFormatDateAndTimeWithDifferentDates()
    {
        // Test avec différents jours de la semaine
        $formatted = DateFormatterService::formatDateAndTime('2024-08-06', '14:00');
        $this->assertStringContainsString('mardi', strtolower($formatted));
        $this->assertStringContainsString('14h00', $formatted);

        $formatted = DateFormatterService::formatDateAndTime('2024-08-07', '18:30');
        $this->assertStringContainsString('mercredi', strtolower($formatted));
        $this->assertStringContainsString('18h30', $formatted);
    }

    public function testGenerateCommentTitle()
    {
        $title = DateFormatterService::generateCommentTitle('tennis');
        $this->assertEquals('Commentaire Tennis', $title);
        
        $title = DateFormatterService::generateCommentTitle('football');
        $this->assertEquals('Commentaire Football', $title);
    }

    public function testGeneratePushCommentTitle()
    {
        $title = DateFormatterService::generatePushCommentTitle('tennis');
        $this->assertEquals('💬 Commentaire Tennis', $title);
        
        $title = DateFormatterService::generatePushCommentTitle('golf');
        $this->assertEquals('💬 Commentaire Golf', $title);
    }

    public function testGenerateCommentMessage()
    {
        $message = DateFormatterService::generateCommentMessage('Jean Dupont', 'tennis', '2024-08-05', '10:30');
        
        // Vérifier que le message contient les éléments attendus
        $this->assertStringContainsString('Jean Dupont a commenté votre session de Tennis', $message);
        $this->assertStringContainsString('lundi', strtolower($message));
        $this->assertStringContainsString('5', $message);
        $this->assertStringContainsString('août', strtolower($message));
        $this->assertStringContainsString('10h30', $message);
    }

    public function testGenerateCommentMessageShort()
    {
        $message = DateFormatterService::generateCommentMessageShort('Marie Martin', 'football');
        $this->assertEquals('Marie Martin a commenté la session de Football', $message);
        
        $message = DateFormatterService::generateCommentMessageShort('Pierre Durand', 'golf');
        $this->assertEquals('Pierre Durand a commenté la session de Golf', $message);
    }

    public function testFormatCommentDate()
    {
        $dateTime = new \DateTime('2024-08-05 10:30:00');
        $formatted = DateFormatterService::formatCommentDate($dateTime);
        
        $this->assertStringContainsString('lundi', strtolower($formatted));
        $this->assertStringContainsString('5', $formatted);
        $this->assertStringContainsString('août', strtolower($formatted));
        $this->assertStringContainsString('10h30', $formatted);
    }
}
