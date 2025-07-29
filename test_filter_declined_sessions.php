<?php

// Test pour vérifier que les sessions refusées ne sont pas affichées dans les listes
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

echo "=== TEST FILTRE SESSIONS REFUSÉES ===\n\n";

// 1. Créer plusieurs utilisateurs
echo "🔐 CRÉATION UTILISATEURS\n";
echo "========================\n";

$users = [];
$emails = ['organizer@filter.com', 'participant1@filter.com', 'participant2@filter.com'];

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

// 2. Créer une session avec les participants
echo "🏃 CRÉATION DE SESSION AVEC PARTICIPANTS\n";
echo "========================================\n";

$organizer = $users[0];
$participantIds = [$users[1]['id'], $users[2]['id']];

$createSessionResult = testEndpoint('POST', '/sessions', [
    'sport' => 'tennis',
    'date' => date('Y-m-d', strtotime('+1 week')),
    'time' => '18:00',
    'location' => 'Tennis Club de Paris',
    'participantIds' => $participantIds
], $organizer['token'], 'Créer une session avec participants');

$sessionData = json_decode($createSessionResult['response'], true);
if (isset($sessionData['data']['id'])) {
    $sessionId = $sessionData['data']['id'];
    echo "   ✅ Session créée avec ID: $sessionId\n\n";

    // 3. Participant1 accepte l'invitation
    echo "👥 PARTICIPANT1 ACCEPTE L'INVITATION\n";
    echo "====================================\n";

    $respondAcceptResult = testEndpoint('PATCH', "/sessions/$sessionId/respond", [
        'response' => 'accept'
    ], $users[1]['token'], 'Participant1 accepte l\'invitation');

    if ($respondAcceptResult['code'] === 200) {
        echo "   ✅ Participant1 a accepté l'invitation\n";
    }

    // 4. Participant2 refuse l'invitation
    echo "\n👥 PARTICIPANT2 REFUSE L'INVITATION\n";
    echo "===================================\n";

    $respondDeclineResult = testEndpoint('PATCH', "/sessions/$sessionId/respond", [
        'response' => 'decline'
    ], $users[2]['token'], 'Participant2 refuse l\'invitation');

    if ($respondDeclineResult['code'] === 200) {
        echo "   ✅ Participant2 a refusé l'invitation\n";
    }

    // 5. Vérifier les sessions de Participant1 (devrait voir la session)
    echo "\n📋 VÉRIFICATION SESSIONS PARTICIPANT1 (ACCEPTÉ)\n";
    echo "===============================================\n";

    $sessionsParticipant1Result = testEndpoint('GET', '/sessions', null, $users[1]['token'], 'Récupérer les sessions de Participant1');

    $sessionsParticipant1Data = json_decode($sessionsParticipant1Result['response'], true);
    if (isset($sessionsParticipant1Data['data'])) {
        $sessions1 = $sessionsParticipant1Data['data'];
        $hasSession1 = false;
        foreach ($sessions1 as $session) {
            if ($session['id'] === $sessionId) {
                $hasSession1 = true;
                break;
            }
        }
        echo "   " . ($hasSession1 ? "✅" : "❌") . " Participant1 voit la session (devrait voir)\n";
        echo "   Nombre de sessions: " . count($sessions1) . "\n";
    }

    // 6. Vérifier les sessions de Participant2 (ne devrait PAS voir la session)
    echo "\n📋 VÉRIFICATION SESSIONS PARTICIPANT2 (REFUSÉ)\n";
    echo "===============================================\n";

    $sessionsParticipant2Result = testEndpoint('GET', '/sessions', null, $users[2]['token'], 'Récupérer les sessions de Participant2');

    $sessionsParticipant2Data = json_decode($sessionsParticipant2Result['response'], true);
    if (isset($sessionsParticipant2Data['data'])) {
        $sessions2 = $sessionsParticipant2Data['data'];
        $hasSession2 = false;
        foreach ($sessions2 as $session) {
            if ($session['id'] === $sessionId) {
                $hasSession2 = true;
                break;
            }
        }
        echo "   " . ($hasSession2 ? "❌" : "✅") . " Participant2 ne voit pas la session (devrait ne pas voir)\n";
        echo "   Nombre de sessions: " . count($sessions2) . "\n";
    }

    // 7. Vérifier les participations de Participant1
    echo "\n📋 VÉRIFICATION PARTICIPATIONS PARTICIPANT1\n";
    echo "===========================================\n";

    $participationsParticipant1Result = testEndpoint('GET', '/sessions/my-participations', null, $users[1]['token'], 'Récupérer les participations de Participant1');

    $participationsParticipant1Data = json_decode($participationsParticipant1Result['response'], true);
    if (isset($participationsParticipant1Data['data']['data'])) {
        $participations1 = $participationsParticipant1Data['data']['data'];
        $hasParticipation1 = false;
        foreach ($participations1 as $session) {
            if ($session['id'] === $sessionId) {
                $hasParticipation1 = true;
                break;
            }
        }
        echo "   " . ($hasParticipation1 ? "✅" : "❌") . " Participant1 voit la session dans ses participations\n";
        echo "   Nombre de participations: " . count($participations1) . "\n";
    }

    // 8. Vérifier les participations de Participant2
    echo "\n📋 VÉRIFICATION PARTICIPATIONS PARTICIPANT2\n";
    echo "===========================================\n";

    $participationsParticipant2Result = testEndpoint('GET', '/sessions/my-participations', null, $users[2]['token'], 'Récupérer les participations de Participant2');

    $participationsParticipant2Data = json_decode($participationsParticipant2Result['response'], true);
    if (isset($participationsParticipant2Data['data']['data'])) {
        $participations2 = $participationsParticipant2Data['data']['data'];
        $hasParticipation2 = false;
        foreach ($participations2 as $session) {
            if ($session['id'] === $sessionId) {
                $hasParticipation2 = true;
                break;
            }
        }
        echo "   " . ($hasParticipation2 ? "❌" : "✅") . " Participant2 ne voit pas la session dans ses participations\n";
        echo "   Nombre de participations: " . count($participations2) . "\n";
    }

} else {
    echo "   ❌ Erreur lors de la création de la session\n";
    echo "   Réponse: " . $createSessionResult['response'] . "\n";
}

echo "\n=== TEST TERMINÉ ===\n";
