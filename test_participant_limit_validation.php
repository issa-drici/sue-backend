<?php

// Test pour vérifier la validation de la limite de participants
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

echo "=== TEST VALIDATION LIMITE PARTICIPANTS ===\n\n";

// 1. Créer plusieurs utilisateurs
echo "🔐 CRÉATION UTILISATEURS\n";
echo "========================\n";

$users = [];
$emails = ['organizer@limit.com', 'participant1@limit.com', 'participant2@limit.com', 'participant3@limit.com'];

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

// 2. Créer une session avec limite de 2 participants
echo "🏃 CRÉATION SESSION AVEC LIMITE DE 2 PARTICIPANTS\n";
echo "==================================================\n";

$organizer = $users[0];
$participantIds = [$users[1]['id'], $users[2]['id'], $users[3]['id']];

$createSessionResult = testEndpoint('POST', '/sessions', [
    'sport' => 'tennis',
    'date' => date('Y-m-d', strtotime('+1 week')),
    'time' => '18:00',
    'location' => 'Tennis Club de Paris',
    'maxParticipants' => 2,
    'participantIds' => $participantIds
], $organizer['token'], 'Créer une session avec limite de 2 participants');

// Vérifier la réponse
$sessionData = json_decode($createSessionResult['response'], true);
if (isset($sessionData['data']['id'])) {
    $sessionId = $sessionData['data']['id'];
    $maxParticipants = $sessionData['data']['maxParticipants'];

    echo "   ✅ Session créée avec succès!\n";
    echo "   Session ID: $sessionId\n";
    echo "   Max Participants: $maxParticipants\n";
    echo "   Participants invités: " . count($participantIds) . "\n\n";

    // 3. Test 1: Premier participant accepte (devrait réussir)
    echo "👥 TEST 1: PREMIER PARTICIPANT ACCEPTE\n";
    echo "======================================\n";

    $respondResult1 = testEndpoint('PATCH', "/sessions/$sessionId/respond", [
        'response' => 'accept'
    ], $users[1]['token'], 'Participant1 accepte l\'invitation');

    if ($respondResult1['code'] === 200) {
        echo "   ✅ Participant1 a pu accepter l'invitation\n";
    } else {
        echo "   ❌ Erreur: " . $respondResult1['response'] . "\n";
    }

    // 4. Test 2: Deuxième participant accepte (devrait réussir)
    echo "\n👥 TEST 2: DEUXIÈME PARTICIPANT ACCEPTE\n";
    echo "========================================\n";

    $respondResult2 = testEndpoint('PATCH', "/sessions/$sessionId/respond", [
        'response' => 'accept'
    ], $users[2]['token'], 'Participant2 accepte l\'invitation');

    if ($respondResult2['code'] === 200) {
        echo "   ✅ Participant2 a pu accepter l'invitation\n";
    } else {
        echo "   ❌ Erreur: " . $respondResult2['response'] . "\n";
    }

    // 5. Test 3: Troisième participant accepte (devrait échouer)
    echo "\n👥 TEST 3: TROISIÈME PARTICIPANT ACCEPTE (LIMITE ATTEINTE)\n";
    echo "==========================================================\n";

    $respondResult3 = testEndpoint('PATCH', "/sessions/$sessionId/respond", [
        'response' => 'accept'
    ], $users[3]['token'], 'Participant3 essaie d\'accepter l\'invitation (limite atteinte)');

    if ($respondResult3['code'] === 400) {
        echo "   ✅ Participant3 ne peut pas accepter (limite atteinte)\n";
        $errorData = json_decode($respondResult3['response'], true);
        if (isset($errorData['error']['message'])) {
            echo "   Message d'erreur: " . $errorData['error']['message'] . "\n";
        }
    } else {
        echo "   ❌ Erreur: Participant3 a pu accepter alors qu'il ne devrait pas\n";
        echo "   Code: " . $respondResult3['code'] . "\n";
        echo "   Réponse: " . $respondResult3['response'] . "\n";
    }

    // 6. Test 4: Vérifier les détails de la session
    echo "\n🔍 VÉRIFICATION DÉTAILS SESSION\n";
    echo "===============================\n";

    $sessionDetailResult = testEndpoint('GET', "/sessions/$sessionId", null, $organizer['token'], 'Récupérer les détails de la session');

    $sessionDetail = json_decode($sessionDetailResult['response'], true);
    if (isset($sessionDetail['data']['participants'])) {
        $participants = $sessionDetail['data']['participants'];
        echo "   ✅ Nombre de participants: " . count($participants) . "\n";

        $acceptedCount = 0;
        foreach ($participants as $participant) {
            echo "   - " . $participant['fullName'] . " (Status: " . $participant['status'] . ")\n";
            if ($participant['status'] === 'accepted') {
                $acceptedCount++;
            }
        }
        echo "   ✅ Nombre de participants acceptés: $acceptedCount\n";
    }

} else {
    echo "   ❌ Erreur lors de la création de la session\n";
    echo "   Réponse: " . $createSessionResult['response'] . "\n";
}

echo "\n=== TEST TERMINÉ ===\n";
