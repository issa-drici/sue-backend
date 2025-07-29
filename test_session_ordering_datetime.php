<?php

// Test pour vérifier le tri des sessions par date et heure
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

echo "=== TEST TRI DES SESSIONS PAR DATE ET HEURE ===\n\n";

// 1. Créer un utilisateur
echo "🔐 CRÉATION UTILISATEUR\n";
echo "=======================\n";

$createUserResult = testEndpoint('POST', '/register', [
    'email' => 'user@datetime.com',
    'password' => 'password123',
    'firstname' => 'User',
    'lastname' => 'DateTime',
    'device_name' => 'test-device'
], null, 'Créer un utilisateur');

// 2. Login
$loginResult = testEndpoint('POST', '/login', [
    'email' => 'user@datetime.com',
    'password' => 'password123',
    'device_name' => 'test-device'
], null, 'Se connecter');

$userData = json_decode($loginResult['response'], true);
if (isset($userData['token'])) {
    $token = $userData['token'];
    $userId = $userData['user']['id'];

    echo "   Token obtenu: " . substr($token, 0, 20) . "...\n";
    echo "   User ID: $userId\n\n";

    // 3. Créer des sessions avec la même date mais des heures différentes
    echo "🏃 CRÉATION DE SESSIONS MÊME DATE\n";
    echo "==================================\n";

    $tomorrow = date('Y-m-d', strtotime('+1 day'));

    echo "   Date: $tomorrow\n\n";

    // Session de demain à 20:00 (plus tard)
    $sessionEveningResult = testEndpoint('POST', '/sessions', [
        'sport' => 'tennis',
        'date' => $tomorrow,
        'time' => '20:00',
        'location' => 'Tennis Club - Soir (20:00)'
    ], $token, 'Créer session soir');

    // Session de demain à 14:00 (après-midi)
    $sessionAfternoonResult = testEndpoint('POST', '/sessions', [
        'sport' => 'tennis',
        'date' => $tomorrow,
        'time' => '14:00',
        'location' => 'Tennis Club - Après-midi (14:00)'
    ], $token, 'Créer session après-midi');

    // Session de demain à 09:00 (matin)
    $sessionMorningResult = testEndpoint('POST', '/sessions', [
        'sport' => 'tennis',
        'date' => $tomorrow,
        'time' => '09:00',
        'location' => 'Tennis Club - Matin (09:00)'
    ], $token, 'Créer session matin');

    // 4. Créer des sessions avec des dates différentes
    echo "\n🏃 CRÉATION DE SESSIONS DATES DIFFÉRENTES\n";
    echo "==========================================\n";

    $nextWeek = date('Y-m-d', strtotime('+1 week'));
    $nextMonth = date('Y-m-d', strtotime('+1 month'));

    // Session de la semaine prochaine à 18:00
    $sessionNextWeekResult = testEndpoint('POST', '/sessions', [
        'sport' => 'tennis',
        'date' => $nextWeek,
        'time' => '18:00',
        'location' => 'Tennis Club - Semaine prochaine (18:00)'
    ], $token, 'Créer session semaine prochaine');

    // Session du mois prochain à 10:00
    $sessionNextMonthResult = testEndpoint('POST', '/sessions', [
        'sport' => 'tennis',
        'date' => $nextMonth,
        'time' => '10:00',
        'location' => 'Tennis Club - Mois prochain (10:00)'
    ], $token, 'Créer session mois prochain');

    // 5. Tester l'endpoint GET /sessions (tri par date et heure croissante)
    echo "\n📋 TEST GET /SESSIONS (TRI DATE + HEURE)\n";
    echo "=========================================\n";

    $sessionsResult = testEndpoint('GET', '/sessions', null, $token, 'Récupérer toutes les sessions');

    if ($sessionsResult['code'] === 200) {
        $sessionsData = json_decode($sessionsResult['response'], true);
        if (isset($sessionsData['data'])) {
            $sessions = $sessionsData['data'];
            echo "   Nombre total de sessions: " . count($sessions) . "\n";

            $expectedOrder = [
                ['date' => $tomorrow, 'time' => '09:00', 'location' => 'Tennis Club - Matin (09:00)'],
                ['date' => $tomorrow, 'time' => '14:00', 'location' => 'Tennis Club - Après-midi (14:00)'],
                ['date' => $tomorrow, 'time' => '20:00', 'location' => 'Tennis Club - Soir (20:00)'],
                ['date' => $nextWeek, 'time' => '18:00', 'location' => 'Tennis Club - Semaine prochaine (18:00)'],
                ['date' => $nextMonth, 'time' => '10:00', 'location' => 'Tennis Club - Mois prochain (10:00)']
            ];

            $isCorrectOrder = true;
            foreach ($sessions as $index => $session) {
                $date = $session['date'];
                $time = $session['time'];
                $location = $session['location'];
                echo "   - Session $index: $date $time - $location\n";

                if ($index < count($expectedOrder)) {
                    $expected = $expectedOrder[$index];
                    if ($date !== $expected['date'] || $time !== $expected['time']) {
                        $isCorrectOrder = false;
                    }
                }
            }

            echo "\n📊 ANALYSE TRI:\n";
            echo "   " . ($isCorrectOrder ? "✅" : "❌") . " Sessions triées par date puis heure croissante: " . ($isCorrectOrder ? "OUI" : "NON") . "\n";

            if ($isCorrectOrder) {
                echo "   ✅ Ordre attendu respecté:\n";
                echo "      1. $tomorrow 09:00 (matin)\n";
                echo "      2. $tomorrow 14:00 (après-midi)\n";
                echo "      3. $tomorrow 20:00 (soir)\n";
                echo "      4. $nextWeek 18:00 (semaine prochaine)\n";
                echo "      5. $nextMonth 10:00 (mois prochain)\n";
            }
        }
    }

    // 6. Tester l'endpoint GET /sessions/my-participations
    echo "\n📋 TEST GET /SESSIONS/MY-PARTICIPATIONS\n";
    echo "=======================================\n";

    $participationsResult = testEndpoint('GET', '/sessions/my-participations', null, $token, 'Récupérer mes participations');

    if ($participationsResult['code'] === 200) {
        $participationsData = json_decode($participationsResult['response'], true);
        if (isset($participationsData['data']['data'])) {
            $participations = $participationsData['data']['data'];
            echo "   Nombre total de participations: " . count($participations) . "\n";

            foreach ($participations as $index => $session) {
                $date = $session['date'];
                $time = $session['time'];
                $location = $session['location'];
                echo "   - Participation $index: $date $time - $location\n";
            }
        }
    }

    echo "\n📝 RÈGLES DE TRI:\n";
    echo "   - Sessions futures/actuelles: Tri par date croissante puis heure croissante\n";
    echo "   - Historique: Tri par date décroissante puis heure décroissante\n";
    echo "   - Exemple: 2025-07-23 09:00 → 2025-07-23 14:00 → 2025-07-23 20:00 → 2025-07-29 18:00\n";

} else {
    echo "   ❌ Erreur lors de la connexion\n";
}

echo "\n=== TEST TERMINÉ ===\n";
