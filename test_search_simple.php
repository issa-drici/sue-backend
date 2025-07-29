<?php

// Test simple pour l'endpoint de recherche d'utilisateurs
$baseUrl = 'http://localhost:8000/api';

function testEndpoint($method, $endpoint, $data = null, $token = null, $description = '') {
    global $baseUrl;

    $url = $baseUrl . $endpoint;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_VERBOSE, true);

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

echo "=== TEST SIMPLE RECHERCHE UTILISATEURS ===\n\n";

// 1. Créer un utilisateur de test
echo "🔐 CRÉATION UTILISATEUR DE TEST\n";
echo "===============================\n";

$createUserResult = testEndpoint('POST', '/register', [
    'email' => 'simpletest@example.com',
    'password' => 'password123',
    'firstname' => 'Simple',
    'lastname' => 'Test',
    'device_name' => 'test-device'
], null, 'Créer un utilisateur de test');

// 2. Créer un utilisateur pour la recherche
echo "🔐 CRÉATION UTILISATEUR POUR RECHERCHE\n";
echo "======================================\n";

$createSearcherResult = testEndpoint('POST', '/register', [
    'email' => 'searcher3@example.com',
    'password' => 'password123',
    'firstname' => 'Searcher',
    'lastname' => 'Three',
    'device_name' => 'test-device'
], null, 'Créer un utilisateur pour la recherche');

// 3. Login avec l'utilisateur de recherche
$loginResult = testEndpoint('POST', '/login', [
    'email' => 'searcher3@example.com',
    'password' => 'password123',
    'device_name' => 'test-device'
], null, 'Se connecter avec l\'utilisateur de recherche');

$loginData = json_decode($loginResult['response'], true);
$token = $loginData['token'] ?? null;

if ($token) {
    echo "   Token obtenu: " . substr($token, 0, 20) . "...\n\n";
}

// 4. Tester la recherche
echo "🔍 TEST RECHERCHE SIMPLE\n";
echo "========================\n";

$searchResult = testEndpoint('GET', '/users/search?q=simpletest@example.com', null, $token, 'Rechercher l\'utilisateur');

echo "   Réponse complète:\n";
echo "   " . $searchResult['response'] . "\n\n";

$searchData = json_decode($searchResult['response'], true);

if (isset($searchData['success']) && $searchData['success']) {
    echo "   ✅ Succès: " . $searchData['success'] . "\n";
    if (isset($searchData['data'])) {
        echo "   📊 Nombre de résultats: " . count($searchData['data']) . "\n";
        foreach ($searchData['data'] as $user) {
            echo "   👤 Utilisateur: " . $user['email'] . " (" . $user['firstname'] . " " . $user['lastname'] . ")\n";
        }
    }
    if (isset($searchData['pagination'])) {
        echo "   📄 Pagination: Page " . $searchData['pagination']['page'] . " sur " . $searchData['pagination']['totalPages'] . "\n";
    }
} else {
    echo "   ❌ Échec de la recherche\n";
    if (isset($searchData['error'])) {
        echo "   🚨 Erreur: " . json_encode($searchData['error']) . "\n";
    }
}

echo "\n=== FIN DU TEST ===\n";
