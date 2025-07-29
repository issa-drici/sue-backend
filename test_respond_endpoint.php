<?php

// Test simple de l'endpoint de réponse à l'invitation
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

echo "=== TEST DE L'ENDPOINT DE RÉPONSE À L'INVITATION ===\n\n";

// 1. Créer un utilisateur créateur de session
echo "🔐 CRÉATION UTILISATEUR CRÉATEUR\n";
echo "================================\n";

$createCreatorResult = testEndpoint('POST', '/register', [
    'email' => 'creator@example.com',
    'password' => 'password123',
    'firstname' => 'Session',
    'lastname' => 'Creator',
    'device_name' => 'test-device-creator'
], null, 'Créer un utilisateur créateur de session');

// 2. Login avec le créateur
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

// 3. Créer une session
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

// 4. Créer un utilisateur invité
echo "👥 CRÉATION UTILISATEUR INVITÉ\n";
echo "==============================\n";

$createInvitedResult = testEndpoint('POST', '/register', [
    'email' => 'responder@example.com',
    'password' => 'password123',
    'firstname' => 'Session',
    'lastname' => 'Responder',
    'device_name' => 'test-device-responder'
], null, 'Créer un utilisateur invité');

// 5. Récupérer l'ID de l'utilisateur invité
echo "🔍 RECHERCHE DE L'UTILISATEUR INVITÉ\n";
echo "====================================\n";

$searchResult = testEndpoint('GET', '/users/search?q=responder', null, $creatorToken, 'Rechercher l\'utilisateur invité');

$searchData = json_decode($searchResult['response'], true);
$invitedUserId = null;
echo "   Réponse de recherche: " . $searchResult['response'] . "\n";
if (isset($searchData['data'][0]['id'])) {
    $invitedUserId = $searchData['data'][0]['id'];
    echo "   Utilisateur invité trouvé avec ID: $invitedUserId\n\n";
} else {
    echo "   Aucun utilisateur trouvé dans la recherche\n\n";
}

// 6. Inviter l'utilisateur à la session
echo "📨 INVITATION\n";
echo "=============\n";

if ($sessionId && $invitedUserId) {
    $inviteResult = testEndpoint('POST', "/sessions/$sessionId/invite", [
        'userIds' => [$invitedUserId]
    ], $creatorToken, 'Inviter l\'utilisateur à la session');

    echo "   Réponse invitation: " . $inviteResult['response'] . "\n\n";
}

// 7. Login avec l'utilisateur invité
echo "🔐 LOGIN UTILISATEUR INVITÉ\n";
echo "===========================\n";

$loginInvitedResult = testEndpoint('POST', '/login', [
    'email' => 'responder@example.com',
    'password' => 'password123',
    'device_name' => 'test-device-responder'
], null, 'Se connecter avec l\'utilisateur invité');

// Extraire le token de l'utilisateur invité
$loginInvitedData = json_decode($loginInvitedResult['response'], true);
if (isset($loginInvitedData['token'])) {
    $invitedToken = $loginInvitedData['token'];
    echo "   Token utilisateur invité obtenu: " . substr($invitedToken, 0, 20) . "...\n\n";
}

// 8. Tester la réponse à l'invitation
echo "✅ TEST DE RÉPONSE À L'INVITATION\n";
echo "=================================\n";

if ($sessionId && $invitedToken) {
    $respondResult = testEndpoint('PATCH', "/sessions/$sessionId/respond", [
        'response' => 'accept'
    ], $invitedToken, 'Répondre à l\'invitation (accepter)');

    echo "\n   Réponse complète: " . $respondResult['response'] . "\n";
}

echo "\n=== FIN DU TEST ===\n";
