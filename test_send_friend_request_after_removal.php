<?php

// Test pour vérifier qu'on peut envoyer une demande d'ami après suppression
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

echo "=== TEST ENVOI DE DEMANDE D'AMI APRÈS SUPPRESSION ===\n\n";

// 1. Login avec l'utilisateur principal
echo "🔐 LOGIN UTILISATEUR PRINCIPAL\n";
echo "==============================\n";

$loginResult = testEndpoint('POST', '/login', [
    'email' => 'driciissa76@gmail.com',
    'password' => 'Asmaa1997!',
    'device_name' => 'test-device'
], null, 'Se connecter avec driciissa76@gmail.com');

$loginData = json_decode($loginResult['response'], true);
$token = $loginData['token'] ?? null;
$userId = $loginData['user']['id'] ?? null;

if ($token) {
    echo "   ✅ Token obtenu: " . substr($token, 0, 20) . "...\n";
    echo "   📊 ID utilisateur: $userId\n";
    echo "   📊 Email: " . $loginData['user']['email'] . "\n\n";
} else {
    echo "   ❌ Échec de la connexion\n";
    echo "   Réponse: " . $loginResult['response'] . "\n\n";
    exit;
}

// 2. Vérifier l'état actuel des demandes d'ami
echo "🔍 ÉTAT ACTUEL DES DEMANDES D'AMI\n";
echo "=================================\n";

$searchResult = testEndpoint('GET', '/users/search?q=Asmaa', [], $token, 'Rechercher Asmaa pour voir le statut');

$searchData = json_decode($searchResult['response'], true);

if (isset($searchData['data']) && count($searchData['data']) > 0) {
    $asmaaUser = null;
    foreach ($searchData['data'] as $user) {
        if (strpos($user['email'], 'gued.as76@hotmail.com') !== false) {
            $asmaaUser = $user;
            break;
        }
    }

    if ($asmaaUser) {
        echo "   📊 Utilisateur trouvé: " . $asmaaUser['firstname'] . " " . $asmaaUser['lastname'] . "\n";
        echo "   📊 ID: " . $asmaaUser['id'] . "\n";
        echo "   📊 Statut relation: " . $asmaaUser['relationship']['status'] . "\n";
        echo "   📊 hasPendingRequest: " . ($asmaaUser['relationship']['hasPendingRequest'] ? 'true' : 'false') . "\n";
        echo "   📊 isFriend: " . ($asmaaUser['relationship']['isFriend'] ? 'true' : 'false') . "\n";
    } else {
        echo "   ❌ Asmaa non trouvée dans les résultats\n";
        exit;
    }
} else {
    echo "   ❌ Aucun utilisateur trouvé\n";
    exit;
}

echo "\n";

// 3. Tenter d'envoyer une demande d'ami
echo "🤝 ENVOI DE DEMANDE D'AMI\n";
echo "=========================\n";

$sendRequestResult = testEndpoint('POST', '/users/friend-requests', [
    'userId' => $asmaaUser['id']
], $token, 'Envoyer une demande d\'ami à Asmaa');

$sendRequestData = json_decode($sendRequestResult['response'], true);

if (isset($sendRequestData['success']) && $sendRequestData['success']) {
    echo "   ✅ Demande d'ami envoyée avec succès\n";
    echo "   📊 Message: " . $sendRequestData['message'] . "\n";
    if (isset($sendRequestData['data'])) {
        echo "   📊 ID ami: " . $sendRequestData['data']['id'] . "\n";
        echo "   📊 Nom: " . $sendRequestData['data']['firstname'] . " " . $sendRequestData['data']['lastname'] . "\n";
        echo "   📊 Amis en commun: " . $sendRequestData['data']['mutualFriends'] . "\n";
    }
} else {
    echo "   ❌ Échec de l'envoi de la demande\n";
    echo "   📊 Code d'erreur: " . ($sendRequestData['error']['code'] ?? 'N/A') . "\n";
    echo "   📊 Message: " . ($sendRequestData['error']['message'] ?? 'N/A') . "\n";
    echo "   📊 Code HTTP: " . $sendRequestResult['code'] . "\n";
}

echo "\n";

// 4. Vérifier le nouveau statut
echo "🔍 VÉRIFICATION DU NOUVEAU STATUT\n";
echo "=================================\n";

$searchAfterResult = testEndpoint('GET', '/users/search?q=Asmaa', [], $token, 'Rechercher Asmaa après envoi');

$searchAfterData = json_decode($searchAfterResult['response'], true);

if (isset($searchAfterData['data']) && count($searchAfterData['data']) > 0) {
    $asmaaUserAfter = null;
    foreach ($searchAfterData['data'] as $user) {
        if (strpos($user['email'], 'gued.as76@hotmail.com') !== false) {
            $asmaaUserAfter = $user;
            break;
        }
    }

    if ($asmaaUserAfter) {
        echo "   📊 Nouveau statut relation: " . $asmaaUserAfter['relationship']['status'] . "\n";
        echo "   📊 hasPendingRequest: " . ($asmaaUserAfter['relationship']['hasPendingRequest'] ? 'true' : 'false') . "\n";
        echo "   📊 isFriend: " . ($asmaaUserAfter['relationship']['isFriend'] ? 'true' : 'false') . "\n";

        if ($asmaaUserAfter['relationship']['status'] === 'pending' && $asmaaUserAfter['relationship']['hasPendingRequest'] === true) {
            echo "   ✅ Statut correct après envoi de demande\n";
        } else {
            echo "   ❌ Statut incorrect après envoi de demande\n";
        }
    }
} else {
    echo "   ❌ Asmaa non trouvée après envoi\n";
}

echo "\n";

// 5. Vérification dans la base de données
echo "🔍 VÉRIFICATION BASE DE DONNÉES\n";
echo "===============================\n";

$dbCheckCommand = "php artisan tinker --execute=\"echo 'État des demandes d\\'ami:' . PHP_EOL; \$user1 = \\App\\Models\\UserModel::where('email', 'driciissa76@gmail.com')->first(); \$user2 = \\App\\Models\\UserModel::where('email', 'gued.as76@hotmail.com')->first(); if (\$user1 && \$user2) { \$requests = \\App\\Models\\FriendRequestModel::where(function(\$q) use (\$user1, \$user2) { \$q->where('sender_id', \$user1->id)->where('receiver_id', \$user2->id); })->orWhere(function(\$q) use (\$user1, \$user2) { \$q->where('sender_id', \$user2->id)->where('receiver_id', \$user1->id); })->get(); echo 'Demandes d\\'ami trouvées: ' . \$requests->count() . PHP_EOL; foreach(\$requests as \$req) { echo '  - ID: ' . \$req->id . ', De: ' . \$req->sender_id . ' vers: ' . \$req->receiver_id . ', Statut: ' . \$req->status . ', Annulée: ' . (\$req->cancelled_at ? 'Oui' . ' (' . \$req->cancelled_at . ')' : 'Non') . PHP_EOL; } } else { echo 'Utilisateurs non trouvés' . PHP_EOL; }\"";
system($dbCheckCommand);

echo "\n";

// 6. Résumé du test
echo "🎯 RÉSUMÉ DU TEST\n";
echo "=================\n";

$testPassed = false;

if (isset($sendRequestData['success']) && $sendRequestData['success']) {
    echo "   ✅ Test réussi: Demande d'ami envoyée après suppression\n";
    echo "   ✅ Le problème de l'erreur 409 est résolu\n";
    $testPassed = true;
} else {
    echo "   ❌ Test échoué: Impossible d'envoyer la demande d'ami\n";
    echo "   ❌ L'erreur 409 persiste\n";
}

echo "\n";

if ($testPassed) {
    echo "🎉 LE PROBLÈME EST RÉSOLU ! Vous pouvez maintenant envoyer des demandes d'ami après avoir supprimé un ami.\n";
} else {
    echo "❌ LE PROBLÈME PERSISTE. Vérifiez l'implémentation.\n";
}

echo "\n=== FIN DU TEST ===\n";
