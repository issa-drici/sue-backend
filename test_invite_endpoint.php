<?php

// Test simple de l'endpoint d'invitation
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

echo "=== TEST DE L'ENDPOINT D'INVITATION ===\n\n";

// 1. Créer un utilisateur de test
echo "🔐 CRÉATION D'UTILISATEUR\n";
echo "========================\n";

$createUserResult = testEndpoint('POST', '/register', [
    'email' => 'invitetest@example.com',
    'password' => 'password123',
    'firstname' => 'Invite',
    'lastname' => 'Test',
    'device_name' => 'test-device'
], null, 'Créer un utilisateur de test');

// 2. Login
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

// 3. Créer une session
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

// 4. Créer un deuxième utilisateur pour l'inviter
echo "👥 CRÉATION D'UN DEUXIÈME UTILISATEUR\n";
echo "=====================================\n";

$createUser2Result = testEndpoint('POST', '/register', [
    'email' => 'invited@example.com',
    'password' => 'password123',
    'firstname' => 'Invited',
    'lastname' => 'User',
    'device_name' => 'test-device-2'
], null, 'Créer un deuxième utilisateur');

// 5. Récupérer l'ID du deuxième utilisateur
echo "🔍 RECHERCHE DU DEUXIÈME UTILISATEUR\n";
echo "===================================\n";

$searchResult = testEndpoint('GET', '/users/search?q=invited', null, $token, 'Rechercher le deuxième utilisateur');

$searchData = json_decode($searchResult['response'], true);
$invitedUserId = null;
echo "   Réponse de recherche: " . $searchResult['response'] . "\n";
if (isset($searchData['data'][0]['id'])) {
    $invitedUserId = $searchData['data'][0]['id'];
    echo "   Utilisateur trouvé avec ID: $invitedUserId\n\n";
} else {
    echo "   Aucun utilisateur trouvé\n\n";
}

// 6. Tester l'invitation
echo "📨 TEST D'INVITATION\n";
echo "===================\n";

if ($sessionId && $invitedUserId) {
    $inviteResult = testEndpoint('POST', "/sessions/$sessionId/invite", [
        'userIds' => [$invitedUserId]
    ], $token, 'Inviter l\'utilisateur à la session');

    echo "\n   Réponse complète: " . $inviteResult['response'] . "\n";
}

echo "\n=== FIN DU TEST ===\n";
