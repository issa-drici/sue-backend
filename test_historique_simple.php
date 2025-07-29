<?php

// Test simple pour vérifier l'historique
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

echo "=== TEST HISTORIQUE SIMPLE ===\n\n";

// 1. Créer un utilisateur
echo "🔐 CRÉATION UTILISATEUR\n";
echo "=======================\n";

$createUserResult = testEndpoint('POST', '/register', [
    'email' => 'user@simple.com',
    'password' => 'password123',
    'firstname' => 'User',
    'lastname' => 'Simple',
    'device_name' => 'test-device'
], null, 'Créer un utilisateur');

// 2. Login
$loginResult = testEndpoint('POST', '/login', [
    'email' => 'user@simple.com',
    'password' => 'password123',
    'device_name' => 'test-device'
], null, 'Se connecter');

$userData = json_decode($loginResult['response'], true);
if (isset($userData['token'])) {
    $token = $userData['token'];
    $userId = $userData['user']['id'];

    echo "   Token obtenu: " . substr($token, 0, 20) . "...\n";
    echo "   User ID: $userId\n\n";

    // 3. Créer une session aujourd'hui
    echo "🏃 CRÉATION SESSION AUJOURD'HUI\n";
    echo "===============================\n";

    $today = date('Y-m-d');
    $sessionTodayResult = testEndpoint('POST', '/sessions', [
        'sport' => 'tennis',
        'date' => $today,
        'time' => '18:00',
        'location' => 'Tennis Club - Aujourd\'hui'
    ], $token, 'Créer session aujourd\'hui');

    // 4. Tester l'endpoint GET /sessions/history
    echo "\n📋 TEST ENDPOINT GET /SESSIONS/HISTORY\n";
    echo "=======================================\n";

    $historyResult = testEndpoint('GET', '/sessions/history', null, $token, 'Récupérer l\'historique');

    if ($historyResult['code'] === 200) {
        $historyData = json_decode($historyResult['response'], true);
        if (isset($historyData['data'])) {
            $history = $historyData['data'];
            echo "   Nombre total de sessions dans l'historique: " . count($history) . "\n";

            $hasTodaySession = false;
            foreach ($history as $session) {
                $date = $session['date'];
                $location = $session['location'];
                echo "   - Historique: $date - $location\n";

                if ($date === $today) {
                    $hasTodaySession = true;
                }
            }

            echo "\n📊 ANALYSE:\n";
            echo "   " . ($hasTodaySession ? "❌" : "✅") . " Session d'aujourd'hui dans l'historique: " . ($hasTodaySession ? "OUI (ERREUR)" : "NON (CORRECT)") . "\n";
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

    echo "\n📝 CONCLUSION:\n";
    echo "   Si la session d'aujourd'hui apparaît dans l'historique, il y a un problème.\n";
    echo "   L'historique ne devrait contenir que les sessions passées.\n";

} else {
    echo "   ❌ Erreur lors de la connexion\n";
}

echo "\n=== TEST TERMINÉ ===\n";
