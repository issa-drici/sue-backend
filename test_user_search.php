<?php

// Test pour diagnostiquer le problème de recherche d'utilisateurs
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

echo "=== TEST RECHERCHE UTILISATEURS ===\n\n";

// 1. Créer un utilisateur de test avec l'email recherché
echo "🔐 CRÉATION UTILISATEUR DE TEST\n";
echo "===============================\n";

$createUserResult = testEndpoint('POST', '/register', [
    'email' => 'gued.as76@hotmail.com',
    'password' => 'password123',
    'firstname' => 'Gued',
    'lastname' => 'Test',
    'device_name' => 'test-device'
], null, 'Créer un utilisateur avec l\'email recherché');

// 2. Créer un autre utilisateur pour la recherche
echo "🔐 CRÉATION UTILISATEUR POUR RECHERCHE\n";
echo "======================================\n";

$createSearcherResult = testEndpoint('POST', '/register', [
    'email' => 'searcher@example.com',
    'password' => 'password123',
    'firstname' => 'Searcher',
    'lastname' => 'User',
    'device_name' => 'test-device'
], null, 'Créer un utilisateur pour faire la recherche');

// 3. Login avec l'utilisateur qui va faire la recherche
$loginResult = testEndpoint('POST', '/login', [
    'email' => 'searcher@example.com',
    'password' => 'password123',
    'device_name' => 'test-device'
], null, 'Se connecter avec l\'utilisateur de recherche');

$loginData = json_decode($loginResult['response'], true);
$token = $loginData['token'] ?? null;

if ($token) {
    echo "   Token obtenu: " . substr($token, 0, 20) . "...\n\n";
}

// 4. Tester la recherche avec l'email complet
echo "🔍 TEST RECHERCHE AVEC EMAIL COMPLET\n";
echo "=====================================\n";

$searchResult = testEndpoint('GET', '/users/search?q=gued.as76@hotmail.com', null, $token, 'Rechercher avec l\'email complet');

$searchData = json_decode($searchResult['response'], true);
if (isset($searchData['data'])) {
    echo "   Nombre de résultats: " . count($searchData['data']) . "\n";
    foreach ($searchData['data'] as $user) {
        echo "   - Utilisateur trouvé: " . $user['email'] . " (" . $user['firstname'] . " " . $user['lastname'] . ")\n";
    }
} else {
    echo "   Aucun résultat trouvé\n";
}
echo "\n";

// 5. Tester la recherche avec une partie de l'email
echo "🔍 TEST RECHERCHE AVEC PARTIE EMAIL\n";
echo "====================================\n";

$searchPartialResult = testEndpoint('GET', '/users/search?q=gued.as76', null, $token, 'Rechercher avec une partie de l\'email');

$searchPartialData = json_decode($searchPartialResult['response'], true);
if (isset($searchPartialData['data'])) {
    echo "   Nombre de résultats: " . count($searchPartialData['data']) . "\n";
    foreach ($searchPartialData['data'] as $user) {
        echo "   - Utilisateur trouvé: " . $user['email'] . " (" . $user['firstname'] . " " . $user['lastname'] . ")\n";
    }
} else {
    echo "   Aucun résultat trouvé\n";
}
echo "\n";

// 6. Tester la recherche avec le prénom
echo "🔍 TEST RECHERCHE AVEC PRÉNOM\n";
echo "==============================\n";

$searchfirstnameResult = testEndpoint('GET', '/users/search?q=Gued', null, $token, 'Rechercher avec le prénom');

$searchfirstnameData = json_decode($searchfirstnameResult['response'], true);
if (isset($searchfirstnameData['data'])) {
    echo "   Nombre de résultats: " . count($searchfirstnameData['data']) . "\n";
    foreach ($searchfirstnameData['data'] as $user) {
        echo "   - Utilisateur trouvé: " . $user['email'] . " (" . $user['firstname'] . " " . $user['lastname'] . ")\n";
    }
} else {
    echo "   Aucun résultat trouvé\n";
}
echo "\n";

// 7. Tester la recherche avec le nom
echo "🔍 TEST RECHERCHE AVEC NOM\n";
echo "===========================\n";

$searchlastnameResult = testEndpoint('GET', '/users/search?q=Test', null, $token, 'Rechercher avec le nom');

$searchlastnameData = json_decode($searchlastnameResult['response'], true);
if (isset($searchlastnameData['data'])) {
    echo "   Nombre de résultats: " . count($searchlastnameData['data']) . "\n";
    foreach ($searchlastnameData['data'] as $user) {
        echo "   - Utilisateur trouvé: " . $user['email'] . " (" . $user['firstname'] . " " . $user['lastname'] . ")\n";
    }
} else {
    echo "   Aucun résultat trouvé\n";
}
echo "\n";

// 8. Vérifier si l'utilisateur existe en base
echo "🔍 VÉRIFICATION EXISTENCE UTILISATEUR\n";
echo "======================================\n";

// Créer un utilisateur admin pour vérifier
$createAdminResult = testEndpoint('POST', '/register', [
    'email' => 'admin@example.com',
    'password' => 'password123',
    'firstname' => 'Admin',
    'lastname' => 'User',
    'device_name' => 'test-device'
], null, 'Créer un utilisateur admin');

$loginAdminResult = testEndpoint('POST', '/login', [
    'email' => 'admin@example.com',
    'password' => 'password123',
    'device_name' => 'test-device'
], null, 'Se connecter avec l\'admin');

$loginAdminData = json_decode($loginAdminResult['response'], true);
$adminToken = $loginAdminData['token'] ?? null;

if ($adminToken) {
    $searchAdminResult = testEndpoint('GET', '/users/search?q=gued.as76@hotmail.com', null, $adminToken, 'Rechercher avec l\'admin');

    $searchAdminData = json_decode($searchAdminResult['response'], true);
    if (isset($searchAdminData['data'])) {
        echo "   Nombre de résultats (admin): " . count($searchAdminData['data']) . "\n";
        foreach ($searchAdminData['data'] as $user) {
            echo "   - Utilisateur trouvé: " . $user['email'] . " (" . $user['firstname'] . " " . $user['lastname'] . ")\n";
        }
    } else {
        echo "   Aucun résultat trouvé (admin)\n";
    }
}

echo "\n=== FIN DU TEST ===\n";
