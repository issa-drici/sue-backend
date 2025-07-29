<?php

// Test de débogage pour la recherche d'utilisateurs
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

echo "=== DÉBOGAGE RECHERCHE UTILISATEURS ===\n\n";

// 1. Créer un utilisateur de test simple
echo "🔐 CRÉATION UTILISATEUR DE TEST\n";
echo "===============================\n";

$createUserResult = testEndpoint('POST', '/register', [
    'email' => 'testsearch@example.com',
    'password' => 'password123',
    'firstname' => 'Test',
    'lastname' => 'Search',
    'device_name' => 'test-device'
], null, 'Créer un utilisateur de test');

// 2. Créer un utilisateur pour faire la recherche
echo "🔐 CRÉATION UTILISATEUR POUR RECHERCHE\n";
echo "======================================\n";

$createSearcherResult = testEndpoint('POST', '/register', [
    'email' => 'searcher2@example.com',
    'password' => 'password123',
    'firstname' => 'Searcher',
    'lastname' => 'Two',
    'device_name' => 'test-device'
], null, 'Créer un utilisateur pour faire la recherche');

// 3. Login avec l'utilisateur de recherche
$loginResult = testEndpoint('POST', '/login', [
    'email' => 'searcher2@example.com',
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

$searchResult = testEndpoint('GET', '/users/search?q=testsearch@example.com', null, $token, 'Rechercher avec l\'email complet');

$searchData = json_decode($searchResult['response'], true);
echo "   Réponse complète: " . $searchResult['response'] . "\n";

if (isset($searchData['data'])) {
    echo "   Nombre de résultats: " . count($searchData['data']) . "\n";
    foreach ($searchData['data'] as $user) {
        echo "   - Utilisateur trouvé: " . $user['email'] . " (" . $user['firstname'] . " " . $user['lastname'] . ")\n";
    }
} else {
    echo "   Aucun résultat trouvé\n";
    if (isset($searchData['error'])) {
        echo "   Erreur: " . json_encode($searchData['error']) . "\n";
    }
}
echo "\n";

// 5. Tester la recherche avec une partie de l'email
echo "🔍 TEST RECHERCHE AVEC PARTIE EMAIL\n";
echo "====================================\n";

$searchPartialResult = testEndpoint('GET', '/users/search?q=testsearch', null, $token, 'Rechercher avec une partie de l\'email');

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

$searchfirstnameResult = testEndpoint('GET', '/users/search?q=Test', null, $token, 'Rechercher avec le prénom');

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

$searchlastnameResult = testEndpoint('GET', '/users/search?q=Search', null, $token, 'Rechercher avec le nom');

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

// 8. Tester la recherche avec un terme qui devrait retourner plusieurs résultats
echo "🔍 TEST RECHERCHE AVEC TERME COMMUN\n";
echo "====================================\n";

$searchCommonResult = testEndpoint('GET', '/users/search?q=test', null, $token, 'Rechercher avec un terme commun');

$searchCommonData = json_decode($searchCommonResult['response'], true);
if (isset($searchCommonData['data'])) {
    echo "   Nombre de résultats: " . count($searchCommonData['data']) . "\n";
    foreach ($searchCommonData['data'] as $user) {
        echo "   - Utilisateur trouvé: " . $user['email'] . " (" . $user['firstname'] . " " . $user['lastname'] . ")\n";
    }
} else {
    echo "   Aucun résultat trouvé\n";
}
echo "\n";

// 9. Vérifier la structure de la réponse
echo "🔍 VÉRIFICATION STRUCTURE RÉPONSE\n";
echo "==================================\n";

echo "   Réponse brute: " . $searchResult['response'] . "\n";
echo "   Code HTTP: " . $searchResult['code'] . "\n";

echo "\n=== FIN DU DÉBOGAGE ===\n";
