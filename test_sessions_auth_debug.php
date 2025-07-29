<?php

// Test pour diagnostiquer le problème d'authentification sur les sessions
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

echo "=== DIAGNOSTIC AUTHENTIFICATION SESSIONS ===\n\n";

// 1. Créer un utilisateur
echo "🔐 CRÉATION UTILISATEUR\n";
echo "=======================\n";

$createUserResult = testEndpoint('POST', '/register', [
    'email' => 'user@auth.com',
    'password' => 'password123',
    'firstname' => 'Auth',
    'lastname' => 'Test',
    'device_name' => 'test-device'
], null, 'Créer un utilisateur');

// 2. Login
$loginResult = testEndpoint('POST', '/login', [
    'email' => 'user@auth.com',
    'password' => 'password123',
    'device_name' => 'test-device'
], null, 'Se connecter');

$userData = json_decode($loginResult['response'], true);
if (isset($userData['token'])) {
    $token = $userData['token'];

    echo "   Token obtenu: " . substr($token, 0, 20) . "...\n";
    echo "   Token complet: $token\n\n";

    // 3. Tester l'endpoint GET /api/user (test d'authentification)
    echo "🔐 TEST AUTHENTIFICATION\n";
    echo "=========================\n";

    $userResult = testEndpoint('GET', '/user', null, $token, 'Tester l\'authentification');

    if ($userResult['code'] === 200) {
        $userInfo = json_decode($userResult['response'], true);
        echo "   ✅ Utilisateur authentifié: " . ($userInfo['email'] ?? 'N/A') . "\n\n";
    } else {
        echo "   ❌ Problème d'authentification\n";
        echo "   Réponse: " . $userResult['response'] . "\n\n";
    }

    // 4. Tester l'endpoint GET /api/sessions
    echo "📋 TEST GET /SESSIONS\n";
    echo "=====================\n";

    $sessionsResult = testEndpoint('GET', '/sessions', null, $token, 'Récupérer les sessions');

    if ($sessionsResult['code'] === 200) {
        $sessionsData = json_decode($sessionsResult['response'], true);
        echo "   ✅ Sessions récupérées avec succès\n";
        echo "   Nombre de sessions: " . count($sessionsData['data'] ?? []) . "\n";
    } else {
        echo "   ❌ Erreur lors de la récupération des sessions\n";
        echo "   Code: " . $sessionsResult['code'] . "\n";
        echo "   Réponse: " . $sessionsResult['response'] . "\n";
    }

    // 5. Tester sans token (pour voir l'erreur d'authentification)
    echo "\n🔐 TEST SANS TOKEN\n";
    echo "==================\n";

    $sessionsNoTokenResult = testEndpoint('GET', '/sessions', null, null, 'Tester sans token');

    echo "   Code: " . $sessionsNoTokenResult['code'] . "\n";
    echo "   Réponse: " . $sessionsNoTokenResult['response'] . "\n";

    // 6. Tester avec un token invalide
    echo "\n🔐 TEST AVEC TOKEN INVALIDE\n";
    echo "============================\n";

    $invalidToken = 'invalid_token_123';
    $sessionsInvalidTokenResult = testEndpoint('GET', '/sessions', null, $invalidToken, 'Tester avec token invalide');

    echo "   Code: " . $sessionsInvalidTokenResult['code'] . "\n";
    echo "   Réponse: " . $sessionsInvalidTokenResult['response'] . "\n";

    // 7. Tester d'autres endpoints protégés pour comparaison
    echo "\n🔐 TEST AUTRES ENDPOINTS PROTÉGÉS\n";
    echo "==================================\n";

    $profileResult = testEndpoint('GET', '/users/profile', null, $token, 'Tester /users/profile');
    $friendsResult = testEndpoint('GET', '/users/friends', null, $token, 'Tester /users/friends');

} else {
    echo "   ❌ Erreur lors de la connexion\n";
    echo "   Réponse: " . $loginResult['response'] . "\n";
}

echo "\n=== DIAGNOSTIC TERMINÉ ===\n";
