<?php

// Test simple de l'endpoint de réponse à l'invitation avec des utilisateurs existants
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

echo "=== TEST SIMPLE DE RÉPONSE À L'INVITATION ===\n\n";

// 1. Login avec un utilisateur créateur existant
echo "🔐 LOGIN CRÉATEUR\n";
echo "================\n";

$loginCreatorResult = testEndpoint('POST', '/login', [
    'email' => 'creator@example.com',
    'password' => 'password123',
    'device_name' => 'test-device-creator'
], null, 'Se connecter avec le créateur');

// Extraire le token du créateur
$loginCreatorData = json_decode($loginCreatorResult['response'], true);
if (isset($loginCreatorData['token'])) {
    $creatorToken = $loginCreatorData['token'];
    echo "   Token créateur obtenu: " . substr($creatorToken, 0, 20) . "...\n\n";
}

// 2. Créer une session
echo "🏃 CRÉATION DE SESSION\n";
echo "=====================\n";

$createSessionResult = testEndpoint('POST', '/sessions', [
    'sport' => 'tennis',
    'date' => date('Y-m-d', strtotime('+1 week')),
    'time' => '18:00',
    'location' => 'Tennis Club de Paris'
], $creatorToken, 'Créer une session');

// Extraire l'ID de session
$sessionData = json_decode($createSessionResult['response'], true);
if (isset($sessionData['data']['id'])) {
    $sessionId = $sessionData['data']['id'];
    echo "   Session créée avec ID: $sessionId\n\n";
}

// 3. Inviter un utilisateur existant à la session
echo "📨 INVITATION\n";
echo "=============\n";

if ($sessionId) {
    // Utiliser un ID d'utilisateur existant
    $inviteResult = testEndpoint('POST', "/sessions/$sessionId/invite", [
        'userIds' => ['9f6fb119-6916-4a91-878b-26676818a25f'] // ID de invited@example.com
    ], $creatorToken, 'Inviter un utilisateur existant à la session');

    echo "   Réponse invitation: " . $inviteResult['response'] . "\n\n";
}

// 4. Login avec l'utilisateur invité
echo "🔐 LOGIN UTILISATEUR INVITÉ\n";
echo "===========================\n";

$loginInvitedResult = testEndpoint('POST', '/login', [
    'email' => 'invited@example.com',
    'password' => 'password123',
    'device_name' => 'test-device-responder'
], null, 'Se connecter avec l\'utilisateur invité');

// Extraire le token de l'utilisateur invité
$loginInvitedData = json_decode($loginInvitedResult['response'], true);
if (isset($loginInvitedData['token'])) {
    $invitedToken = $loginInvitedData['token'];
    echo "   Token utilisateur invité obtenu: " . substr($invitedToken, 0, 20) . "...\n\n";
}

// 5. Tester la réponse à l'invitation
echo "✅ TEST DE RÉPONSE À L'INVITATION\n";
echo "=================================\n";

if ($sessionId && $invitedToken) {
    $respondResult = testEndpoint('PATCH', "/sessions/$sessionId/respond", [
        'response' => 'accept'
    ], $invitedToken, 'Répondre à l\'invitation (accepter)');

    echo "\n   Réponse complète: " . $respondResult['response'] . "\n";
}

echo "\n=== FIN DU TEST ===\n";
