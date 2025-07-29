<?php

// Test pour vérifier le comportement de maxParticipants
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

echo "=== TEST MAX_PARTICIPANTS NULL ===\n\n";

// 1. Créer un utilisateur
echo "🔐 CRÉATION UTILISATEUR\n";
echo "=======================\n";

$createUserResult = testEndpoint('POST', '/register', [
    'email' => 'user@max.com',
    'password' => 'password123',
    'firstname' => 'Max',
    'lastname' => 'Test',
    'device_name' => 'test-device'
], null, 'Créer un utilisateur');

// 2. Login
$loginResult = testEndpoint('POST', '/login', [
    'email' => 'user@max.com',
    'password' => 'password123',
    'device_name' => 'test-device'
], null, 'Se connecter');

$userData = json_decode($loginResult['response'], true);
if (isset($userData['token'])) {
    $token = $userData['token'];

    echo "   Token obtenu: " . substr($token, 0, 20) . "...\n\n";

    // 3. Créer une session SANS maxParticipants
    echo "📋 CRÉATION SESSION SANS MAX_PARTICIPANTS\n";
    echo "=========================================\n";

    $sessionData = [
        'sport' => 'tennis',
        'date' => date('Y-m-d', strtotime('+1 day')),
        'time' => '18:00',
        'location' => 'Tennis Club - Test Null'
    ];

    $createSessionResult = testEndpoint('POST', '/sessions', $sessionData, $token, 'Créer session sans maxParticipants');

    if ($createSessionResult['code'] === 201) {
        $sessionResponse = json_decode($createSessionResult['response'], true);
        $session = $sessionResponse['data'];

        echo "   ✅ Session créée avec succès\n";
        echo "   - ID: " . $session['id'] . "\n";
        echo "   - MaxParticipants: " . ($session['maxParticipants'] ?? 'NULL') . "\n";
        echo "   - Type: " . gettype($session['maxParticipants'] ?? null) . "\n";

        if (isset($session['maxParticipants'])) {
            echo "   ❌ PROBLÈME: maxParticipants n'est pas null\n";
        } else {
            echo "   ✅ CORRECT: maxParticipants est null\n";
        }
    }

    // 4. Créer une session AVEC maxParticipants = null explicitement
    echo "\n📋 CRÉATION SESSION AVEC MAX_PARTICIPANTS = NULL\n";
    echo "================================================\n";

    $sessionData2 = [
        'sport' => 'tennis',
        'date' => date('Y-m-d', strtotime('+2 days')),
        'time' => '19:00',
        'location' => 'Tennis Club - Test Null Explicit',
        'maxParticipants' => null
    ];

    $createSessionResult2 = testEndpoint('POST', '/sessions', $sessionData2, $token, 'Créer session avec maxParticipants = null');

    if ($createSessionResult2['code'] === 201) {
        $sessionResponse2 = json_decode($createSessionResult2['response'], true);
        $session2 = $sessionResponse2['data'];

        echo "   ✅ Session créée avec succès\n";
        echo "   - ID: " . $session2['id'] . "\n";
        echo "   - MaxParticipants: " . ($session2['maxParticipants'] ?? 'NULL') . "\n";
        echo "   - Type: " . gettype($session2['maxParticipants'] ?? null) . "\n";

        if (isset($session2['maxParticipants'])) {
            echo "   ❌ PROBLÈME: maxParticipants n'est pas null\n";
        } else {
            echo "   ✅ CORRECT: maxParticipants est null\n";
        }
    }

    // 5. Créer une session AVEC maxParticipants = 5
    echo "\n📋 CRÉATION SESSION AVEC MAX_PARTICIPANTS = 5\n";
    echo "==============================================\n";

    $sessionData3 = [
        'sport' => 'tennis',
        'date' => date('Y-m-d', strtotime('+3 days')),
        'time' => '20:00',
        'location' => 'Tennis Club - Test Value 5',
        'maxParticipants' => 5
    ];

    $createSessionResult3 = testEndpoint('POST', '/sessions', $sessionData3, $token, 'Créer session avec maxParticipants = 5');

    if ($createSessionResult3['code'] === 201) {
        $sessionResponse3 = json_decode($createSessionResult3['response'], true);
        $session3 = $sessionResponse3['data'];

        echo "   ✅ Session créée avec succès\n";
        echo "   - ID: " . $session3['id'] . "\n";
        echo "   - MaxParticipants: " . ($session3['maxParticipants'] ?? 'NULL') . "\n";
        echo "   - Type: " . gettype($session3['maxParticipants'] ?? null) . "\n";

        if ($session3['maxParticipants'] === 5) {
            echo "   ✅ CORRECT: maxParticipants = 5\n";
        } else {
            echo "   ❌ PROBLÈME: maxParticipants n'est pas 5\n";
        }
    }

    // 6. Récupérer les détails d'une session pour vérifier
    if (isset($session['id'])) {
        echo "\n📋 RÉCUPÉRATION DÉTAILS SESSION\n";
        echo "===============================\n";

        $sessionDetailsResult = testEndpoint('GET', '/sessions/' . $session['id'], null, $token, 'Récupérer détails session');

        if ($sessionDetailsResult['code'] === 200) {
            $sessionDetails = json_decode($sessionDetailsResult['response'], true);
            $sessionDetail = $sessionDetails['data'];

            echo "   ✅ Détails récupérés\n";
            echo "   - ID: " . $sessionDetail['id'] . "\n";
            echo "   - MaxParticipants: " . ($sessionDetail['maxParticipants'] ?? 'NULL') . "\n";
            echo "   - Type: " . gettype($sessionDetail['maxParticipants'] ?? null) . "\n";
        }
    }

} else {
    echo "   ❌ Erreur lors de la connexion\n";
}

echo "\n=== TEST TERMINÉ ===\n";
