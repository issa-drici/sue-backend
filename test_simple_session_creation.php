<?php

// Test simple pour vérifier la création de session avec maxParticipants
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

echo "=== TEST SIMPLE CRÉATION SESSION ===\n\n";

// 1. Créer un utilisateur
echo "🔐 CRÉATION UTILISATEUR\n";
echo "=======================\n";

$createUserResult = testEndpoint('POST', '/register', [
    'email' => 'simpletest@example.com',
    'password' => 'password123',
    'firstname' => 'Simple',
    'lastname' => 'Test',
    'device_name' => 'test-device'
], null, 'Créer un utilisateur de test');

// 2. Login
$loginResult = testEndpoint('POST', '/login', [
    'email' => 'simpletest@example.com',
    'password' => 'password123',
    'device_name' => 'test-device'
], null, 'Se connecter');

// Extraire le token
$loginData = json_decode($loginResult['response'], true);
if (isset($loginData['token'])) {
    $token = $loginData['token'];
    echo "   Token obtenu: " . substr($token, 0, 20) . "...\n\n";
}

// 3. Créer une session avec maxParticipants
echo "🏃 CRÉATION DE SESSION AVEC MAX PARTICIPANTS\n";
echo "============================================\n";

$createSessionResult = testEndpoint('POST', '/sessions', [
    'sport' => 'tennis',
    'date' => date('Y-m-d', strtotime('+1 week')),
    'time' => '18:00',
    'location' => 'Tennis Club de Paris',
    'maxParticipants' => 6
], $token, 'Créer une session avec maxParticipants');

// Vérifier la réponse
$sessionData = json_decode($createSessionResult['response'], true);
if (isset($sessionData['data']['id'])) {
    $sessionId = $sessionData['data']['id'];
    $maxParticipants = $sessionData['data']['maxParticipants'];

    echo "   ✅ Session créée avec succès!\n";
    echo "   Session ID: $sessionId\n";
    echo "   Max Participants: $maxParticipants\n";
    echo "   Réponse complète: " . json_encode($sessionData['data'], JSON_PRETTY_PRINT) . "\n";
} else {
    echo "   ❌ Erreur lors de la création de la session\n";
    echo "   Réponse: " . $createSessionResult['response'] . "\n";
}

echo "\n=== TEST TERMINÉ ===\n";
