<?php

// Test pour vérifier que la recherche de gued.as76@hotmail.com fonctionne
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

echo "=== TEST RECHERCHE GUED.AS76@HOTMAIL.COM CORRIGÉE ===\n\n";

// 1. Créer un utilisateur pour faire la recherche
echo "🔐 CRÉATION UTILISATEUR POUR RECHERCHE\n";
echo "======================================\n";

$createSearcherResult = testEndpoint('POST', '/register', [
    'email' => 'searcher5@example.com',
    'password' => 'password123',
    'firstname' => 'Searcher',
    'lastname' => 'Five',
    'device_name' => 'test-device'
], null, 'Créer un utilisateur pour faire la recherche');

// 2. Login avec l'utilisateur de recherche
$loginResult = testEndpoint('POST', '/login', [
    'email' => 'searcher5@example.com',
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
        echo "   👤 " . $user['firstname'] . " " . $user['lastname'] . " (" . $user['email'] . ")\n";

        if (isset($user['relationship'])) {
            $rel = $user['relationship'];
            echo "      📊 Statut: " . $rel['status'] . "\n";
            echo "      🤝 Ami: " . ($rel['isFriend'] ? 'Oui' : 'Non') . "\n";
            echo "      ⏳ Demande en attente: " . ($rel['hasPendingRequest'] ? 'Oui' : 'Non') . "\n";
            echo "      👥 Amis en commun: " . $rel['mutualFriends'] . "\n";
        } else {
            echo "      ❌ Pas d'informations de relation\n";
        }
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
        echo "   👤 " . $user['firstname'] . " " . $user['lastname'] . " (" . $user['email'] . ")\n";

        if (isset($user['relationship'])) {
            $rel = $user['relationship'];
            echo "      📊 Statut: " . $rel['status'] . "\n";
            echo "      🤝 Ami: " . ($rel['isFriend'] ? 'Oui' : 'Non') . "\n";
            echo "      ⏳ Demande en attente: " . ($rel['hasPendingRequest'] ? 'Oui' : 'Non') . "\n";
            echo "      👥 Amis en commun: " . $rel['mutualFriends'] . "\n";
        }
    }
} else {
    echo "   Aucun résultat trouvé\n";
}
echo "\n";

// 5. Test recherche par prénom
echo "🔍 TEST RECHERCHE PAR PRÉNOM\n";
echo "=============================\n";

$searchfirstnameResult = testEndpoint('GET', '/users/search?q=Asmaa', null, $token, 'Rechercher par prénom "Asmaa"');

$searchfirstnameData = json_decode($searchfirstnameResult['response'], true);
if (isset($searchfirstnameData['data'])) {
    echo "   Nombre de résultats: " . count($searchfirstnameData['data']) . "\n";
    foreach ($searchfirstnameData['data'] as $user) {
        echo "   👤 " . $user['firstname'] . " " . $user['lastname'] . " (" . $user['email'] . ")\n";

        if (isset($user['relationship'])) {
            $rel = $user['relationship'];
            echo "      📊 Statut: " . $rel['status'] . "\n";
            echo "      🤝 Ami: " . ($rel['isFriend'] ? 'Oui' : 'Non') . "\n";
            echo "      ⏳ Demande en attente: " . ($rel['hasPendingRequest'] ? 'Oui' : 'Non') . "\n";
            echo "      👥 Amis en commun: " . $rel['mutualFriends'] . "\n";
        }
    }
} else {
    echo "   Aucun résultat trouvé\n";
}
echo "\n";

// 6. Test recherche par nom
echo "🔍 TEST RECHERCHE PAR NOM\n";
echo "==========================\n";

$searchlastnameResult = testEndpoint('GET', '/users/search?q=Guediri', null, $token, 'Rechercher par nom "Guediri"');

$searchlastnameData = json_decode($searchlastnameResult['response'], true);
if (isset($searchlastnameData['data'])) {
    echo "   Nombre de résultats: " . count($searchlastnameData['data']) . "\n";
    foreach ($searchlastnameData['data'] as $user) {
        echo "   👤 " . $user['firstname'] . " " . $user['lastname'] . " (" . $user['email'] . ")\n";

        if (isset($user['relationship'])) {
            $rel = $user['relationship'];
            echo "      📊 Statut: " . $rel['status'] . "\n";
            echo "      🤝 Ami: " . ($rel['isFriend'] ? 'Oui' : 'Non') . "\n";
            echo "      ⏳ Demande en attente: " . ($rel['hasPendingRequest'] ? 'Oui' : 'Non') . "\n";
            echo "      👥 Amis en commun: " . $rel['mutualFriends'] . "\n";
        }
    }
} else {
    echo "   Aucun résultat trouvé\n";
}

echo "\n=== FIN DU TEST ===\n";
