<?php

// Test pour démontrer les améliorations de la recherche
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

echo "=== TEST AMÉLIORATIONS RECHERCHE ===\n\n";

// 1. Créer un utilisateur de test
echo "🔐 CRÉATION UTILISATEUR DE TEST\n";
echo "===============================\n";

$createUserResult = testEndpoint('POST', '/register', [
    'email' => 'john.doe@example.com',
    'password' => 'password123',
    'firstname' => 'John',
    'lastname' => 'Doe',
    'device_name' => 'test-device'
], null, 'Créer un utilisateur de test');

// 2. Créer un utilisateur pour la recherche
echo "🔐 CRÉATION UTILISATEUR POUR RECHERCHE\n";
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

// 4. Test insensible à la casse - MAJUSCULES
echo "🔍 TEST INSENSIBLE À LA CASSE - MAJUSCULES\n";
echo "==========================================\n";

$searchUpperResult = testEndpoint('GET', '/users/search?q=JOHN', null, $token, 'Rechercher "JOHN" en majuscules');

$searchUpperData = json_decode($searchUpperResult['response'], true);
if (isset($searchUpperData['data'])) {
    echo "   Nombre de résultats: " . count($searchUpperData['data']) . "\n";
    foreach ($searchUpperData['data'] as $user) {
        echo "   - " . $user['firstname'] . " " . $user['lastname'] . " (" . $user['email'] . ")\n";
    }
} else {
    echo "   Aucun résultat trouvé\n";
}
echo "\n";

// 5. Test insensible à la casse - minuscules
echo "🔍 TEST INSENSIBLE À LA CASSE - MINUSCULES\n";
echo "==========================================\n";

$searchLowerResult = testEndpoint('GET', '/users/search?q=john', null, $token, 'Rechercher "john" en minuscules');

$searchLowerData = json_decode($searchLowerResult['response'], true);
if (isset($searchLowerData['data'])) {
    echo "   Nombre de résultats: " . count($searchLowerData['data']) . "\n";
    foreach ($searchLowerData['data'] as $user) {
        echo "   - " . $user['firstname'] . " " . $user['lastname'] . " (" . $user['email'] . ")\n";
    }
} else {
    echo "   Aucun résultat trouvé\n";
}
echo "\n";

// 6. Test insensible à la casse - mélangé
echo "🔍 TEST INSENSIBLE À LA CASSE - MÉLANGÉ\n";
echo "========================================\n";

$searchMixedResult = testEndpoint('GET', '/users/search?q=JoHn', null, $token, 'Rechercher "JoHn" en mélangé');

$searchMixedData = json_decode($searchMixedResult['response'], true);
if (isset($searchMixedData['data'])) {
    echo "   Nombre de résultats: " . count($searchMixedData['data']) . "\n";
    foreach ($searchMixedData['data'] as $user) {
        echo "   - " . $user['firstname'] . " " . $user['lastname'] . " (" . $user['email'] . ")\n";
    }
} else {
    echo "   Aucun résultat trouvé\n";
}
echo "\n";

// 7. Test nettoyage des espaces - espaces multiples
echo "🔍 TEST NETTOYAGE ESPACES - ESPACES MULTIPLES\n";
echo "=============================================\n";

$searchSpacesResult = testEndpoint('GET', '/users/search?q=  John   Doe  ', null, $token, 'Rechercher avec espaces multiples');

$searchSpacesData = json_decode($searchSpacesResult['response'], true);
if (isset($searchSpacesData['data'])) {
    echo "   Nombre de résultats: " . count($searchSpacesData['data']) . "\n";
    foreach ($searchSpacesData['data'] as $user) {
        echo "   - " . $user['firstname'] . " " . $user['lastname'] . " (" . $user['email'] . ")\n";
    }
} else {
    echo "   Aucun résultat trouvé\n";
}
echo "\n";

// 8. Test nettoyage des caractères spéciaux
echo "🔍 TEST NETTOYAGE CARACTÈRES SPÉCIAUX\n";
echo "======================================\n";

$searchSpecialResult = testEndpoint('GET', '/users/search?q=John!@#$%^&*()', null, $token, 'Rechercher avec caractères spéciaux');

$searchSpecialData = json_decode($searchSpecialResult['response'], true);
if (isset($searchSpecialData['data'])) {
    echo "   Nombre de résultats: " . count($searchSpecialData['data']) . "\n";
    foreach ($searchSpecialData['data'] as $user) {
        echo "   - " . $user['firstname'] . " " . $user['lastname'] . " (" . $user['email'] . ")\n";
    }
} else {
    echo "   Aucun résultat trouvé\n";
}
echo "\n";

// 9. Test email avec caractères spéciaux autorisés
echo "🔍 TEST EMAIL AVEC CARACTÈRES AUTORISÉS\n";
echo "========================================\n";

$searchEmailResult = testEndpoint('GET', '/users/search?q=john.doe@example.com', null, $token, 'Rechercher email avec points et @');

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

// 10. Test requête trop courte
echo "🔍 TEST REQUÊTE TROP COURTE\n";
echo "============================\n";

$searchShortResult = testEndpoint('GET', '/users/search?q=a', null, $token, 'Rechercher avec un seul caractère');

$searchShortData = json_decode($searchShortResult['response'], true);
if (isset($searchShortData['success']) && !$searchShortData['success']) {
    echo "   ✅ Erreur attendue: " . $searchShortData['error']['message'] . "\n";
} else {
    echo "   ❌ Erreur non détectée\n";
}
echo "\n";

// 11. Test requête vide
echo "🔍 TEST REQUÊTE VIDE\n";
echo "====================\n";

$searchEmptyResult = testEndpoint('GET', '/users/search?q=', null, $token, 'Rechercher avec requête vide');

$searchEmptyData = json_decode($searchEmptyResult['response'], true);
if (isset($searchEmptyData['success']) && !$searchEmptyData['success']) {
    echo "   ✅ Erreur attendue: " . $searchEmptyData['error']['message'] . "\n";
} else {
    echo "   ❌ Erreur non détectée\n";
}
echo "\n";

// 12. Test recherche par nom avec casse différente
echo "🔍 TEST RECHERCHE NOM AVEC CASSE DIFFÉRENTE\n";
echo "===========================================\n";

$searchNameResult = testEndpoint('GET', '/users/search?q=DOE', null, $token, 'Rechercher nom "DOE" en majuscules');

$searchNameData = json_decode($searchNameResult['response'], true);
if (isset($searchNameData['data'])) {
    echo "   Nombre de résultats: " . count($searchNameData['data']) . "\n";
    foreach ($searchNameData['data'] as $user) {
        echo "   - " . $user['firstname'] . " " . $user['lastname'] . " (" . $user['email'] . ")\n";
    }
} else {
    echo "   Aucun résultat trouvé\n";
}

echo "\n=== FIN DU TEST ===\n";
