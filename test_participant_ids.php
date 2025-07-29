<?php

// Test pour vérifier que les participantIds fonctionnent
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

echo "=== TEST PARTICIPANT IDS ===\n\n";

// 1. Créer plusieurs utilisateurs
echo "🔐 CRÉATION UTILISATEURS\n";
echo "========================\n";

$users = [];
$emails = ['organizer@test.com', 'participant1@test.com', 'participant2@test.com'];

foreach ($emails as $index => $email) {
    $name = $index === 0 ? 'Organisateur' : 'Participant' . $index;

    $createResult = testEndpoint('POST', '/register', [
        'email' => $email,
        'password' => 'password123',
        'firstname' => $name,
        'lastname' => 'Test',
        'device_name' => 'test-device'
    ], null, "Créer $name");

    $loginResult = testEndpoint('POST', '/login', [
        'email' => $email,
        'password' => 'password123',
        'device_name' => 'test-device'
    ], null, "Login $name");

    $loginData = json_decode($loginResult['response'], true);
    if (isset($loginData['token'])) {
        $users[] = [
            'email' => $email,
            'name' => $name,
            'token' => $loginData['token'],
            'id' => $loginData['user']['id']
        ];
        echo "   $name ID: " . $loginData['user']['id'] . "\n";
    }
}

echo "\n";

// 2. Créer une session avec participantIds
echo "🏃 CRÉATION SESSION AVEC PARTICIPANT IDS\n";
echo "========================================\n";

$organizer = $users[0];
$participantIds = [$users[1]['id'], $users[2]['id']];

$createSessionResult = testEndpoint('POST', '/sessions', [
    'sport' => 'tennis',
    'date' => date('Y-m-d', strtotime('+1 week')),
    'time' => '18:00',
    'location' => 'Tennis Club de Paris',
    'maxParticipants' => 5,
    'participantIds' => $participantIds
], $organizer['token'], 'Créer une session avec participantIds');

// Vérifier la réponse
$sessionData = json_decode($createSessionResult['response'], true);
if (isset($sessionData['data']['id'])) {
    $sessionId = $sessionData['data']['id'];
    $participants = $sessionData['data']['participants'];

    echo "   ✅ Session créée avec succès!\n";
    echo "   Session ID: $sessionId\n";
    echo "   Nombre de participants: " . count($participants) . "\n";

    foreach ($participants as $participant) {
        echo "   - " . $participant['fullName'] . " (Status: " . $participant['status'] . ")\n";
    }

    // 3. Vérifier les détails de la session
    echo "\n🔍 VÉRIFICATION DÉTAILS SESSION\n";
    echo "===============================\n";

    $sessionDetailResult = testEndpoint('GET', "/sessions/$sessionId", null, $organizer['token'], 'Récupérer les détails de la session');

    $sessionDetail = json_decode($sessionDetailResult['response'], true);
    if (isset($sessionDetail['data']['participants'])) {
        $detailParticipants = $sessionDetail['data']['participants'];
        echo "   ✅ Nombre de participants dans les détails: " . count($detailParticipants) . "\n";

        foreach ($detailParticipants as $participant) {
            echo "   - " . $participant['fullName'] . " (Status: " . $participant['status'] . ")\n";
        }
    }

    // 4. Vérifier que les participants peuvent voir la session
    echo "\n👥 VÉRIFICATION ACCÈS PARTICIPANTS\n";
    echo "==================================\n";

    foreach ($users as $user) {
        $mySessionsResult = testEndpoint('GET', '/sessions/my-participations', null, $user['token'], "Vérifier participations de " . $user['name']);

        $mySessionsData = json_decode($mySessionsResult['response'], true);
        if (isset($mySessionsData['data']['data'])) {
            $sessions = $mySessionsData['data']['data'];
            $hasSession = false;
            foreach ($sessions as $session) {
                if ($session['id'] === $sessionId) {
                    $hasSession = true;
                    break;
                }
            }
            echo "   " . ($hasSession ? "✅" : "❌") . " " . $user['name'] . " peut voir la session\n";
        }
    }

} else {
    echo "   ❌ Erreur lors de la création de la session\n";
    echo "   Réponse: " . $createSessionResult['response'] . "\n";
}

echo "\n=== TEST TERMINÉ ===\n";
