<?php

// Test simple de l'endpoint d'invitation avec des utilisateurs existants
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

    if ($httpCode >= 400) {
        echo "   Erreur: " . substr($response, 0, 200) . (strlen($response) > 200 ? "..." : "") . "\n";
    }

    return ['code' => $httpCode, 'response' => $response];
}

echo "=== TEST SIMPLE DE L'ENDPOINT D'INVITATION ===\n\n";

// 1. Login avec un utilisateur existant
echo "🔐 LOGIN\n";
echo "========\n";

$loginResult = testEndpoint('POST', '/login', [
    'email' => 'invitetest@example.com',
    'password' => 'password123',
    'device_name' => 'test-device'
], null, 'Se connecter');

// Extraire le token
$loginData = json_decode($loginResult['response'], true);
if (isset($loginData['token'])) {
    $token = $loginData['token'];
    echo "   Token obtenu: " . substr($token, 0, 20) . "...\n\n";
}

// 2. Créer une session
echo "🏃 CRÉATION DE SESSION\n";
echo "=====================\n";

$createSessionResult = testEndpoint('POST', '/sessions', [
    'sport' => 'tennis',
    'date' => date('Y-m-d', strtotime('+1 week')),
    'time' => '18:00',
    'location' => 'Tennis Club de Paris'
], $token, 'Créer une session');

// Extraire l'ID de session
$sessionData = json_decode($createSessionResult['response'], true);
if (isset($sessionData['data']['id'])) {
    $sessionId = $sessionData['data']['id'];
    echo "   Session créée avec ID: $sessionId\n\n";
}

// 3. Tester l'invitation avec un ID d'utilisateur existant
echo "📨 TEST D'INVITATION\n";
echo "===================\n";

if ($sessionId) {
    // Utiliser un ID d'utilisateur existant
    $inviteResult = testEndpoint('POST', "/sessions/$sessionId/invite", [
        'userIds' => ['9f6fa81c-76b5-4df9-aaa6-358d7006a64f'] // Utiliser un ID existant
    ], $token, 'Inviter un utilisateur existant à la session');

    echo "\n   Réponse complète: " . $inviteResult['response'] . "\n";
}

echo "\n=== FIN DU TEST ===\n";
