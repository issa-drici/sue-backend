<?php

// Test pour vérifier les commentaires système lors des réponses aux invitations
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

echo "=== TEST COMMENTAIRES SYSTÈME ===\n\n";

// 1. Créer un organisateur
echo "🔐 CRÉATION ORGANISATEUR\n";
echo "=========================\n";

$createOrganizerResult = testEndpoint('POST', '/register', [
    'email' => 'organizer@system.com',
    'password' => 'password123',
    'firstname' => 'Organisateur',
    'lastname' => 'System',
    'device_name' => 'test-device'
], null, 'Créer un organisateur');

// 2. Login organisateur
$loginOrganizerResult = testEndpoint('POST', '/login', [
    'email' => 'organizer@system.com',
    'password' => 'password123',
    'device_name' => 'test-device'
], null, 'Se connecter en tant qu\'organisateur');

$organizerData = json_decode($loginOrganizerResult['response'], true);
if (!isset($organizerData['token'])) {
    echo "   ❌ Erreur lors de la connexion de l'organisateur\n";
    exit;
}

$organizerToken = $organizerData['token'];
echo "   Token organisateur obtenu: " . substr($organizerToken, 0, 20) . "...\n\n";

// 3. Créer un participant
echo "🔐 CRÉATION PARTICIPANT\n";
echo "========================\n";

$createParticipantResult = testEndpoint('POST', '/register', [
    'email' => 'participant@system.com',
    'password' => 'password123',
    'firstname' => 'Participant',
    'lastname' => 'System',
    'device_name' => 'test-device'
], null, 'Créer un participant');

// 4. Login participant
$loginParticipantResult = testEndpoint('POST', '/login', [
    'email' => 'participant@system.com',
    'password' => 'password123',
    'device_name' => 'test-device'
], null, 'Se connecter en tant que participant');

$participantData = json_decode($loginParticipantResult['response'], true);
if (!isset($participantData['token'])) {
    echo "   ❌ Erreur lors de la connexion du participant\n";
    exit;
}

$participantToken = $participantData['token'];
echo "   Token participant obtenu: " . substr($participantToken, 0, 20) . "...\n\n";

// 5. Créer une session avec le participant invité
echo "📋 CRÉATION SESSION AVEC INVITATION\n";
echo "====================================\n";

$sessionData = [
    'sport' => 'tennis',
    'date' => date('Y-m-d', strtotime('+1 day')),
    'time' => '18:00',
    'location' => 'Tennis Club - Test Commentaires',
    'participantIds' => [$participantData['user']['id']]
];

$createSessionResult = testEndpoint('POST', '/sessions', $sessionData, $organizerToken, 'Créer session avec invitation');

if ($createSessionResult['code'] !== 201) {
    echo "   ❌ Erreur lors de la création de la session\n";
    exit;
}

$sessionResponse = json_decode($createSessionResult['response'], true);
$session = $sessionResponse['data'];
echo "   ✅ Session créée avec succès\n";
echo "   - ID: " . $session['id'] . "\n\n";

// 6. Vérifier les commentaires initiaux
echo "💬 VÉRIFICATION COMMENTAIRES INITIAUX\n";
echo "=====================================\n";

$initialCommentsResult = testEndpoint('GET', '/sessions/' . $session['id'] . '/comments', null, $organizerToken, 'Récupérer commentaires initiaux');

if ($initialCommentsResult['code'] === 200) {
    $initialComments = json_decode($initialCommentsResult['response'], true);
    $initialCount = count($initialComments['data']);
    echo "   ✅ Commentaires initiaux récupérés\n";
    echo "   - Nombre de commentaires: $initialCount\n";
} else {
    echo "   ❌ Erreur lors de la récupération des commentaires initiaux\n";
    $initialCount = 0;
}

// 7. Accepter l'invitation
echo "\n✅ ACCEPTATION INVITATION\n";
echo "==========================\n";

$acceptResult = testEndpoint('PATCH', '/sessions/' . $session['id'] . '/respond', [
    'response' => 'accept'
], $participantToken, 'Accepter l\'invitation');

if ($acceptResult['code'] === 200) {
    echo "   ✅ Invitation acceptée avec succès\n";
} else {
    echo "   ❌ Erreur lors de l'acceptation\n";
    echo "   Réponse: " . $acceptResult['response'] . "\n";
}

// 8. Vérifier les commentaires après acceptation
echo "\n💬 VÉRIFICATION COMMENTAIRES APRÈS ACCEPTATION\n";
echo "===============================================\n";

$commentsAfterAcceptResult = testEndpoint('GET', '/sessions/' . $session['id'] . '/comments', null, $organizerToken, 'Récupérer commentaires après acceptation');

if ($commentsAfterAcceptResult['code'] === 200) {
    $commentsAfterAccept = json_decode($commentsAfterAcceptResult['response'], true);
    $acceptCount = count($commentsAfterAccept['data']);
    echo "   ✅ Commentaires après acceptation récupérés\n";
    echo "   - Nombre de commentaires: $acceptCount\n";

    if ($acceptCount > $initialCount) {
        echo "   ✅ Nouveau commentaire système ajouté\n";

        // Afficher le dernier commentaire
        $lastComment = end($commentsAfterAccept['data']);
        echo "   - Dernier commentaire: " . $lastComment['content'] . "\n";
        echo "   - Auteur: " . $lastComment['user']['firstname'] . " " . $lastComment['user']['lastname'] . "\n";
    } else {
        echo "   ❌ Aucun nouveau commentaire système ajouté\n";
    }
} else {
    echo "   ❌ Erreur lors de la récupération des commentaires après acceptation\n";
}

// 9. Créer une nouvelle session pour tester le refus
echo "\n📋 CRÉATION NOUVELLE SESSION POUR TEST REFUS\n";
echo "=============================================\n";

$sessionData2 = [
    'sport' => 'football',
    'date' => date('Y-m-d', strtotime('+2 days')),
    'time' => '19:00',
    'location' => 'Stade - Test Refus',
    'participantIds' => [$participantData['user']['id']]
];

$createSessionResult2 = testEndpoint('POST', '/sessions', $sessionData2, $organizerToken, 'Créer deuxième session avec invitation');

if ($createSessionResult2['code'] !== 201) {
    echo "   ❌ Erreur lors de la création de la deuxième session\n";
    exit;
}

$sessionResponse2 = json_decode($createSessionResult2['response'], true);
$session2 = $sessionResponse2['data'];
echo "   ✅ Deuxième session créée avec succès\n";
echo "   - ID: " . $session2['id'] . "\n\n";

// 10. Refuser l'invitation
echo "❌ REFUS INVITATION\n";
echo "===================\n";

$declineResult = testEndpoint('PATCH', '/sessions/' . $session2['id'] . '/respond', [
    'response' => 'decline'
], $participantToken, 'Refuser l\'invitation');

if ($declineResult['code'] === 200) {
    echo "   ✅ Invitation refusée avec succès\n";
} else {
    echo "   ❌ Erreur lors du refus\n";
    echo "   Réponse: " . $declineResult['response'] . "\n";
}

// 11. Vérifier les commentaires après refus
echo "\n💬 VÉRIFICATION COMMENTAIRES APRÈS REFUS\n";
echo "=========================================\n";

$commentsAfterDeclineResult = testEndpoint('GET', '/sessions/' . $session2['id'] . '/comments', null, $organizerToken, 'Récupérer commentaires après refus');

if ($commentsAfterDeclineResult['code'] === 200) {
    $commentsAfterDecline = json_decode($commentsAfterDeclineResult['response'], true);
    $declineCount = count($commentsAfterDecline['data']);
    echo "   ✅ Commentaires après refus récupérés\n";
    echo "   - Nombre de commentaires: $declineCount\n";

    if ($declineCount > 0) {
        echo "   ✅ Commentaire système ajouté pour le refus\n";

        // Afficher le dernier commentaire
        $lastComment = end($commentsAfterDecline['data']);
        echo "   - Dernier commentaire: " . $lastComment['content'] . "\n";
        echo "   - Auteur: " . $lastComment['user']['firstname'] . " " . $lastComment['user']['lastname'] . "\n";
    } else {
        echo "   ❌ Aucun commentaire système ajouté pour le refus\n";
    }
} else {
    echo "   ❌ Erreur lors de la récupération des commentaires après refus\n";
}

echo "\n=== TEST TERMINÉ ===\n";
