<?php

namespace Tests\Unit;

use App\Services\SportService;
use PHPUnit\Framework\TestCase;

class SportServiceTest extends TestCase
{
    public function test_get_supported_sports_returns_48_sports()
    {
        $sports = SportService::getSupportedSports();

        $this->assertCount(48, $sports);
        $this->assertIsArray($sports);
    }

    public function test_supported_sports_are_alphabetically_sorted()
    {
        $sports = SportService::getSupportedSports();
        $sortedSports = $sports;

        // Trier en ignorant les accents comme dans SportService
        usort($sortedSports, function($a, $b) {
            return strcoll(iconv('UTF-8', 'ASCII//TRANSLIT', $a), iconv('UTF-8', 'ASCII//TRANSLIT', $b));
        });

        $this->assertEquals($sortedSports, $sports);
    }

    public function test_all_original_sports_are_included()
    {
        $sports = SportService::getSupportedSports();
        $originalSports = ['tennis', 'golf', 'musculation', 'football', 'basketball'];

        foreach ($originalSports as $sport) {
            $this->assertContains($sport, $sports, "Original sport '{$sport}' should be included in the new list");
        }
    }

    public function test_is_valid_sport_with_valid_sports()
    {
        $validSports = ['tennis', 'golf', 'musculation', 'football', 'basketball', 'natation', 'yoga', 'padel'];

        foreach ($validSports as $sport) {
            $this->assertTrue(SportService::isValidSport($sport), "Sport '{$sport}' should be valid");
        }
    }

    public function test_is_valid_sport_with_invalid_sports()
    {
        $invalidSports = ['invalid-sport', 'soccer', 'swimming', 'tennis-ball', ''];

        foreach ($invalidSports as $sport) {
            $this->assertFalse(SportService::isValidSport($sport), "Sport '{$sport}' should be invalid");
        }
    }

    public function test_get_formatted_sport_name()
    {
        $testCases = [
            'tennis' => 'Tennis',
            'golf' => 'Golf',
            'jiu-jitsu-brésilien' => 'Jiu-jitsu brésilien',
            'marche-nordique' => 'Marche nordique',
            'planche-à-voile' => 'Planche à voile',
            'stand-up-paddle' => 'Stand up paddle',
            'tir-à-l-arc' => 'Tir à l\'arc',
            'ping-pong' => 'Ping-pong'
        ];

        foreach ($testCases as $sport => $expected) {
            $this->assertEquals($expected, SportService::getFormattedSportName($sport));
        }
    }

    public function test_get_validation_rule_returns_comma_separated_string()
    {
        $rule = SportService::getValidationRule();

        $this->assertIsString($rule);
        $this->assertStringContainsString(',', $rule);

        // Vérifier que tous les sports sont présents dans la règle
        $sports = SportService::getSupportedSports();
        foreach ($sports as $sport) {
            $this->assertStringContainsString($sport, $rule, "Sport '{$sport}' should be in validation rule");
        }
    }

    public function test_get_sport_categories_returns_correct_structure()
    {
        $categories = SportService::getSportCategories();

        $this->assertIsArray($categories);
        $this->assertArrayHasKey('sports-de-raquette', $categories);
        $this->assertArrayHasKey('sports-aquatiques', $categories);
        $this->assertArrayHasKey('sports-endurance', $categories);
        $this->assertArrayHasKey('arts-martiaux', $categories);
        $this->assertArrayHasKey('sports-glisse', $categories);
        $this->assertArrayHasKey('sports-collectifs', $categories);
        $this->assertArrayHasKey('sports-bien-etre', $categories);
        $this->assertArrayHasKey('sports-precision', $categories);
        $this->assertArrayHasKey('autres', $categories);
    }

    public function test_get_sport_category_returns_correct_category()
    {
        $testCases = [
            'tennis' => 'sports-de-raquette',
            'natation' => 'sports-aquatiques',
            'course' => 'sports-endurance',
            'boxe' => 'arts-martiaux',
            'ski' => 'sports-glisse',
            'football' => 'sports-collectifs',
            'yoga' => 'sports-bien-etre',
            'golf' => 'sports-precision',
            'musculation' => 'autres'
        ];

        foreach ($testCases as $sport => $expectedCategory) {
            $this->assertEquals($expectedCategory, SportService::getSportCategory($sport));
        }
    }

    public function test_get_sport_category_returns_null_for_invalid_sport()
    {
        $this->assertNull(SportService::getSportCategory('invalid-sport'));
    }

    public function test_all_sports_have_categories()
    {
        $sports = SportService::getSupportedSports();
        $categories = SportService::getSportCategories();

        $allCategorizedSports = [];
        foreach ($categories as $categorySports) {
            $allCategorizedSports = array_merge($allCategorizedSports, $categorySports);
        }

        foreach ($sports as $sport) {
            $this->assertContains($sport, $allCategorizedSports, "Sport '{$sport}' should have a category");
        }
    }
}
