<?php

// Test pour vérifier le comportement de l'endpoint d'historique
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
    'email' => 'user@historique.com',
    'password' => 'password123',
    'firstname' => 'User',
    'lastname' => 'Historique',
    'device_name' => 'test-device'
], null, 'Créer un utilisateur');

// 2. Login
$loginResult = testEndpoint('POST', '/login', [
    'email' => 'user@historique.com',
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

    // Session d'hier
    $sessionYesterdayResult = testEndpoint('POST', '/sessions', [
        'sport' => 'tennis',
        'date' => $yesterday,
        'time' => '18:00',
        'location' => 'Tennis Club - Hier'
    ], $token, 'Créer session hier');

    // Session d'aujourd'hui
    $sessionTodayResult = testEndpoint('POST', '/sessions', [
        'sport' => 'tennis',
        'date' => $today,
        'time' => '18:00',
        'location' => 'Tennis Club - Aujourd\'hui'
    ], $token, 'Créer session aujourd\'hui');

    // Session de demain
    $sessionTomorrowResult = testEndpoint('POST', '/sessions', [
        'sport' => 'tennis',
        'date' => $tomorrow,
        'time' => '18:00',
        'location' => 'Tennis Club - Demain'
    ], $token, 'Créer session demain');

    // 4. Tester l'endpoint GET /sessions (historique potentiel)
    echo "\n📋 TEST ENDPOINT GET /SESSIONS\n";
    echo "===============================\n";

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

    // 5. Tester l'endpoint GET /sessions/my-participations
    echo "\n📋 TEST ENDPOINT GET /SESSIONS/MY-PARTICIPATIONS\n";
    echo "=================================================\n";

    $participationsResult = testEndpoint('GET', '/sessions/my-participations', null, $token, 'Récupérer mes participations');

    if ($participationsResult['code'] === 200) {
        $participationsData = json_decode($participationsResult['response'], true);
        if (isset($participationsData['data']['data'])) {
            $participations = $participationsData['data']['data'];
            echo "   Nombre total de participations: " . count($participations) . "\n";

            foreach ($participations as $session) {
                $date = $session['date'];
                $location = $session['location'];
                echo "   - Participation: $date - $location\n";
            }
        }
    }

    // 6. Tester l'endpoint GET /sessions/my-created
    echo "\n📋 TEST ENDPOINT GET /SESSIONS/MY-CREATED\n";
    echo "==========================================\n";

    $createdResult = testEndpoint('GET', '/sessions/my-created', null, $token, 'Récupérer mes sessions créées');

    if ($createdResult['code'] === 200) {
        $createdData = json_decode($createdResult['response'], true);
        if (isset($createdData['data']['data'])) {
            $created = $createdData['data']['data'];
            echo "   Nombre total de sessions créées: " . count($created) . "\n";

            foreach ($created as $session) {
                $date = $session['date'];
                $location = $session['location'];
                echo "   - Créée: $date - $location\n";
            }
        }
    }

    // 7. Tester avec filtre date spécifique
    echo "\n📋 TEST AVEC FILTRE DATE\n";
    echo "=========================\n";

    $sessionsWithFilterResult = testEndpoint('GET', "/sessions?date=$today", null, $token, "Récupérer sessions du $today");

    if ($sessionsWithFilterResult['code'] === 200) {
        $sessionsWithFilterData = json_decode($sessionsWithFilterResult['response'], true);
        if (isset($sessionsWithFilterData['data'])) {
            $sessionsWithFilter = $sessionsWithFilterData['data'];
            echo "   Sessions du $today: " . count($sessionsWithFilter) . "\n";

            foreach ($sessionsWithFilter as $session) {
                $date = $session['date'];
                $location = $session['location'];
                echo "   - Session filtrée: $date - $location\n";
            }
        }
    }

    echo "\n📝 ANALYSE:\n";
    echo "   - GET /sessions: Retourne toutes les sessions de l'utilisateur\n";
    echo "   - GET /sessions/my-participations: Retourne les participations\n";
    echo "   - GET /sessions/my-created: Retourne les sessions créées\n";
    echo "   - Aucun filtre automatique par date passée/future\n";

} else {
    echo "   ❌ Erreur lors de la connexion\n";
}

echo "\n=== TEST TERMINÉ ===\n";
