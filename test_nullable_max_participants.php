<?php

// Test pour vérifier que maxParticipants peut être null
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

echo "=== TEST MAX PARTICIPANTS NULLABLE ===\n\n";

// 1. Créer un utilisateur
echo "🔐 CRÉATION UTILISATEUR\n";
echo "=======================\n";

$createUserResult = testEndpoint('POST', '/register', [
    'email' => 'nullabletest@example.com',
    'password' => 'password123',
    'firstname' => 'Nullable',
    'lastname' => 'Test',
    'device_name' => 'test-device'
], null, 'Créer un utilisateur de test');

// 2. Login
$loginResult = testEndpoint('POST', '/login', [
    'email' => 'nullabletest@example.com',
    'password' => 'password123',
    'device_name' => 'test-device'
], null, 'Se connecter');

// Extraire le token
$loginData = json_decode($loginResult['response'], true);
if (isset($loginData['token'])) {
    $token = $loginData['token'];
    echo "   Token obtenu: " . substr($token, 0, 20) . "...\n\n";
}

// 3. Test 1: Créer une session SANS maxParticipants
echo "🏃 TEST 1: SESSION SANS MAX PARTICIPANTS\n";
echo "=========================================\n";

$createSessionResult1 = testEndpoint('POST', '/sessions', [
    'sport' => 'tennis',
    'date' => date('Y-m-d', strtotime('+1 week')),
    'time' => '18:00',
    'location' => 'Tennis Club de Paris'
], $token, 'Créer une session sans maxParticipants');

// Vérifier la réponse
$sessionData1 = json_decode($createSessionResult1['response'], true);
if (isset($sessionData1['data']['id'])) {
    $sessionId1 = $sessionData1['data']['id'];
    $maxParticipants1 = $sessionData1['data']['maxParticipants'];

    echo "   ✅ Session créée avec succès!\n";
    echo "   Session ID: $sessionId1\n";
    echo "   Max Participants: " . ($maxParticipants1 === null ? 'null' : $maxParticipants1) . "\n";
} else {
    echo "   ❌ Erreur lors de la création de la session\n";
    echo "   Réponse: " . $createSessionResult1['response'] . "\n";
}

// 4. Test 2: Créer une session AVEC maxParticipants = null explicitement
echo "\n🏃 TEST 2: SESSION AVEC MAX PARTICIPANTS = NULL\n";
echo "================================================\n";

$createSessionResult2 = testEndpoint('POST', '/sessions', [
    'sport' => 'tennis',
    'date' => date('Y-m-d', strtotime('+1 week')),
    'time' => '19:00',
    'location' => 'Tennis Club de Paris',
    'maxParticipants' => null
], $token, 'Créer une session avec maxParticipants = null');

// Vérifier la réponse
$sessionData2 = json_decode($createSessionResult2['response'], true);
if (isset($sessionData2['data']['id'])) {
    $sessionId2 = $sessionData2['data']['id'];
    $maxParticipants2 = $sessionData2['data']['maxParticipants'];

    echo "   ✅ Session créée avec succès!\n";
    echo "   Session ID: $sessionId2\n";
    echo "   Max Participants: " . ($maxParticipants2 === null ? 'null' : $maxParticipants2) . "\n";
} else {
    echo "   ❌ Erreur lors de la création de la session\n";
    echo "   Réponse: " . $createSessionResult2['response'] . "\n";
}

// 5. Test 3: Créer une session AVEC maxParticipants = 5
echo "\n🏃 TEST 3: SESSION AVEC MAX PARTICIPANTS = 5\n";
echo "==============================================\n";

$createSessionResult3 = testEndpoint('POST', '/sessions', [
    'sport' => 'tennis',
    'date' => date('Y-m-d', strtotime('+1 week')),
    'time' => '20:00',
    'location' => 'Tennis Club de Paris',
    'maxParticipants' => 5
], $token, 'Créer une session avec maxParticipants = 5');

// Vérifier la réponse
$sessionData3 = json_decode($createSessionResult3['response'], true);
if (isset($sessionData3['data']['id'])) {
    $sessionId3 = $sessionData3['data']['id'];
    $maxParticipants3 = $sessionData3['data']['maxParticipants'];

    echo "   ✅ Session créée avec succès!\n";
    echo "   Session ID: $sessionId3\n";
    echo "   Max Participants: " . ($maxParticipants3 === null ? 'null' : $maxParticipants3) . "\n";
} else {
    echo "   ❌ Erreur lors de la création de la session\n";
    echo "   Réponse: " . $createSessionResult3['response'] . "\n";
}

echo "\n=== TEST TERMINÉ ===\n";
