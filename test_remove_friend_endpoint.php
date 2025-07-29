<?php

// Test complet pour l'endpoint DELETE /api/users/friends/{friendId}
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

echo "=== TEST ENDPOINT DELETE /api/users/friends/{friendId} ===\n\n";

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

// 2. Vérifier la liste des amis avant suppression
echo "👥 LISTE DES AMIS AVANT SUPPRESSION\n";
echo "===================================\n";

$friendsBeforeResult = testEndpoint('GET', '/users/friends', [], $token, 'Récupérer la liste des amis avant suppression');

$friendsBeforeData = json_decode($friendsBeforeResult['response'], true);
$friendToRemove = null;

if (isset($friendsBeforeData['success']) && $friendsBeforeData['success']) {
    echo "   ✅ Requête réussie\n";
    echo "   📊 Nombre d'amis: " . count($friendsBeforeData['data']) . "\n";

    if (count($friendsBeforeData['data']) > 0) {
        $friendToRemove = $friendsBeforeData['data'][0];
        echo "   📋 Ami à supprimer:\n";
        echo "      ID: " . $friendToRemove['id'] . "\n";
        echo "      Nom: " . $friendToRemove['firstname'] . " " . $friendToRemove['lastname'] . "\n";
        echo "      Email: " . $friendToRemove['email'] . "\n";
    } else {
        echo "   ℹ️  Aucun ami trouvé, impossible de tester la suppression\n";
        exit;
    }
} else {
    echo "   ❌ Échec de la requête\n";
    echo "   Erreur: " . ($friendsBeforeData['error']['message'] ?? 'Erreur inconnue') . "\n";
    exit;
}

echo "\n";

// 3. Test 1 : Suppression d'ami réussie
echo "🗑️  TEST 1 : SUPPRESSION D'AMI RÉUSSIE\n";
echo "======================================\n";

$removeFriendResult = testEndpoint('DELETE', "/users/friends/{$friendToRemove['id']}", [], $token, 'Supprimer l\'ami');

$removeFriendData = json_decode($removeFriendResult['response'], true);

if (isset($removeFriendData['success']) && $removeFriendData['success']) {
    echo "   ✅ Suppression réussie\n";
    echo "   📊 ID ami supprimé: " . $removeFriendData['data']['removedFriendId'] . "\n";
    echo "   📊 Date de suppression: " . $removeFriendData['data']['removedAt'] . "\n";
    echo "   📊 Message: " . $removeFriendData['message'] . "\n";
} else {
    echo "   ❌ Échec de la suppression\n";
    echo "   Erreur: " . ($removeFriendData['error']['message'] ?? 'Erreur inconnue') . "\n";
    echo "   Code: " . ($removeFriendData['error']['code'] ?? 'N/A') . "\n";
}

echo "\n";

// 4. Vérifier que l'ami a bien été supprimé
echo "🔍 VÉRIFICATION SUPPRESSION\n";
echo "===========================\n";

$friendsAfterResult = testEndpoint('GET', '/users/friends', [], $token, 'Récupérer la liste des amis après suppression');

$friendsAfterData = json_decode($friendsAfterResult['response'], true);

if (isset($friendsAfterData['success']) && $friendsAfterData['success']) {
    echo "   ✅ Requête réussie\n";
    echo "   📊 Nombre d'amis après suppression: " . count($friendsAfterData['data']) . "\n";

    $friendStillExists = false;
    foreach ($friendsAfterData['data'] as $friend) {
        if ($friend['id'] === $friendToRemove['id']) {
            $friendStillExists = true;
            break;
        }
    }

    if (!$friendStillExists) {
        echo "   ✅ L'ami a bien été supprimé de la liste\n";
    } else {
        echo "   ❌ L'ami est toujours dans la liste\n";
    }
} else {
    echo "   ❌ Échec de la vérification\n";
    echo "   Erreur: " . ($friendsAfterData['error']['message'] ?? 'Erreur inconnue') . "\n";
}

echo "\n";

// 5. Test 2 : Tentative de suppression d'un non-ami
echo "🚫 TEST 2 : SUPPRESSION D'UN NON-AMI\n";
echo "====================================\n";

$nonFriendId = '9f6fd1d4-a6f6-4156-8c55-41c9c590896c'; // ID de l'ami qu'on vient de supprimer
$removeNonFriendResult = testEndpoint('DELETE', "/users/friends/$nonFriendId", [], $token, 'Tenter de supprimer un non-ami');

$removeNonFriendData = json_decode($removeNonFriendResult['response'], true);

if (isset($removeNonFriendData['success']) && !$removeNonFriendData['success']) {
    echo "   ✅ Erreur attendue\n";
    echo "   📊 Code d'erreur: " . ($removeNonFriendData['error']['code'] ?? 'N/A') . "\n";
    echo "   📊 Message: " . ($removeNonFriendData['error']['message'] ?? 'N/A') . "\n";
    echo "   📊 Code HTTP: " . $removeNonFriendResult['code'] . "\n";

    if ($removeNonFriendData['error']['code'] === 'FRIEND_NOT_FOUND') {
        echo "   ✅ Code d'erreur correct\n";
    } else {
        echo "   ❌ Code d'erreur incorrect\n";
    }
} else {
    echo "   ❌ La suppression aurait dû échouer\n";
}

echo "\n";

// 6. Test 3 : ID d'ami invalide
echo "❌ TEST 3 : ID D'AMI INVALIDE\n";
echo "=============================\n";

$invalidId = 'invalid-uuid';
$removeInvalidResult = testEndpoint('DELETE', "/users/friends/$invalidId", [], $token, 'Tenter de supprimer avec un ID invalide');

$removeInvalidData = json_decode($removeInvalidResult['response'], true);

if (isset($removeInvalidData['success']) && !$removeInvalidData['success']) {
    echo "   ✅ Erreur attendue\n";
    echo "   📊 Code d'erreur: " . ($removeInvalidData['error']['code'] ?? 'N/A') . "\n";
    echo "   📊 Message: " . ($removeInvalidData['error']['message'] ?? 'N/A') . "\n";
    echo "   📊 Code HTTP: " . $removeInvalidResult['code'] . "\n";

    if ($removeInvalidData['error']['code'] === 'INVALID_FRIEND_ID') {
        echo "   ✅ Code d'erreur correct\n";
    } else {
        echo "   ❌ Code d'erreur incorrect\n";
    }
} else {
    echo "   ❌ La suppression aurait dû échouer\n";
}

echo "\n";

// 7. Test 4 : Tentative de suppression de soi-même
echo "🔄 TEST 4 : SUPPRESSION DE SOI-MÊME\n";
echo "===================================\n";

$removeSelfResult = testEndpoint('DELETE', "/users/friends/$userId", [], $token, 'Tenter de se supprimer de ses amis');

$removeSelfData = json_decode($removeSelfResult['response'], true);

if (isset($removeSelfData['success']) && !$removeSelfData['success']) {
    echo "   ✅ Erreur attendue\n";
    echo "   📊 Code d'erreur: " . ($removeSelfData['error']['code'] ?? 'N/A') . "\n";
    echo "   📊 Message: " . ($removeSelfData['error']['message'] ?? 'N/A') . "\n";
    echo "   📊 Code HTTP: " . $removeSelfResult['code'] . "\n";

    if ($removeSelfData['error']['code'] === 'INVALID_FRIEND_ID') {
        echo "   ✅ Code d'erreur correct\n";
    } else {
        echo "   ❌ Code d'erreur incorrect\n";
    }
} else {
    echo "   ❌ La suppression aurait dû échouer\n";
}

echo "\n";

// 8. Vérification dans la base de données
echo "🔍 VÉRIFICATION BASE DE DONNÉES\n";
echo "===============================\n";

$dbCheckCommand = "php artisan tinker --execute=\"echo 'Amis de l\\'utilisateur après suppression:' . PHP_EOL; \$friends = \\App\\Models\\FriendModel::with('friend')->where('user_id', '$userId')->get(); foreach(\$friends as \$friend) { echo '  - ' . \$friend->friend->firstname . ' ' . \$friend->friend->lastname . ' (' . \$friend->friend->email . ')' . PHP_EOL; } echo 'Total amis: ' . \$friends->count() . PHP_EOL;\"";
system($dbCheckCommand);

echo "\n";

// 9. Résumé des tests
echo "🎯 RÉSUMÉ DES TESTS\n";
echo "===================\n";

$allTestsPassed = true;

// Test 1: Suppression réussie
if (isset($removeFriendData['success']) && $removeFriendData['success']) {
    echo "   ✅ Test 1: Suppression d'ami réussie\n";
} else {
    echo "   ❌ Test 1: Échec de la suppression d'ami\n";
    $allTestsPassed = false;
}

// Test 2: Erreur pour non-ami
if (isset($removeNonFriendData['success']) && !$removeNonFriendData['success'] && $removeNonFriendData['error']['code'] === 'FRIEND_NOT_FOUND') {
    echo "   ✅ Test 2: Erreur correcte pour non-ami\n";
} else {
    echo "   ❌ Test 2: Erreur incorrecte pour non-ami\n";
    $allTestsPassed = false;
}

// Test 3: Erreur pour ID invalide
if (isset($removeInvalidData['success']) && !$removeInvalidData['success'] && $removeInvalidData['error']['code'] === 'INVALID_FRIEND_ID') {
    echo "   ✅ Test 3: Erreur correcte pour ID invalide\n";
} else {
    echo "   ❌ Test 3: Erreur incorrecte pour ID invalide\n";
    $allTestsPassed = false;
}

// Test 4: Erreur pour suppression de soi-même
if (isset($removeSelfData['success']) && !$removeSelfData['success'] && $removeSelfData['error']['code'] === 'INVALID_FRIEND_ID') {
    echo "   ✅ Test 4: Erreur correcte pour suppression de soi-même\n";
} else {
    echo "   ❌ Test 4: Erreur incorrecte pour suppression de soi-même\n";
    $allTestsPassed = false;
}

echo "\n";

if ($allTestsPassed) {
    echo "🎉 TOUS LES TESTS PASSENT ! L'endpoint de suppression d'ami fonctionne correctement.\n";
} else {
    echo "❌ CERTAINS TESTS ÉCHOUENT. Vérifiez l'implémentation.\n";
}

echo "\n=== FIN DU TEST ===\n";
