<?php

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\Http;

// Configuration
$baseUrl = 'http://localhost:8000/api';
$organizerToken = '172|XIxo3WMAxfIq2LlnBYBKdcnV33w4NkbkTjsvEbmH424d7021';
$organizerId = '9f728e62-b8c0-4482-b5fb-65259c544a0f';

// Utilisateurs à inviter (avec leurs tokens Expo enregistrés)
$participants = [
    [
        'id' => '9f6fd1d4-a6f6-4156-8c55-41c9c590896c',
        'name' => 'Asmaa Guediri',
        'expoToken' => 'ExponentPushToken[eFrryVHbyufBlMhS7ai2OO]'
    ],
    [
        'id' => '9f6fd17e-21c6-427e-9b82-983b7e2cbd7a',
        'name' => 'Issa Drici',
        'expoToken' => 'ExponentPushToken[DUDyt2MqQBUht8msAQSlx4]'
    ]
];

echo "🧪 Test des notifications automatiques lors de création de session\n";
echo "==============================================================\n\n";

// 1. Créer une session avec participants
echo "1️⃣ Création d'une session avec participants...\n";

$sessionData = [
    'sport' => 'tennis',
    'date' => '2025-07-25',
    'time' => '14:30',
    'location' => 'Tennis Club de Paris',
    'maxParticipants' => 4,
    'participantIds' => array_column($participants, 'id')
];

$response = Http::withHeaders([
    'Authorization' => 'Bearer ' . $organizerToken,
    'Content-Type' => 'application/json',
    'Accept' => 'application/json'
])->post($baseUrl . '/sessions', $sessionData);

echo "Status: " . $response->status() . "\n";

if ($response->successful()) {
    $session = $response->json()['data'];
    $sessionId = $session['id'];
    echo "✅ Session créée avec succès: {$sessionId}\n";
    echo "📋 Participants invités: " . count($participants) . "\n\n";

    // 2. Vérifier les notifications créées
    echo "2️⃣ Vérification des notifications créées...\n";

    foreach ($participants as $participant) {
        echo "\n--- Notification pour {$participant['name']} ---\n";

        // Récupérer les notifications de l'utilisateur
        $notificationsResponse = Http::withHeaders([
            'Authorization' => 'Bearer ' . $organizerToken, // On utilise le même token pour simplifier
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ])->get($baseUrl . '/notifications');

        if ($notificationsResponse->successful()) {
            $notifications = $notificationsResponse->json()['data'];

            // Chercher la notification d'invitation pour cette session
            $invitationNotification = null;
            foreach ($notifications as $notification) {
                if ($notification['type'] === 'invitation' &&
                    $notification['session_id'] === $sessionId &&
                    $notification['user_id'] === $participant['id']) {
                    $invitationNotification = $notification;
                    break;
                }
            }

            if ($invitationNotification) {
                echo "✅ Notification d'invitation trouvée\n";
                echo "   ID: {$invitationNotification['id']}\n";
                echo "   Titre: {$invitationNotification['title']}\n";
                echo "   Message: {$invitationNotification['message']}\n";
                echo "   Push envoyé: " . ($invitationNotification['push_sent'] ? 'Oui' : 'Non') . "\n";

                if (isset($invitationNotification['push_sent_at'])) {
                    echo "   Push envoyé à: {$invitationNotification['push_sent_at']}\n";
                }
            } else {
                echo "❌ Aucune notification d'invitation trouvée\n";
            }
        } else {
            echo "❌ Erreur lors de la récupération des notifications\n";
            echo "   Status: " . $notificationsResponse->status() . "\n";
            echo "   Response: " . $notificationsResponse->body() . "\n";
        }
    }

    // 3. Test d'envoi manuel de notification pour vérifier
    echo "\n3️⃣ Test d'envoi manuel de notification...\n";

    foreach ($participants as $participant) {
        echo "\n--- Test pour {$participant['name']} ---\n";

        $manualNotificationData = [
            'recipientId' => $participant['id'],
            'title' => 'Test manuel - Invitation session',
            'body' => "Test manuel: Vous avez été invité à une session de tennis",
            'data' => [
                'type' => 'session_invitation',
                'session_id' => $sessionId,
                'test' => 'manual'
            ]
        ];

        $manualResponse = Http::withHeaders([
            'Authorization' => 'Bearer ' . $organizerToken,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ])->post($baseUrl . '/notifications/send', $manualNotificationData);

        echo "Status: " . $manualResponse->status() . "\n";

        if ($manualResponse->successful()) {
            $result = $manualResponse->json();
            echo "✅ Notification manuelle envoyée avec succès\n";
            echo "   Tokens utilisés: {$result['data']['tokensCount']}\n";
            echo "   Succès: {$result['data']['result']['total_success_count']}\n";
            echo "   Erreurs: {$result['data']['result']['total_error_count']}\n";
        } else {
            echo "❌ Erreur lors de l'envoi manuel\n";
            echo "   Response: " . $manualResponse->body() . "\n";
        }
    }

} else {
    echo "❌ Erreur lors de la création de session\n";
    echo "Status: " . $response->status() . "\n";
    echo "Response: " . $response->body() . "\n";
}

echo "\n�� Test terminé\n";
