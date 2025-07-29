<?php

// Test pour vérifier le tri des sessions par date
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

echo "=== TEST TRI DES SESSIONS PAR DATE ===\n\n";

// 1. Créer un utilisateur
echo "🔐 CRÉATION UTILISATEUR\n";
echo "=======================\n";

$createUserResult = testEndpoint('POST', '/register', [
    'email' => 'user@ordering.com',
    'password' => 'password123',
    'firstname' => 'User',
    'lastname' => 'Ordering',
    'device_name' => 'test-device'
], null, 'Créer un utilisateur');

// 2. Login
$loginResult = testEndpoint('POST', '/login', [
    'email' => 'user@ordering.com',
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
    $tomorrow = date('Y-m-d', strtotime('+1 day'));
    $nextWeek = date('Y-m-d', strtotime('+1 week'));
    $nextMonth = date('Y-m-d', strtotime('+1 month'));

    echo "   Date aujourd'hui: $today\n";
    echo "   Date demain: $tomorrow\n";
    echo "   Date semaine prochaine: $nextWeek\n";
    echo "   Date mois prochain: $nextMonth\n\n";

    // Session de demain (la plus proche)
    $sessionTomorrowResult = testEndpoint('POST', '/sessions', [
        'sport' => 'tennis',
        'date' => $tomorrow,
        'time' => '18:00',
        'location' => 'Tennis Club - Demain (Plus proche)'
    ], $token, 'Créer session demain');

    // Session de la semaine prochaine
    $sessionNextWeekResult = testEndpoint('POST', '/sessions', [
        'sport' => 'tennis',
        'date' => $nextWeek,
        'time' => '18:00',
        'location' => 'Tennis Club - Semaine prochaine'
    ], $token, 'Créer session semaine prochaine');

    // Session du mois prochain (la plus éloignée)
    $sessionNextMonthResult = testEndpoint('POST', '/sessions', [
        'sport' => 'tennis',
        'date' => $nextMonth,
        'time' => '18:00',
        'location' => 'Tennis Club - Mois prochain (Plus éloignée)'
    ], $token, 'Créer session mois prochain');

    // 4. Tester l'endpoint GET /sessions (tri par date croissante)
    echo "\n📋 TEST GET /SESSIONS (TRI CROISSANT)\n";
    echo "=====================================\n";

    $sessionsResult = testEndpoint('GET', '/sessions', null, $token, 'Récupérer toutes les sessions');

    if ($sessionsResult['code'] === 200) {
        $sessionsData = json_decode($sessionsResult['response'], true);
        if (isset($sessionsData['data'])) {
            $sessions = $sessionsData['data'];
            echo "   Nombre total de sessions: " . count($sessions) . "\n";

            $dates = [];
            foreach ($sessions as $session) {
                $date = $session['date'];
                $location = $session['location'];
                $dates[] = $date;
                echo "   - Session: $date - $location\n";
            }

            // Vérifier que les dates sont en ordre croissant
            $isOrdered = true;
            for ($i = 1; $i < count($dates); $i++) {
                if ($dates[$i] < $dates[$i-1]) {
                    $isOrdered = false;
                    break;
                }
            }

            echo "\n📊 ANALYSE TRI:\n";
            echo "   " . ($isOrdered ? "✅" : "❌") . " Sessions triées par date croissante (plus proche en premier): " . ($isOrdered ? "OUI" : "NON") . "\n";
        }
    }

    // 5. Tester l'endpoint GET /sessions/my-participations
    echo "\n📋 TEST GET /SESSIONS/MY-PARTICIPATIONS\n";
    echo "=======================================\n";

    $participationsResult = testEndpoint('GET', '/sessions/my-participations', null, $token, 'Récupérer mes participations');

    if ($participationsResult['code'] === 200) {
        $participationsData = json_decode($participationsResult['response'], true);
        if (isset($participationsData['data']['data'])) {
            $participations = $participationsData['data']['data'];
            echo "   Nombre total de participations: " . count($participations) . "\n";

            $dates = [];
            foreach ($participations as $session) {
                $date = $session['date'];
                $location = $session['location'];
                $dates[] = $date;
                echo "   - Participation: $date - $location\n";
            }

            // Vérifier que les dates sont en ordre croissant
            $isOrdered = true;
            for ($i = 1; $i < count($dates); $i++) {
                if ($dates[$i] < $dates[$i-1]) {
                    $isOrdered = false;
                    break;
                }
            }

            echo "\n📊 ANALYSE TRI:\n";
            echo "   " . ($isOrdered ? "✅" : "❌") . " Participations triées par date croissante: " . ($isOrdered ? "OUI" : "NON") . "\n";
        }
    }

    // 6. Tester l'endpoint GET /sessions/my-created
    echo "\n📋 TEST GET /SESSIONS/MY-CREATED\n";
    echo "=================================\n";

    $createdResult = testEndpoint('GET', '/sessions/my-created', null, $token, 'Récupérer mes sessions créées');

    if ($createdResult['code'] === 200) {
        $createdData = json_decode($createdResult['response'], true);
        if (isset($createdData['data']['data'])) {
            $created = $createdData['data']['data'];
            echo "   Nombre total de sessions créées: " . count($created) . "\n";

            $dates = [];
            foreach ($created as $session) {
                $date = $session['date'];
                $location = $session['location'];
                $dates[] = $date;
                echo "   - Créée: $date - $location\n";
            }

            // Vérifier que les dates sont en ordre croissant
            $isOrdered = true;
            for ($i = 1; $i < count($dates); $i++) {
                if ($dates[$i] < $dates[$i-1]) {
                    $isOrdered = false;
                    break;
                }
            }

            echo "\n📊 ANALYSE TRI:\n";
            echo "   " . ($isOrdered ? "✅" : "❌") . " Sessions créées triées par date croissante: " . ($isOrdered ? "OUI" : "NON") . "\n";
        }
    }

    echo "\n📝 RÈGLES DE TRI:\n";
    echo "   - Sessions futures/actuelles: Tri croissant (plus proche en premier)\n";
    echo "   - Historique: Tri décroissant (plus récent en premier)\n";

} else {
    echo "   ❌ Erreur lors de la connexion\n";
}

echo "\n=== TEST TERMINÉ ===\n";
