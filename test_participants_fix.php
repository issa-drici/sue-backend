<?php

// Test spécifique pour vérifier que l'organisateur apparaît dans les participants
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

echo "=== TEST PARTICIPANTS AVEC ORGANISATEUR ===\n\n";

// 1. Créer un utilisateur de test
echo "🔐 CRÉATION UTILISATEUR DE TEST\n";
echo "===============================\n";

$createUserResult = testEndpoint('POST', '/register', [
    'email' => 'participanttest@example.com',
    'password' => 'password123',
    'firstname' => 'Participant',
    'lastname' => 'Test',
    'device_name' => 'test-device'
], null, 'Créer un utilisateur de test');

// 2. Login avec l'utilisateur
$loginResult = testEndpoint('POST', '/login', [
    'email' => 'participanttest@example.com',
    'password' => 'password123',
    'device_name' => 'test-device'
], null, 'Se connecter avec l\'utilisateur de test');

// Extraire le token
$loginData = json_decode($loginResult['response'], true);
if (isset($loginData['token'])) {
    $token = $loginData['token'];
    $userId = $loginData['user']['id'];
    echo "   Token obtenu: " . substr($token, 0, 20) . "...\n";
    echo "   User ID: $userId\n\n";
}

// 3. Créer une session
echo "🏃 CRÉATION DE SESSION\n";
echo "=====================\n";

$createSessionResult = testEndpoint('POST', '/sessions', [
    'sport' => 'tennis',
    'date' => date('Y-m-d', strtotime('+1 week')),
    'time' => '18:00',
    'location' => 'Tennis Club de Paris'
], $token, 'Créer une session de test');

// Extraire l'ID de session et vérifier les participants
$sessionData = json_decode($createSessionResult['response'], true);
if (isset($sessionData['data']['id'])) {
    $sessionId = $sessionData['data']['id'];
    $participants = $sessionData['data']['participants'];
    $organizer = $sessionData['data']['organizer'];

    echo "   Session créée avec ID: $sessionId\n";
    echo "   Organisateur: " . $organizer['fullName'] . " (ID: " . $organizer['id'] . ")\n";
    echo "   Nombre de participants: " . count($participants) . "\n";

    // Vérifier que l'organisateur est dans les participants
    $organizerInParticipants = false;
    foreach ($participants as $participant) {
        echo "   - Participant: " . $participant['fullName'] . " (ID: " . $participant['id'] . ", Status: " . $participant['status'] . ")\n";
        if ($participant['id'] === $organizer['id']) {
            $organizerInParticipants = true;
        }
    }

    if ($organizerInParticipants) {
        echo "   ✅ L'organisateur est bien dans la liste des participants\n";
    } else {
        echo "   ❌ L'organisateur n'est PAS dans la liste des participants\n";
    }
    echo "\n";
}

// 4. Tester l'endpoint GET /sessions/{id} pour vérifier les participants
echo "📋 TEST ENDPOINT GET /SESSIONS/{ID}\n";
echo "===================================\n";

if (isset($sessionId)) {
    $getSessionResult = testEndpoint('GET', "/sessions/$sessionId", null, $token, 'Obtenir la session par ID');

    $sessionDetail = json_decode($getSessionResult['response'], true);
    if (isset($sessionDetail['data'])) {
        $participants = $sessionDetail['data']['participants'];
        $organizer = $sessionDetail['data']['organizer'];

        echo "   Organisateur: " . $organizer['fullName'] . " (ID: " . $organizer['id'] . ")\n";
        echo "   Nombre de participants: " . count($participants) . "\n";

        // Vérifier que l'organisateur est dans les participants
        $organizerInParticipants = false;
        foreach ($participants as $participant) {
            echo "   - Participant: " . $participant['fullName'] . " (ID: " . $participant['id'] . ", Status: " . $participant['status'] . ")\n";
            if ($participant['id'] === $organizer['id']) {
                $organizerInParticipants = true;
            }
        }

        if ($organizerInParticipants) {
            echo "   ✅ L'organisateur est bien dans la liste des participants\n";
        } else {
            echo "   ❌ L'organisateur n'est PAS dans la liste des participants\n";
        }
    }
}

echo "\n=== FIN DU TEST ===\n";
