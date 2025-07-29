<?php

// Test pour vérifier que les sessions refusées ne s'affichent plus du tout
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

echo "=== TEST SESSIONS REFUSÉES CACHÉES ===\n\n";

// 1. Créer des utilisateurs
echo "🔐 CRÉATION UTILISATEURS\n";
echo "========================\n";

$createOrganizerResult = testEndpoint('POST', '/register', [
    'email' => 'organizer@hidden.com',
    'password' => 'password123',
    'firstname' => 'Organisateur',
    'lastname' => 'Hidden',
    'device_name' => 'test-device'
], null, 'Créer un utilisateur organisateur');

$createParticipantResult = testEndpoint('POST', '/register', [
    'email' => 'participant@hidden.com',
    'password' => 'password123',
    'firstname' => 'Participant',
    'lastname' => 'Hidden',
    'device_name' => 'test-device'
], null, 'Créer un utilisateur participant');

// 2. Login
$loginOrganizerResult = testEndpoint('POST', '/login', [
    'email' => 'organizer@hidden.com',
    'password' => 'password123',
    'device_name' => 'test-device'
], null, 'Se connecter avec l\'organisateur');

$loginParticipantResult = testEndpoint('POST', '/login', [
    'email' => 'participant@hidden.com',
    'password' => 'password123',
    'device_name' => 'test-device'
], null, 'Se connecter avec le participant');

// Extraire les tokens
$organizerData = json_decode($loginOrganizerResult['response'], true);
$participantData = json_decode($loginParticipantResult['response'], true);

if (isset($organizerData['token']) && isset($participantData['token'])) {
    $organizerToken = $organizerData['token'];
    $participantToken = $participantData['token'];
    $participantId = $participantData['user']['id'];

    echo "   Token organisateur obtenu: " . substr($organizerToken, 0, 20) . "...\n";
    echo "   Token participant obtenu: " . substr($participantToken, 0, 20) . "...\n";
    echo "   Participant ID: $participantId\n\n";

    // 3. Créer une session
    echo "🏃 CRÉATION DE SESSION\n";
    echo "=====================\n";

    $createSessionResult = testEndpoint('POST', '/sessions', [
        'sport' => 'tennis',
        'date' => date('Y-m-d', strtotime('+1 week')),
        'time' => '18:00',
        'location' => 'Tennis Club de Paris',
        'participantIds' => [$participantId]
    ], $organizerToken, 'Créer une session avec participant');

    $sessionData = json_decode($createSessionResult['response'], true);
    if (isset($sessionData['data']['id'])) {
        $sessionId = $sessionData['data']['id'];
        echo "   ✅ Session créée avec ID: $sessionId\n\n";

        // 4. Vérifier que le participant voit la session avant de refuser
        echo "📋 VÉRIFICATION AVANT REFUS\n";
        echo "===========================\n";

        $sessionsBeforeResult = testEndpoint('GET', '/sessions', null, $participantToken, 'Récupérer les sessions avant refus');

        $sessionsBeforeData = json_decode($sessionsBeforeResult['response'], true);
        if (isset($sessionsBeforeData['data'])) {
            $sessionsBefore = $sessionsBeforeData['data'];
            $hasSessionBefore = false;
            foreach ($sessionsBefore as $session) {
                if ($session['id'] === $sessionId) {
                    $hasSessionBefore = true;
                    break;
                }
            }
            echo "   " . ($hasSessionBefore ? "✅" : "❌") . " Participant voit la session avant refus\n";
            echo "   Nombre de sessions: " . count($sessionsBefore) . "\n";
        }

        // 5. Participant refuse l'invitation
        echo "\n👥 PARTICIPANT REFUSE L'INVITATION\n";
        echo "==================================\n";

        $respondDeclineResult = testEndpoint('PATCH', "/sessions/$sessionId/respond", [
            'response' => 'decline'
        ], $participantToken, 'Participant refuse l\'invitation');

        if ($respondDeclineResult['code'] === 200) {
            echo "   ✅ Participant a refusé l'invitation\n";
        }

        // 6. Vérifier que le participant NE VOIT PLUS la session après refus
        echo "\n📋 VÉRIFICATION APRÈS REFUS (SESSIONS CACHÉES)\n";
        echo "===============================================\n";

        $sessionsAfterResult = testEndpoint('GET', '/sessions', null, $participantToken, 'Récupérer les sessions après refus');

        $sessionsAfterData = json_decode($sessionsAfterResult['response'], true);
        if (isset($sessionsAfterData['data'])) {
            $sessionsAfter = $sessionsAfterData['data'];
            $hasSessionAfter = false;
            foreach ($sessionsAfter as $session) {
                if ($session['id'] === $sessionId) {
                    $hasSessionAfter = true;
                    break;
                }
            }
            echo "   " . ($hasSessionAfter ? "❌" : "✅") . " Participant NE voit PLUS la session après refus\n";
            echo "   Nombre de sessions: " . count($sessionsAfter) . "\n";
        }

        // 7. Vérifier les participations
        echo "\n📋 VÉRIFICATION PARTICIPATIONS\n";
        echo "=============================\n";

        $participationsResult = testEndpoint('GET', '/sessions/my-participations', null, $participantToken, 'Récupérer les participations');

        $participationsData = json_decode($participationsResult['response'], true);
        if (isset($participationsData['data']['data'])) {
            $participations = $participationsData['data']['data'];
            $hasParticipation = false;
            foreach ($participations as $session) {
                if ($session['id'] === $sessionId) {
                    $hasParticipation = true;
                    break;
                }
            }
            echo "   " . ($hasParticipation ? "❌" : "✅") . " Participant NE voit PLUS la session dans ses participations\n";
            echo "   Nombre de participations: " . count($participations) . "\n";
        }

        // 8. Vérifier que l'organisateur voit toujours la session
        echo "\n👤 VÉRIFICATION ORGANISATEUR\n";
        echo "============================\n";

        $organizerSessionsResult = testEndpoint('GET', '/sessions', null, $organizerToken, 'Récupérer les sessions de l\'organisateur');

        $organizerSessionsData = json_decode($organizerSessionsResult['response'], true);
        if (isset($organizerSessionsData['data'])) {
            $organizerSessions = $organizerSessionsData['data'];
            $organizerHasSession = false;
            foreach ($organizerSessions as $session) {
                if ($session['id'] === $sessionId) {
                    $organizerHasSession = true;
                    break;
                }
            }
            echo "   " . ($organizerHasSession ? "✅" : "❌") . " Organisateur voit toujours la session\n";
            echo "   Nombre de sessions organisateur: " . count($organizerSessions) . "\n";
        }

        // 9. Vérifier les détails de la session (devrait toujours être accessible)
        echo "\n🔍 VÉRIFICATION DÉTAILS SESSION\n";
        echo "===============================\n";

        $sessionDetailResult = testEndpoint('GET', "/sessions/$sessionId", null, $participantToken, 'Récupérer les détails de la session');

        if ($sessionDetailResult['code'] === 200) {
            $sessionDetailData = json_decode($sessionDetailResult['response'], true);
            if (isset($sessionDetailData['data']['participants'])) {
                $participants = $sessionDetailData['data']['participants'];
                foreach ($participants as $participant) {
                    if ($participant['id'] === $participantId) {
                        echo "   ✅ Statut du participant: " . $participant['status'] . "\n";
                        break;
                    }
                }
            }
        } else {
            echo "   ❌ Impossible d'accéder aux détails de la session\n";
        }

    } else {
        echo "   ❌ Erreur lors de la création de la session\n";
        echo "   Réponse: " . $createSessionResult['response'] . "\n";
    }
} else {
    echo "   ❌ Erreur lors de la connexion\n";
}

echo "\n=== TEST TERMINÉ ===\n";
