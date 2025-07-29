<?php

// Test pour vérifier que l'endpoint GET /sessions/{id} renvoie maxParticipants
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

echo "=== TEST SESSION DETAILS MAX PARTICIPANTS ===\n\n";

// 1. Créer un utilisateur
echo "🔐 CRÉATION UTILISATEUR\n";
echo "=======================\n";

$createUserResult = testEndpoint('POST', '/register', [
    'email' => 'details@test.com',
    'password' => 'password123',
    'firstname' => 'Details',
    'lastname' => 'Test',
    'device_name' => 'test-device'
], null, 'Créer un utilisateur de test');

// 2. Login
$loginResult = testEndpoint('POST', '/login', [
    'email' => 'details@test.com',
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

$sessionData1 = json_decode($createSessionResult1['response'], true);
if (isset($sessionData1['data']['id'])) {
    $sessionId1 = $sessionData1['data']['id'];
    echo "   ✅ Session créée avec ID: $sessionId1\n";

    // Récupérer les détails de la session
    $detailsResult1 = testEndpoint('GET', "/sessions/$sessionId1", null, $token, 'Récupérer les détails de la session');

    $detailsData1 = json_decode($detailsResult1['response'], true);
    if (isset($detailsData1['data']['maxParticipants'])) {
        $maxParticipants1 = $detailsData1['data']['maxParticipants'];
        echo "   ✅ Max Participants dans les détails: " . ($maxParticipants1 === null ? 'null' : $maxParticipants1) . "\n";
        echo "   ✅ Réponse complète: " . json_encode($detailsData1['data'], JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "   ❌ Max Participants manquant dans les détails\n";
    }
}

// 4. Test 2: Créer une session AVEC maxParticipants = 5
echo "\n🏃 TEST 2: SESSION AVEC MAX PARTICIPANTS = 5\n";
echo "==============================================\n";

$createSessionResult2 = testEndpoint('POST', '/sessions', [
    'sport' => 'tennis',
    'date' => date('Y-m-d', strtotime('+1 week')),
    'time' => '19:00',
    'location' => 'Tennis Club de Paris',
    'maxParticipants' => 5
], $token, 'Créer une session avec maxParticipants = 5');

$sessionData2 = json_decode($createSessionResult2['response'], true);
if (isset($sessionData2['data']['id'])) {
    $sessionId2 = $sessionData2['data']['id'];
    echo "   ✅ Session créée avec ID: $sessionId2\n";

    // Récupérer les détails de la session
    $detailsResult2 = testEndpoint('GET', "/sessions/$sessionId2", null, $token, 'Récupérer les détails de la session');

    $detailsData2 = json_decode($detailsResult2['response'], true);
    if (isset($detailsData2['data']['maxParticipants'])) {
        $maxParticipants2 = $detailsData2['data']['maxParticipants'];
        echo "   ✅ Max Participants dans les détails: " . ($maxParticipants2 === null ? 'null' : $maxParticipants2) . "\n";
        echo "   ✅ Réponse complète: " . json_encode($detailsData2['data'], JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "   ❌ Max Participants manquant dans les détails\n";
    }
}

// 5. Test 3: Créer une session AVEC maxParticipants = null explicitement
echo "\n🏃 TEST 3: SESSION AVEC MAX PARTICIPANTS = NULL\n";
echo "================================================\n";

$createSessionResult3 = testEndpoint('POST', '/sessions', [
    'sport' => 'tennis',
    'date' => date('Y-m-d', strtotime('+1 week')),
    'time' => '20:00',
    'location' => 'Tennis Club de Paris',
    'maxParticipants' => null
], $token, 'Créer une session avec maxParticipants = null');

$sessionData3 = json_decode($createSessionResult3['response'], true);
if (isset($sessionData3['data']['id'])) {
    $sessionId3 = $sessionData3['data']['id'];
    echo "   ✅ Session créée avec ID: $sessionId3\n";

    // Récupérer les détails de la session
    $detailsResult3 = testEndpoint('GET', "/sessions/$sessionId3", null, $token, 'Récupérer les détails de la session');

    $detailsData3 = json_decode($detailsResult3['response'], true);
    if (isset($detailsData3['data']['maxParticipants'])) {
        $maxParticipants3 = $detailsData3['data']['maxParticipants'];
        echo "   ✅ Max Participants dans les détails: " . ($maxParticipants3 === null ? 'null' : $maxParticipants3) . "\n";
        echo "   ✅ Réponse complète: " . json_encode($detailsData3['data'], JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "   ❌ Max Participants manquant dans les détails\n";
    }
}

echo "\n=== TEST TERMINÉ ===\n";
