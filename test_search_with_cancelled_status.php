<?php

// Test pour vérifier le statut "cancelled" dans l'API de recherche
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

echo "=== TEST STATUT CANCELLED DANS L'API DE RECHERCHE ===\n\n";

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
$initialStatus = null;

if (isset($searchData['data']) && count($searchData['data']) > 0) {
    // Prendre le premier utilisateur qui n'est pas l'utilisateur connecté
    foreach ($searchData['data'] as $user) {
        if ($user['id'] !== $userId) {
            $targetUserId = $user['id'];
            $initialStatus = $user['relationship']['status'];
            echo "   ✅ Utilisateur cible trouvé: " . $user['firstname'] . " " . $user['lastname'] . " (ID: $targetUserId)\n";
            echo "   📊 Statut initial: " . $initialStatus . "\n";
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

// 3. Envoyer une demande d'ami
echo "🤝 ENVOI DEMANDE D'AMI\n";
echo "======================\n";

$sendRequestResult = testEndpoint('POST', '/users/friend-requests', [
    'userId' => $targetUserId
], $token, 'Envoyer une demande d\'ami');

$sendRequestData = json_decode($sendRequestResult['response'], true);
if (isset($sendRequestData['success']) && $sendRequestData['success']) {
    echo "   ✅ Demande d'ami envoyée avec succès\n";
    echo "   📊 De: $userId vers: $targetUserId\n";
} else {
    echo "   ❌ Échec de l'envoi de la demande d'ami\n";
    echo "   Erreur: " . ($sendRequestData['error']['message'] ?? 'Erreur inconnue') . "\n";

    // Si la demande existe déjà, on continue quand même
    if (str_contains($sendRequestData['error']['message'] ?? '', 'existe déjà')) {
        echo "   ℹ️  La demande existe déjà, on continue...\n";
    } else {
        echo "   Réponse complète: " . $sendRequestResult['response'] . "\n\n";
        exit;
    }
}
echo "\n";

// 4. Vérifier le statut après envoi de la demande
echo "🔍 VÉRIFICATION STATUT APRÈS ENVOI\n";
echo "==================================\n";

$searchAfterSendResult = testEndpoint('GET', "/users/search?q=test", [], $token, 'Rechercher après envoi de demande');

$searchAfterSendData = json_decode($searchAfterSendResult['response'], true);
$statusAfterSend = null;

if (isset($searchAfterSendData['data'])) {
    foreach ($searchAfterSendData['data'] as $user) {
        if ($user['id'] === $targetUserId) {
            $statusAfterSend = $user['relationship']['status'];
            $hasPendingRequest = $user['relationship']['hasPendingRequest'];
            echo "   📊 Statut après envoi: " . $statusAfterSend . "\n";
            echo "   📊 hasPendingRequest: " . ($hasPendingRequest ? 'true' : 'false') . "\n";
            break;
        }
    }
}

if ($statusAfterSend === 'pending') {
    echo "   ✅ Statut correct après envoi\n";
} else {
    echo "   ❌ Statut incorrect après envoi: $statusAfterSend\n";
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
    echo "   Réponse complète: " . $cancelRequestResult['response'] . "\n\n";
    exit;
}
echo "\n";

// 6. Vérifier le statut après annulation
echo "🔍 VÉRIFICATION STATUT APRÈS ANNULATION\n";
echo "========================================\n";

$searchAfterCancelResult = testEndpoint('GET', "/users/search?q=test", [], $token, 'Rechercher après annulation');

$searchAfterCancelData = json_decode($searchAfterCancelResult['response'], true);
$statusAfterCancel = null;
$hasPendingRequestAfterCancel = null;

if (isset($searchAfterCancelData['data'])) {
    foreach ($searchAfterCancelData['data'] as $user) {
        if ($user['id'] === $targetUserId) {
            $statusAfterCancel = $user['relationship']['status'];
            $hasPendingRequestAfterCancel = $user['relationship']['hasPendingRequest'];
            echo "   📊 Statut après annulation: " . $statusAfterCancel . "\n";
            echo "   📊 hasPendingRequest: " . ($hasPendingRequestAfterCancel ? 'true' : 'false') . "\n";
            break;
        }
    }
}

// 7. Vérifier les critères d'acceptation
echo "\n🎯 VÉRIFICATION DES CRITÈRES D'ACCEPTATION\n";
echo "==========================================\n";

$allTestsPassed = true;

// Test 1: Le statut doit être "cancelled"
if ($statusAfterCancel === 'cancelled') {
    echo "   ✅ Test 1: Statut 'cancelled' retourné\n";
} else {
    echo "   ❌ Test 1: Statut incorrect. Attendu: 'cancelled', Reçu: '$statusAfterCancel'\n";
    $allTestsPassed = false;
}

// Test 2: hasPendingRequest doit être false
if ($hasPendingRequestAfterCancel === false) {
    echo "   ✅ Test 2: hasPendingRequest est false\n";
} else {
    echo "   ❌ Test 2: hasPendingRequest incorrect. Attendu: false, Reçu: " . ($hasPendingRequestAfterCancel ? 'true' : 'false') . "\n";
    $allTestsPassed = false;
}

// Test 3: isFriend doit être false
if (isset($searchAfterCancelData['data'])) {
    foreach ($searchAfterCancelData['data'] as $user) {
        if ($user['id'] === $targetUserId) {
            $isFriend = $user['relationship']['isFriend'];
            if ($isFriend === false) {
                echo "   ✅ Test 3: isFriend est false\n";
            } else {
                echo "   ❌ Test 3: isFriend incorrect. Attendu: false, Reçu: " . ($isFriend ? 'true' : 'false') . "\n";
                $allTestsPassed = false;
            }
            break;
        }
    }
}

// Test 4: Vérifier la transition des statuts
echo "   📊 Transition des statuts:\n";
echo "      Initial: $initialStatus\n";
echo "      Après envoi: $statusAfterSend\n";
echo "      Après annulation: $statusAfterCancel\n";

if ($initialStatus === 'none' && $statusAfterSend === 'pending' && $statusAfterCancel === 'cancelled') {
    echo "   ✅ Test 4: Transition des statuts correcte\n";
} else {
    echo "   ❌ Test 4: Transition des statuts incorrecte\n";
    $allTestsPassed = false;
}

echo "\n";

// Résumé final
if ($allTestsPassed) {
    echo "🎉 TOUS LES TESTS PASSENT ! Le statut 'cancelled' fonctionne correctement.\n";
} else {
    echo "❌ CERTAINS TESTS ÉCHOUENT. Vérifiez l'implémentation.\n";
}

echo "\n=== FIN DU TEST ===\n";
