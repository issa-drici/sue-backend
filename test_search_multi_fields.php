<?php

// Test pour démontrer la recherche multi-champs dans /users/search
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

echo "=== TEST RECHERCHE MULTI-CHAMPS ===\n\n";

// 1. Créer plusieurs utilisateurs de test avec des noms différents
echo "🔐 CRÉATION UTILISATEURS DE TEST\n";
echo "================================\n";

$users = [
    ['email' => 'john.doe@example.com', 'firstname' => 'John', 'lastname' => 'Doe'],
    ['email' => 'jane.smith@example.com', 'firstname' => 'Jane', 'lastname' => 'Smith'],
    ['email' => 'bob.wilson@example.com', 'firstname' => 'Bob', 'lastname' => 'Wilson'],
    ['email' => 'alice.johnson@example.com', 'firstname' => 'Alice', 'lastname' => 'Johnson'],
    ['email' => 'gued.as76@hotmail.com', 'firstname' => 'Gued', 'lastname' => 'Test']
];

foreach ($users as $index => $user) {
    $createResult = testEndpoint('POST', '/register', [
        'email' => $user['email'],
        'password' => 'password123',
        'firstname' => $user['firstname'],
        'lastname' => $user['lastname'],
        'device_name' => 'test-device'
    ], null, "Créer utilisateur " . ($index + 1) . ": " . $user['firstname'] . " " . $user['lastname']);
}

// 2. Créer un utilisateur pour faire la recherche
echo "\n🔐 CRÉATION UTILISATEUR POUR RECHERCHE\n";
echo "======================================\n";

$createSearcherResult = testEndpoint('POST', '/register', [
    'email' => 'searcher@example.com',
    'password' => 'password123',
    'firstname' => 'Searcher',
    'lastname' => 'User',
    'device_name' => 'test-device'
], null, 'Créer un utilisateur pour faire la recherche');

// 3. Login avec l'utilisateur de recherche
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

// 4. Tests de recherche par prénom
echo "🔍 TEST RECHERCHE PAR PRÉNOM\n";
echo "============================\n";

$searchfirstnameResult = testEndpoint('GET', '/users/search?q=John', null, $token, 'Rechercher par prénom "John"');

$searchfirstnameData = json_decode($searchfirstnameResult['response'], true);
if (isset($searchfirstnameData['data'])) {
    echo "   Nombre de résultats: " . count($searchfirstnameData['data']) . "\n";
    foreach ($searchfirstnameData['data'] as $user) {
        echo "   - " . $user['firstname'] . " " . $user['lastname'] . " (" . $user['email'] . ")\n";
    }
} else {
    echo "   Aucun résultat trouvé\n";
}
echo "\n";

// 5. Tests de recherche par nom
echo "🔍 TEST RECHERCHE PAR NOM\n";
echo "==========================\n";

$searchlastnameResult = testEndpoint('GET', '/users/search?q=Doe', null, $token, 'Rechercher par nom "Doe"');

$searchlastnameData = json_decode($searchlastnameResult['response'], true);
if (isset($searchlastnameData['data'])) {
    echo "   Nombre de résultats: " . count($searchlastnameData['data']) . "\n";
    foreach ($searchlastnameData['data'] as $user) {
        echo "   - " . $user['firstname'] . " " . $user['lastname'] . " (" . $user['email'] . ")\n";
    }
} else {
    echo "   Aucun résultat trouvé\n";
}
echo "\n";

// 6. Tests de recherche par email complet
echo "🔍 TEST RECHERCHE PAR EMAIL COMPLET\n";
echo "===================================\n";

$searchEmailResult = testEndpoint('GET', '/users/search?q=gued.as76@hotmail.com', null, $token, 'Rechercher par email complet');

$searchEmailData = json_decode($searchEmailResult['response'], true);
if (isset($searchEmailData['data'])) {
    echo "   Nombre de résultats: " . count($searchEmailData['data']) . "\n";
    foreach ($searchEmailData['data'] as $user) {
        echo "   - " . $user['firstname'] . " " . $user['lastname'] . " (" . $user['email'] . ")\n";
    }
} else {
    echo "   Aucun résultat trouvé\n";
}
echo "\n";

// 7. Tests de recherche par partie d'email
echo "🔍 TEST RECHERCHE PAR PARTIE D'EMAIL\n";
echo "====================================\n";

$searchPartialEmailResult = testEndpoint('GET', '/users/search?q=gued.as76', null, $token, 'Rechercher par partie d\'email "gued.as76"');

$searchPartialEmailData = json_decode($searchPartialEmailResult['response'], true);
if (isset($searchPartialEmailData['data'])) {
    echo "   Nombre de résultats: " . count($searchPartialEmailData['data']) . "\n";
    foreach ($searchPartialEmailData['data'] as $user) {
        echo "   - " . $user['firstname'] . " " . $user['lastname'] . " (" . $user['email'] . ")\n";
    }
} else {
    echo "   Aucun résultat trouvé\n";
}
echo "\n";

// 8. Tests de recherche par domaine email
echo "🔍 TEST RECHERCHE PAR DOMAINE EMAIL\n";
echo "===================================\n";

$searchDomainResult = testEndpoint('GET', '/users/search?q=example.com', null, $token, 'Rechercher par domaine "example.com"');

$searchDomainData = json_decode($searchDomainResult['response'], true);
if (isset($searchDomainData['data'])) {
    echo "   Nombre de résultats: " . count($searchDomainData['data']) . "\n";
    foreach ($searchDomainData['data'] as $user) {
        echo "   - " . $user['firstname'] . " " . $user['lastname'] . " (" . $user['email'] . ")\n";
    }
} else {
    echo "   Aucun résultat trouvé\n";
}
echo "\n";

// 9. Tests de recherche par terme commun
echo "🔍 TEST RECHERCHE PAR TERME COMMUN\n";
echo "===================================\n";

$searchCommonResult = testEndpoint('GET', '/users/search?q=john', null, $token, 'Rechercher par terme "john" (prénom et nom)');

$searchCommonData = json_decode($searchCommonResult['response'], true);
if (isset($searchCommonData['data'])) {
    echo "   Nombre de résultats: " . count($searchCommonData['data']) . "\n";
    foreach ($searchCommonData['data'] as $user) {
        echo "   - " . $user['firstname'] . " " . $user['lastname'] . " (" . $user['email'] . ")\n";
    }
} else {
    echo "   Aucun résultat trouvé\n";
}
echo "\n";

// 10. Vérifier que l'utilisateur connecté n'apparaît pas
echo "🔍 VÉRIFICATION EXCLUSION UTILISATEUR CONNECTÉ\n";
echo "==============================================\n";

$searchSelfResult = testEndpoint('GET', '/users/search?q=searcher', null, $token, 'Rechercher l\'utilisateur connecté "searcher"');

$searchSelfData = json_decode($searchSelfResult['response'], true);
if (isset($searchSelfData['data'])) {
    echo "   Nombre de résultats: " . count($searchSelfData['data']) . "\n";
    foreach ($searchSelfData['data'] as $user) {
        echo "   - " . $user['firstname'] . " " . $user['lastname'] . " (" . $user['email'] . ")\n";
    }
} else {
    echo "   Aucun résultat trouvé (correct - l'utilisateur connecté est exclu)\n";
}

echo "\n=== FIN DU TEST ===\n";
