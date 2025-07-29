<?php

// Test pour vérifier l'historique avec des sessions passées créées directement en base
require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

$baseUrl = 'http://localhost:8000/api';

function testEndpoint($method, $endpoint, $data = null, $token = null, $description = '') {
    global $baseUrl;

    $url = $baseUrl . $endpoint;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

    if ($data) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json'
        ]);
    }

    if ($token) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
            'Accept: application/json'
        ]);
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $status = ($httpCode >= 200 && $httpCode < 300) ? '✅' : '❌';
    echo "$status $method $endpoint - Code: $httpCode ($description)\n";

    return ['code' => $httpCode, 'response' => $response];
}

echo "=== TEST HISTORIQUE AVEC SESSIONS PASSÉES ===\n\n";

// 1. Créer un utilisateur
echo "🔐 CRÉATION UTILISATEUR\n";
echo "=======================\n";

$createUserResult = testEndpoint('POST', '/register', [
    'email' => 'user@past.com',
    'password' => 'password123',
    'firstname' => 'User',
    'lastname' => 'Past',
    'device_name' => 'test-device'
], null, 'Créer un utilisateur');

// 2. Login
$loginResult = testEndpoint('POST', '/login', [
    'email' => 'user@past.com',
    'password' => 'password123',
    'device_name' => 'test-device'
], null, 'Se connecter');

$userData = json_decode($loginResult['response'], true);
if (isset($userData['token'])) {
    $token = $userData['token'];
    $userId = $userData['user']['id'];

    echo "   Token obtenu: " . substr($token, 0, 20) . "...\n";
    echo "   User ID: $userId\n\n";

    // 3. Créer une session normale (aujourd'hui)
    echo "🏃 CRÉATION SESSION NORMALE\n";
    echo "===========================\n";

    $today = date('Y-m-d');
    $sessionTodayResult = testEndpoint('POST', '/sessions', [
        'sport' => 'tennis',
        'date' => $today,
        'time' => '18:00',
        'location' => 'Tennis Club - Aujourd\'hui'
    ], $token, 'Créer session aujourd\'hui');

    $sessionData = json_decode($sessionTodayResult['response'], true);
    if (isset($sessionData['data']['id'])) {
        $sessionId = $sessionData['data']['id'];
        echo "   ✅ Session créée avec ID: $sessionId\n\n";

        // 4. Modifier la date de la session pour la rendre passée
        echo "⏰ MODIFICATION DATE SESSION\n";
        echo "============================\n";

        $yesterday = date('Y-m-d', strtotime('-1 day'));

        DB::table('sport_sessions')
            ->where('id', $sessionId)
            ->update(['date' => $yesterday]);

        echo "   ✅ Date de session modifiée à: $yesterday\n\n";

        // 5. Tester l'endpoint GET /sessions/history
        echo "📋 TEST ENDPOINT GET /SESSIONS/HISTORY\n";
        echo "=======================================\n";

        $historyResult = testEndpoint('GET', '/sessions/history', null, $token, 'Récupérer l\'historique');

        if ($historyResult['code'] === 200) {
            $historyData = json_decode($historyResult['response'], true);
            if (isset($historyData['data'])) {
                $history = $historyData['data'];
                echo "   Nombre total de sessions dans l'historique: " . count($history) . "\n";

                $hasPastSession = false;
                foreach ($history as $session) {
                    $date = $session['date'];
                    $location = $session['location'];
                    echo "   - Historique: $date - $location\n";

                    if ($date === $yesterday) {
                        $hasPastSession = true;
                    }
                }

                echo "\n📊 ANALYSE:\n";
                echo "   " . ($hasPastSession ? "✅" : "❌") . " Session passée dans l'historique: " . ($hasPastSession ? "OUI" : "NON") . "\n";
            }
        }

        // 6. Comparer avec l'endpoint GET /sessions normal
        echo "\n📋 COMPARAISON AVEC GET /SESSIONS\n";
        echo "==================================\n";

        $sessionsResult = testEndpoint('GET', '/sessions', null, $token, 'Récupérer toutes les sessions');

        if ($sessionsResult['code'] === 200) {
            $sessionsData = json_decode($sessionsResult['response'], true);
            if (isset($sessionsData['data'])) {
                $sessions = $sessionsData['data'];
                echo "   Nombre total de sessions: " . count($sessions) . "\n";

                foreach ($sessions as $session) {
                    $date = $session['date'];
                    $location = $session['location'];
                    echo "   - Session: $date - $location\n";
                }
            }
        }

        // 7. Créer une autre session et la laisser à aujourd'hui
        echo "\n🏃 CRÉATION SESSION AUJOURD'HUI\n";
        echo "===============================\n";

        $sessionToday2Result = testEndpoint('POST', '/sessions', [
            'sport' => 'tennis',
            'date' => $today,
            'time' => '19:00',
            'location' => 'Tennis Club - Aujourd\'hui (2)'
        ], $token, 'Créer deuxième session aujourd\'hui');

        // 8. Tester à nouveau l'historique
        echo "\n📋 TEST HISTORIQUE FINAL\n";
        echo "=========================\n";

        $historyFinalResult = testEndpoint('GET', '/sessions/history', null, $token, 'Récupérer l\'historique final');

        if ($historyFinalResult['code'] === 200) {
            $historyFinalData = json_decode($historyFinalResult['response'], true);
            if (isset($historyFinalData['data'])) {
                $historyFinal = $historyFinalData['data'];
                echo "   Nombre total de sessions dans l'historique: " . count($historyFinal) . "\n";

                $hasPastSession = false;
                $hasTodaySession = false;
                foreach ($historyFinal as $session) {
                    $date = $session['date'];
                    $location = $session['location'];
                    echo "   - Historique: $date - $location\n";

                    if ($date === $yesterday) {
                        $hasPastSession = true;
                    } elseif ($date === $today) {
                        $hasTodaySession = true;
                    }
                }

                echo "\n📊 ANALYSE FINALE:\n";
                echo "   " . ($hasPastSession ? "✅" : "❌") . " Session passée dans l'historique: " . ($hasPastSession ? "OUI" : "NON") . "\n";
                echo "   " . ($hasTodaySession ? "❌" : "✅") . " Session d'aujourd'hui dans l'historique: " . ($hasTodaySession ? "OUI (ERREUR)" : "NON (CORRECT)") . "\n";
            }
        }

    } else {
        echo "   ❌ Erreur lors de la création de la session\n";
        echo "   Réponse: " . $sessionTodayResult['response'] . "\n";
    }
} else {
    echo "   ❌ Erreur lors de la connexion\n";
}

echo "\n=== TEST TERMINÉ ===\n";
