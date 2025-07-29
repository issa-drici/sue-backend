<?php

// Test pour vérifier que l'endpoint d'historique ne retourne que les sessions passées
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

echo "=== TEST ENDPOINT HISTORIQUE ===\n\n";

// 1. Créer un utilisateur
echo "🔐 CRÉATION UTILISATEUR\n";
echo "=======================\n";

$createUserResult = testEndpoint('POST', '/register', [
    'email' => 'user@history.com',
    'password' => 'password123',
    'firstname' => 'User',
    'lastname' => 'History',
    'device_name' => 'test-device'
], null, 'Créer un utilisateur');

// 2. Login
$loginResult = testEndpoint('POST', '/login', [
    'email' => 'user@history.com',
    'password' => 'password123',
    'device_name' => 'test-device'
], null, 'Se connecter');

$userData = json_decode($loginResult['response'], true);
if (isset($userData['token'])) {
    $token = $userData['token'];
    $userId = $userData['user']['id'];

    echo "   Token obtenu: " . substr($token, 0, 20) . "...\n";
    echo "   User ID: $userId\n\n";

    // 3. Créer des sessions avec différentes dates
    echo "🏃 CRÉATION DE SESSIONS\n";
    echo "=======================\n";

    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    $tomorrow = date('Y-m-d', strtotime('+1 day'));

    echo "   Date aujourd'hui: $today\n";
    echo "   Date hier: $yesterday\n";
    echo "   Date demain: $tomorrow\n\n";

    // Session d'hier (devrait apparaître dans l'historique)
    $sessionYesterdayResult = testEndpoint('POST', '/sessions', [
        'sport' => 'tennis',
        'date' => $yesterday,
        'time' => '18:00',
        'location' => 'Tennis Club - Hier (Historique)'
    ], $token, 'Créer session hier');

    // Session d'aujourd'hui (ne devrait PAS apparaître dans l'historique)
    $sessionTodayResult = testEndpoint('POST', '/sessions', [
        'sport' => 'tennis',
        'date' => $today,
        'time' => '18:00',
        'location' => 'Tennis Club - Aujourd\'hui (Pas historique)'
    ], $token, 'Créer session aujourd\'hui');

    // Session de demain (ne devrait PAS apparaître dans l'historique)
    $sessionTomorrowResult = testEndpoint('POST', '/sessions', [
        'sport' => 'tennis',
        'date' => $tomorrow,
        'time' => '18:00',
        'location' => 'Tennis Club - Demain (Pas historique)'
    ], $token, 'Créer session demain');

    // 4. Tester l'endpoint GET /sessions/history
    echo "\n📋 TEST ENDPOINT GET /SESSIONS/HISTORY\n";
    echo "=======================================\n";

    $historyResult = testEndpoint('GET', '/sessions/history', null, $token, 'Récupérer l\'historique');

    if ($historyResult['code'] === 200) {
        $historyData = json_decode($historyResult['response'], true);
        if (isset($historyData['data'])) {
            $history = $historyData['data'];
            echo "   Nombre total de sessions dans l'historique: " . count($history) . "\n";

            $hasYesterday = false;
            $hasToday = false;
            $hasTomorrow = false;

            foreach ($history as $session) {
                $date = $session['date'];
                $location = $session['location'];
                echo "   - Historique: $date - $location\n";

                if ($date === $yesterday) {
                    $hasYesterday = true;
                } elseif ($date === $today) {
                    $hasToday = true;
                } elseif ($date === $tomorrow) {
                    $hasTomorrow = true;
                }
            }

            echo "\n📊 ANALYSE:\n";
            echo "   " . ($hasYesterday ? "✅" : "❌") . " Session d'hier dans l'historique: " . ($hasYesterday ? "OUI" : "NON") . "\n";
            echo "   " . ($hasToday ? "❌" : "✅") . " Session d'aujourd'hui dans l'historique: " . ($hasToday ? "OUI (ERREUR)" : "NON (CORRECT)") . "\n";
            echo "   " . ($hasTomorrow ? "❌" : "✅") . " Session de demain dans l'historique: " . ($hasTomorrow ? "OUI (ERREUR)" : "NON (CORRECT)") . "\n";
        }
    }

    // 5. Comparer avec l'endpoint GET /sessions normal
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

    // 6. Tester avec filtre sport
    echo "\n📋 TEST HISTORIQUE AVEC FILTRE SPORT\n";
    echo "=====================================\n";

    $historyTennisResult = testEndpoint('GET', '/sessions/history?sport=tennis', null, $token, 'Récupérer l\'historique tennis');

    if ($historyTennisResult['code'] === 200) {
        $historyTennisData = json_decode($historyTennisResult['response'], true);
        if (isset($historyTennisData['data'])) {
            $historyTennis = $historyTennisData['data'];
            echo "   Nombre de sessions tennis dans l'historique: " . count($historyTennis) . "\n";

            foreach ($historyTennis as $session) {
                $date = $session['date'];
                $sport = $session['sport'];
                $location = $session['location'];
                echo "   - Historique tennis: $date - $sport - $location\n";
            }
        }
    }

} else {
    echo "   ❌ Erreur lors de la connexion\n";
}

echo "\n=== TEST TERMINÉ ===\n";
