<?php

// Test de débogage du serveur Laravel
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

echo "=== DÉBOGAGE SERVEUR LARAVEL ===\n\n";

// 1. Tester l'endpoint de version (publique)
echo "🔍 TEST ENDPOINT VERSION (PUBLIQUE)\n";
echo "===================================\n";

$versionResult = testEndpoint('GET', '/version', null, null, 'Tester l\'endpoint de version');

echo "   Réponse: " . $versionResult['response'] . "\n\n";

// 2. Créer un utilisateur de test
echo "🔐 CRÉATION UTILISATEUR DE TEST\n";
echo "===============================\n";

$createUserResult = testEndpoint('POST', '/register', [
    'email' => 'debugtest@example.com',
    'password' => 'password123',
    'firstname' => 'Debug',
    'lastname' => 'Test',
    'device_name' => 'test-device'
], null, 'Créer un utilisateur de test');

echo "   Réponse: " . $createUserResult['response'] . "\n\n";

// 3. Login avec l'utilisateur
$loginResult = testEndpoint('POST', '/login', [
    'email' => 'debugtest@example.com',
    'password' => 'password123',
    'device_name' => 'test-device'
], null, 'Se connecter avec l\'utilisateur');

$loginData = json_decode($loginResult['response'], true);
$token = $loginData['token'] ?? null;

if ($token) {
    echo "   Token obtenu: " . substr($token, 0, 20) . "...\n\n";
}

// 4. Tester l'endpoint /user (Laravel par défaut)
echo "🔍 TEST ENDPOINT /USER (LARAVEL DÉFAUT)\n";
echo "========================================\n";

$userResult = testEndpoint('GET', '/user', null, $token, 'Tester l\'endpoint /user de Laravel');

echo "   Réponse: " . $userResult['response'] . "\n\n";

// 5. Tester l'endpoint de recherche
echo "🔍 TEST ENDPOINT RECHERCHE\n";
echo "==========================\n";

$searchResult = testEndpoint('GET', '/users/search?q=debugtest@example.com', null, $token, 'Tester l\'endpoint de recherche');

echo "   Réponse: " . $searchResult['response'] . "\n\n";

// 6. Tester un autre endpoint protégé
echo "🔍 TEST AUTRE ENDPOINT PROTÉGÉ\n";
echo "===============================\n";

$profileResult = testEndpoint('GET', '/profile', null, $token, 'Tester l\'endpoint de profil');

echo "   Réponse: " . $profileResult['response'] . "\n\n";

// 7. Vérifier les routes disponibles
echo "🔍 VÉRIFICATION ROUTES\n";
echo "======================\n";

$routesResult = testEndpoint('GET', '/', null, null, 'Tester la racine de l\'API');

echo "   Réponse racine: " . $routesResult['response'] . "\n\n";

echo "=== FIN DU DÉBOGAGE ===\n";
