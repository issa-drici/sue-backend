<?php

// Test spécifique pour la recherche de gued.as76@hotmail.com
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

echo "=== TEST RECHERCHE GUED.AS76@HOTMAIL.COM ===\n\n";

// 1. Créer un utilisateur pour faire la recherche
echo "🔐 CRÉATION UTILISATEUR POUR RECHERCHE\n";
echo "======================================\n";

$createSearcherResult = testEndpoint('POST', '/register', [
    'email' => 'searcher4@example.com',
    'password' => 'password123',
    'firstname' => 'Searcher',
    'lastname' => 'Four',
    'device_name' => 'test-device'
], null, 'Créer un utilisateur pour faire la recherche');

// 2. Login avec l'utilisateur de recherche
$loginResult = testEndpoint('POST', '/login', [
    'email' => 'searcher4@example.com',
    'password' => 'password123',
    'device_name' => 'test-device'
], null, 'Se connecter avec l\'utilisateur de recherche');

$loginData = json_decode($loginResult['response'], true);
$token = $loginData['token'] ?? null;

if ($token) {
    echo "   Token obtenu: " . substr($token, 0, 20) . "...\n\n";
}

// 3. Test recherche email exact
echo "🔍 TEST RECHERCHE EMAIL EXACT\n";
echo "=============================\n";

$searchExactResult = testEndpoint('GET', '/users/search?q=gued.as76@hotmail.com', null, $token, 'Rechercher email exact');

$searchExactData = json_decode($searchExactResult['response'], true);
if (isset($searchExactData['data'])) {
    echo "   Nombre de résultats: " . count($searchExactData['data']) . "\n";
    foreach ($searchExactData['data'] as $user) {
        echo "   - " . $user['firstname'] . " " . $user['lastname'] . " (" . $user['email'] . ")\n";
    }
} else {
    echo "   Aucun résultat trouvé\n";
}
echo "\n";

// 4. Test recherche email en majuscules
echo "🔍 TEST RECHERCHE EMAIL MAJUSCULES\n";
echo "==================================\n";

$searchUpperResult = testEndpoint('GET', '/users/search?q=GUED.AS76@HOTMAIL.COM', null, $token, 'Rechercher email en majuscules');

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

// 5. Test recherche partie email
echo "🔍 TEST RECHERCHE PARTIE EMAIL\n";
echo "==============================\n";

$searchPartialResult = testEndpoint('GET', '/users/search?q=gued.as76', null, $token, 'Rechercher partie email');

$searchPartialData = json_decode($searchPartialResult['response'], true);
if (isset($searchPartialData['data'])) {
    echo "   Nombre de résultats: " . count($searchPartialData['data']) . "\n";
    foreach ($searchPartialData['data'] as $user) {
        echo "   - " . $user['firstname'] . " " . $user['lastname'] . " (" . $user['email'] . ")\n";
    }
} else {
    echo "   Aucun résultat trouvé\n";
}
echo "\n";

// 6. Test recherche par prénom
echo "🔍 TEST RECHERCHE PAR PRÉNOM\n";
echo "============================\n";

$searchfirstnameResult = testEndpoint('GET', '/users/search?q=Gued', null, $token, 'Rechercher par prénom');

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

// 7. Test recherche par prénom en minuscules
echo "🔍 TEST RECHERCHE PRÉNOM MINUSCULES\n";
echo "===================================\n";

$searchfirstnameLowerResult = testEndpoint('GET', '/users/search?q=gued', null, $token, 'Rechercher par prénom en minuscules');

$searchfirstnameLowerData = json_decode($searchfirstnameLowerResult['response'], true);
if (isset($searchfirstnameLowerData['data'])) {
    echo "   Nombre de résultats: " . count($searchfirstnameLowerData['data']) . "\n";
    foreach ($searchfirstnameLowerData['data'] as $user) {
        echo "   - " . $user['firstname'] . " " . $user['lastname'] . " (" . $user['email'] . ")\n";
    }
} else {
    echo "   Aucun résultat trouvé\n";
}
echo "\n";

// 8. Test recherche par nom
echo "🔍 TEST RECHERCHE PAR NOM\n";
echo "==========================\n";

$searchlastnameResult = testEndpoint('GET', '/users/search?q=Test', null, $token, 'Rechercher par nom');

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

// 9. Test recherche par domaine email
echo "🔍 TEST RECHERCHE PAR DOMAINE EMAIL\n";
echo "===================================\n";

$searchDomainResult = testEndpoint('GET', '/users/search?q=hotmail.com', null, $token, 'Rechercher par domaine email');

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

// 10. Test recherche avec espaces et caractères spéciaux
echo "🔍 TEST RECHERCHE AVEC ESPACES ET CARACTÈRES SPÉCIAUX\n";
echo "====================================================\n";

$searchMessyResult = testEndpoint('GET', '/users/search?q=  gued.as76@hotmail.com!@#$%  ', null, $token, 'Rechercher avec espaces et caractères spéciaux');

$searchMessyData = json_decode($searchMessyResult['response'], true);
if (isset($searchMessyData['data'])) {
    echo "   Nombre de résultats: " . count($searchMessyData['data']) . "\n";
    foreach ($searchMessyData['data'] as $user) {
        echo "   - " . $user['firstname'] . " " . $user['lastname'] . " (" . $user['email'] . ")\n";
    }
} else {
    echo "   Aucun résultat trouvé\n";
}

echo "\n=== FIN DU TEST ===\n";
