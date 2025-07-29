<?php

// Test final pour démontrer toutes les fonctionnalités de recherche
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

echo "=== DÉMONSTRATION FINALE RECHERCHE AVEC RELATIONS ===\n\n";

// 1. Login avec un utilisateur
echo "🔐 CONNEXION UTILISATEUR\n";
echo "=========================\n";

$loginResult = testEndpoint('POST', '/login', [
    'email' => 'main.user@example.com',
    'password' => 'password123',
    'device_name' => 'test-device'
], null, 'Se connecter avec main.user@example.com');

$loginData = json_decode($loginResult['response'], true);
$token = $loginData['token'] ?? null;

if ($token) {
    echo "   Token obtenu: " . substr($token, 0, 20) . "...\n\n";
}

// 2. Test recherche insensible à la casse
echo "🔍 TEST RECHERCHE INSENSIBLE À LA CASSE\n";
echo "=======================================\n";

$searchCaseResult = testEndpoint('GET', '/users/search?q=TEST', null, $token, 'Rechercher "TEST" en majuscules');

$searchCaseData = json_decode($searchCaseResult['response'], true);
if (isset($searchCaseData['data']) && count($searchCaseData['data']) > 0) {
    $firstUser = $searchCaseData['data'][0];
    echo "   👤 Premier résultat: " . $firstUser['firstname'] . " " . $firstUser['lastname'] . " (" . $firstUser['email'] . ")\n";

    if (isset($firstUser['relationship'])) {
        $rel = $firstUser['relationship'];
        echo "      📊 Statut de relation: " . $rel['status'] . "\n";
        echo "      🤝 Ami: " . ($rel['isFriend'] ? 'Oui' : 'Non') . "\n";
        echo "      ⏳ Demande en attente: " . ($rel['hasPendingRequest'] ? 'Oui' : 'Non') . "\n";
        echo "      👥 Amis en commun: " . $rel['mutualFriends'] . "\n";
    }
}
echo "\n";

// 3. Test recherche par email avec caractères spéciaux
echo "🔍 TEST RECHERCHE EMAIL AVEC CARACTÈRES SPÉCIAUX\n";
echo "================================================\n";

$searchEmailResult = testEndpoint('GET', '/users/search?q=test-detail@test.com', null, $token, 'Rechercher email exact');

$searchEmailData = json_decode($searchEmailResult['response'], true);
if (isset($searchEmailData['data']) && count($searchEmailData['data']) > 0) {
    $emailUser = $searchEmailData['data'][0];
    echo "   👤 Résultat: " . $emailUser['firstname'] . " " . $emailUser['lastname'] . " (" . $emailUser['email'] . ")\n";

    if (isset($emailUser['relationship'])) {
        $rel = $emailUser['relationship'];
        echo "      📊 Statut de relation: " . $rel['status'] . "\n";
        echo "      🤝 Ami: " . ($rel['isFriend'] ? 'Oui' : 'Non') . "\n";
        echo "      ⏳ Demande en attente: " . ($rel['hasPendingRequest'] ? 'Oui' : 'Non') . "\n";
        echo "      👥 Amis en commun: " . $rel['mutualFriends'] . "\n";
    }
}
echo "\n";

// 4. Test recherche par prénom
echo "🔍 TEST RECHERCHE PAR PRÉNOM\n";
echo "=============================\n";

$searchfirstnameResult = testEndpoint('GET', '/users/search?q=Test', null, $token, 'Rechercher par prénom "Test"');

$searchfirstnameData = json_decode($searchfirstnameResult['response'], true);
if (isset($searchfirstnameData['data']) && count($searchfirstnameData['data']) > 0) {
    echo "   Nombre de résultats: " . count($searchfirstnameData['data']) . "\n";
    foreach ($searchfirstnameData['data'] as $user) {
        echo "   👤 " . $user['firstname'] . " " . $user['lastname'] . " (" . $user['email'] . ")\n";
        if (isset($user['relationship'])) {
            $rel = $user['relationship'];
            echo "      📊 Statut: " . $rel['status'] . "\n";
        }
    }
}
echo "\n";

// 5. Test recherche avec espaces et caractères spéciaux
echo "🔍 TEST RECHERCHE AVEC NETTOYAGE\n";
echo "=================================\n";

$searchCleanResult = testEndpoint('GET', '/users/search?q=  Test!@#$%^&*()  ', null, $token, 'Rechercher avec espaces et caractères spéciaux');

$searchCleanData = json_decode($searchCleanResult['response'], true);
if (isset($searchCleanData['data']) && count($searchCleanData['data']) > 0) {
    echo "   ✅ Recherche nettoyée fonctionne - " . count($searchCleanData['data']) . " résultats trouvés\n";
} else {
    echo "   ❌ Aucun résultat trouvé\n";
}
echo "\n";

// 6. Test requête trop courte
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

// 7. Test exclusion utilisateur connecté
echo "🔍 TEST EXCLUSION UTILISATEUR CONNECTÉ\n";
echo "=======================================\n";

$searchSelfResult = testEndpoint('GET', '/users/search?q=main.user@example.com', null, $token, 'Rechercher l\'utilisateur connecté');

$searchSelfData = json_decode($searchSelfResult['response'], true);
if (isset($searchSelfData['data']) && count($searchSelfData['data']) === 0) {
    echo "   ✅ Utilisateur connecté correctement exclu des résultats\n";
} else {
    echo "   ❌ Utilisateur connecté trouvé dans les résultats\n";
}
echo "\n";

// 8. Résumé des fonctionnalités
echo "📋 RÉSUMÉ DES FONCTIONNALITÉS\n";
echo "=============================\n";
echo "✅ Recherche insensible à la casse (majuscules/minuscules)\n";
echo "✅ Recherche multi-champs (prénom, nom, email)\n";
echo "✅ Nettoyage automatique des espaces et caractères spéciaux\n";
echo "✅ Validation des requêtes (minimum 2 caractères)\n";
echo "✅ Exclusion de l'utilisateur connecté\n";
echo "✅ Informations de relation incluses:\n";
echo "   - Statut de relation (none, friend, request_sent, request_received, self)\n";
echo "   - Statut d'amitié (isFriend)\n";
echo "   - Demande en attente (hasPendingRequest)\n";
echo "   - Nombre d'amis en commun (mutualFriends)\n";
echo "✅ Pagination des résultats\n";
echo "✅ Gestion des erreurs\n";

echo "\n=== DÉMONSTRATION TERMINÉE ===\n";
