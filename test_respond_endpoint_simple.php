<?php

// Test simple pour vérifier que l'endpoint respond fonctionne
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

echo "=== TEST ENDPOINT RESPOND SIMPLE ===\n\n";

// 1. Créer un utilisateur organisateur
echo "🔐 CRÉATION UTILISATEUR ORGANISATEUR\n";
echo "====================================\n";

$createOrganizerResult = testEndpoint('POST', '/register', [
    'email' => 'organizer@respond.com',
    'password' => 'password123',
    'firstname' => 'Organisateur',
    'lastname' => 'Test',
    'device_name' => 'test-device'
], null, 'Créer un utilisateur organisateur');

// 2. Créer un utilisateur participant
echo "\n🔐 CRÉATION UTILISATEUR PARTICIPANT\n";
echo "====================================\n";

$createParticipantResult = testEndpoint('POST', '/register', [
    'email' => 'participant@respond.com',
    'password' => 'password123',
    'firstname' => 'Participant',
    'lastname' => 'Test',
    'device_name' => 'test-device'
], null, 'Créer un utilisateur participant');

// 3. Login avec l'organisateur
$loginOrganizerResult = testEndpoint('POST', '/login', [
    'email' => 'organizer@respond.com',
    'password' => 'password123',
    'device_name' => 'test-device'
], null, 'Se connecter avec l\'organisateur');

// Extraire le token et l'ID de l'organisateur
$organizerData = json_decode($loginOrganizerResult['response'], true);
if (isset($organizerData['token'])) {
    $organizerToken = $organizerData['token'];
    $organizerId = $organizerData['user']['id'];
    echo "   Token organisateur obtenu: " . substr($organizerToken, 0, 20) . "...\n";
    echo "   Organisateur ID: $organizerId\n\n";
}

// 4. Login avec le participant pour obtenir son ID
$loginParticipantResult = testEndpoint('POST', '/login', [
    'email' => 'participant@respond.com',
    'password' => 'password123',
    'device_name' => 'test-device'
], null, 'Se connecter avec le participant');

// Extraire l'ID du participant
$participantData = json_decode($loginParticipantResult['response'], true);
if (isset($participantData['user']['id'])) {
    $participantId = $participantData['user']['id'];
    $participantToken = $participantData['token'];
    echo "   Participant ID: $participantId\n";
    echo "   Token participant obtenu: " . substr($participantToken, 0, 20) . "...\n\n";
}

// 5. Créer une session avec le participant invité
echo "🏃 CRÉATION DE SESSION AVEC PARTICIPANT\n";
echo "=======================================\n";

$createSessionResult = testEndpoint('POST', '/sessions', [
    'sport' => 'tennis',
    'date' => date('Y-m-d', strtotime('+1 week')),
    'time' => '18:00',
    'location' => 'Tennis Club de Paris',
    'participantIds' => [$participantId]
], $organizerToken, 'Créer une session avec participant invité');

// Vérifier la réponse
$sessionData = json_decode($createSessionResult['response'], true);
if (isset($sessionData['data']['id'])) {
    $sessionId = $sessionData['data']['id'];
    echo "   ✅ Session créée avec succès!\n";
    echo "   Session ID: $sessionId\n\n";

    // 6. Test de l'endpoint respond - ACCEPT
    echo "👥 TEST ENDPOINT RESPOND - ACCEPT\n";
    echo "==================================\n";

    $respondAcceptResult = testEndpoint('PATCH', "/sessions/$sessionId/respond", [
        'response' => 'accept'
    ], $participantToken, 'Participant accepte l\'invitation');

    echo "   Réponse complète: " . $respondAcceptResult['response'] . "\n";

    // 7. Test de l'endpoint respond - DECLINE
    echo "\n👥 TEST ENDPOINT RESPOND - DECLINE\n";
    echo "===================================\n";

    $respondDeclineResult = testEndpoint('PATCH', "/sessions/$sessionId/respond", [
        'response' => 'decline'
    ], $participantToken, 'Participant décline l\'invitation');

    echo "   Réponse complète: " . $respondDeclineResult['response'] . "\n";

} else {
    echo "   ❌ Erreur lors de la création de la session\n";
    echo "   Réponse: " . $createSessionResult['response'] . "\n";
}

echo "\n=== TEST TERMINÉ ===\n";
