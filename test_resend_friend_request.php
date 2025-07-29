<?php

// Test pour vérifier qu'on peut renvoyer une demande d'ami après annulation
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

echo "=== TEST RENVOI DE DEMANDE D'AMI APRÈS ANNULATION ===\n\n";

// 1. Login avec un utilisateur existant
echo "🔐 LOGIN UTILISATEUR PRINCIPAL\n";
echo "=============================\n";

$loginResult = testEndpoint('POST', '/login', [
    'email' => 'cancel.test@example.com',
    'password' => 'password',
    'device_name' => 'test-device'
], null, 'Se connecter avec l\'utilisateur principal');

$loginData = json_decode($loginResult['response'], true);
$token = $loginData['token'] ?? null;
$userId = $loginData['user']['id'] ?? null;

if ($token) {
    echo "   ✅ Token obtenu: " . substr($token, 0, 20) . "...\n";
    echo "   📊 ID utilisateur: $userId\n\n";
} else {
    echo "   ❌ Échec de la connexion\n";
    echo "   Réponse: " . $loginResult['response'] . "\n\n";
    exit;
}

// 2. Rechercher des utilisateurs pour trouver une cible
echo "🔍 RECHERCHE D'UTILISATEURS\n";
echo "===========================\n";

$searchResult = testEndpoint('GET', '/users/search?q=test', [], $token, 'Rechercher des utilisateurs');

$searchData = json_decode($searchResult['response'], true);
$targetUserId = null;

if (isset($searchData['data']) && count($searchData['data']) > 0) {
    // Prendre le premier utilisateur qui n'est pas l'utilisateur connecté
    foreach ($searchData['data'] as $user) {
        if ($user['id'] !== $userId) {
            $targetUserId = $user['id'];
            echo "   ✅ Utilisateur cible trouvé: " . $user['firstname'] . " " . $user['lastname'] . " (ID: $targetUserId)\n";
            echo "   📊 Statut initial: " . $user['relationship']['status'] . "\n";
            break;
        }
    }
}

if (!$targetUserId) {
    echo "   ❌ Aucun utilisateur cible trouvé\n";
    echo "   Réponse: " . $searchResult['response'] . "\n\n";
    exit;
}

echo "\n";

// 3. Envoyer une première demande d'ami
echo "🤝 ENVOI PREMIÈRE DEMANDE D'AMI\n";
echo "===============================\n";

$sendRequestResult = testEndpoint('POST', '/users/friend-requests', [
    'userId' => $targetUserId
], $token, 'Envoyer une première demande d\'ami');

$sendRequestData = json_decode($sendRequestResult['response'], true);
if (isset($sendRequestData['success']) && $sendRequestData['success']) {
    echo "   ✅ Première demande d'ami envoyée avec succès\n";
    echo "   📊 De: $userId vers: $targetUserId\n";
} else {
    echo "   ❌ Échec de l'envoi de la première demande\n";
    echo "   Erreur: " . ($sendRequestData['error']['message'] ?? 'Erreur inconnue') . "\n";
    exit;
}
echo "\n";

// 4. Vérifier le statut après envoi
echo "🔍 VÉRIFICATION STATUT APRÈS PREMIER ENVOI\n";
echo "==========================================\n";

$searchAfterFirstSendResult = testEndpoint('GET', "/users/search?q=test", [], $token, 'Rechercher après premier envoi');

$searchAfterFirstSendData = json_decode($searchAfterFirstSendResult['response'], true);
$statusAfterFirstSend = null;

if (isset($searchAfterFirstSendData['data'])) {
    foreach ($searchAfterFirstSendData['data'] as $user) {
        if ($user['id'] === $targetUserId) {
            $statusAfterFirstSend = $user['relationship']['status'];
            echo "   📊 Statut après premier envoi: " . $statusAfterFirstSend . "\n";
            break;
        }
    }
}

if ($statusAfterFirstSend === 'pending') {
    echo "   ✅ Statut correct après premier envoi\n";
} else {
    echo "   ❌ Statut incorrect après premier envoi: $statusAfterFirstSend\n";
}
echo "\n";

// 5. Annuler la demande d'ami
echo "❌ ANNULATION DE LA DEMANDE\n";
echo "==========================\n";

$cancelRequestResult = testEndpoint('DELETE', '/users/friend-requests', [
    'target_user_id' => $targetUserId
], $token, 'Annuler la demande d\'ami');

$cancelRequestData = json_decode($cancelRequestResult['response'], true);
if (isset($cancelRequestData['success']) && $cancelRequestData['success']) {
    echo "   ✅ Demande d'ami annulée avec succès\n";
    echo "   📊 Request ID: " . $cancelRequestData['data']['requestId'] . "\n";
    echo "   📊 Statut: " . $cancelRequestData['data']['status'] . "\n";
} else {
    echo "   ❌ Échec de l'annulation\n";
    echo "   Erreur: " . ($cancelRequestData['error']['message'] ?? 'Erreur inconnue') . "\n";
    exit;
}
echo "\n";

// 6. Vérifier le statut après annulation
echo "🔍 VÉRIFICATION STATUT APRÈS ANNULATION\n";
echo "========================================\n";

$searchAfterCancelResult = testEndpoint('GET', "/users/search?q=test", [], $token, 'Rechercher après annulation');

$searchAfterCancelData = json_decode($searchAfterCancelResult['response'], true);
$statusAfterCancel = null;

if (isset($searchAfterCancelData['data'])) {
    foreach ($searchAfterCancelData['data'] as $user) {
        if ($user['id'] === $targetUserId) {
            $statusAfterCancel = $user['relationship']['status'];
            echo "   📊 Statut après annulation: " . $statusAfterCancel . "\n";
            break;
        }
    }
}

if ($statusAfterCancel === 'cancelled') {
    echo "   ✅ Statut correct après annulation\n";
} else {
    echo "   ❌ Statut incorrect après annulation: $statusAfterCancel\n";
}
echo "\n";

// 7. Renvoyer une nouvelle demande d'ami
echo "🤝 RENVOI DE LA DEMANDE D'AMI\n";
echo "=============================\n";

$resendRequestResult = testEndpoint('POST', '/users/friend-requests', [
    'userId' => $targetUserId
], $token, 'Renvoi de la demande d\'ami');

$resendRequestData = json_decode($resendRequestResult['response'], true);
if (isset($resendRequestData['success']) && $resendRequestData['success']) {
    echo "   ✅ Demande d'ami renvoyée avec succès\n";
    echo "   📊 De: $userId vers: $targetUserId\n";
} else {
    echo "   ❌ Échec du renvoi de la demande\n";
    echo "   Erreur: " . ($resendRequestData['error']['message'] ?? 'Erreur inconnue') . "\n";
    echo "   Code d'erreur: " . ($resendRequestData['error']['code'] ?? 'N/A') . "\n";
    echo "   Réponse complète: " . $resendRequestResult['response'] . "\n\n";
    exit;
}
echo "\n";

// 8. Vérifier le statut après renvoi
echo "🔍 VÉRIFICATION STATUT APRÈS RENVOI\n";
echo "===================================\n";

$searchAfterResendResult = testEndpoint('GET', "/users/search?q=test", [], $token, 'Rechercher après renvoi');

$searchAfterResendData = json_decode($searchAfterResendResult['response'], true);
$statusAfterResend = null;

if (isset($searchAfterResendData['data'])) {
    foreach ($searchAfterResendData['data'] as $user) {
        if ($user['id'] === $targetUserId) {
            $statusAfterResend = $user['relationship']['status'];
            $hasPendingRequest = $user['relationship']['hasPendingRequest'];
            echo "   📊 Statut après renvoi: " . $statusAfterResend . "\n";
            echo "   📊 hasPendingRequest: " . ($hasPendingRequest ? 'true' : 'false') . "\n";
            break;
        }
    }
}

// 9. Vérifier les critères d'acceptation
echo "\n🎯 VÉRIFICATION DES CRITÈRES D'ACCEPTATION\n";
echo "==========================================\n";

$allTestsPassed = true;

// Test 1: Le renvoi doit réussir (pas d'erreur 409)
if (isset($resendRequestData['success']) && $resendRequestData['success']) {
    echo "   ✅ Test 1: Renvoi de demande réussi\n";
} else {
    echo "   ❌ Test 1: Échec du renvoi de demande\n";
    $allTestsPassed = false;
}

// Test 2: Le statut doit redevenir "pending"
if ($statusAfterResend === 'pending') {
    echo "   ✅ Test 2: Statut redevenu 'pending' après renvoi\n";
} else {
    echo "   ❌ Test 2: Statut incorrect après renvoi. Attendu: 'pending', Reçu: '$statusAfterResend'\n";
    $allTestsPassed = false;
}

// Test 3: hasPendingRequest doit être true
if (isset($searchAfterResendData['data'])) {
    foreach ($searchAfterResendData['data'] as $user) {
        if ($user['id'] === $targetUserId) {
            $hasPendingRequest = $user['relationship']['hasPendingRequest'];
            if ($hasPendingRequest === true) {
                echo "   ✅ Test 3: hasPendingRequest est true\n";
            } else {
                echo "   ❌ Test 3: hasPendingRequest incorrect. Attendu: true, Reçu: " . ($hasPendingRequest ? 'true' : 'false') . "\n";
                $allTestsPassed = false;
            }
            break;
        }
    }
}

// Test 4: Vérifier la transition complète des statuts
echo "   📊 Transition complète des statuts:\n";
echo "      Initial: " . ($searchData['data'][0]['relationship']['status'] ?? 'unknown') . "\n";
echo "      Après premier envoi: $statusAfterFirstSend\n";
echo "      Après annulation: $statusAfterCancel\n";
echo "      Après renvoi: $statusAfterResend\n";

if ($statusAfterFirstSend === 'pending' && $statusAfterCancel === 'cancelled' && $statusAfterResend === 'pending') {
    echo "   ✅ Test 4: Transition complète des statuts correcte\n";
} else {
    echo "   ❌ Test 4: Transition des statuts incorrecte\n";
    $allTestsPassed = false;
}

echo "\n";

// Résumé final
if ($allTestsPassed) {
    echo "🎉 TOUS LES TESTS PASSENT ! Le renvoi de demande d'ami fonctionne correctement.\n";
} else {
    echo "❌ CERTAINS TESTS ÉCHOUENT. Vérifiez l'implémentation.\n";
}

echo "\n=== FIN DU TEST ===\n";
