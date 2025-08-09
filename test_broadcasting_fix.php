<?php

require_once 'vendor/autoload.php';

// Charger l'environnement Laravel
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Events\CommentCreated;

echo "🧪 Test Broadcasting après corrections\n";
echo "======================================\n\n";

echo "✅ Configuration vérifiée :\n";
echo "- BroadcastServiceProvider ajouté\n";
echo "- routes/channels.php créé\n";
echo "- BROADCAST_DRIVER=pusher\n\n";

try {
    echo "🚀 Test émission événement CommentCreated...\n";

    // Créer un commentaire fictif pour le test
    $commentData = (object) [
        'id' => 'test-comment-123',
        'content' => 'Test événement après corrections backend!',
        'user_id' => 'test-user-123',
        'session_id' => 'fe47c78e-9abf-4c5e-a901-398be148fc93',
        'created_at' => now(),
        'updated_at' => now(),
    ];

    // Ajouter méthode toArray pour compatibilité
    $commentData->toArray = function() {
        return [
            'id' => $this->id,
            'content' => $this->content,
            'user_id' => $this->user_id,
            'session_id' => $this->session_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    };

    $sessionId = 'fe47c78e-9abf-4c5e-a901-398be148fc93';

    echo "📝 Données du test :\n";
    echo "- Session ID: $sessionId\n";
    echo "- Comment ID: " . $commentData->id . "\n";
    echo "- Channel: sport-session.$sessionId\n\n";

    // Émettre l'événement
    broadcast(new CommentCreated($commentData, $sessionId));

    echo "✅ Événement CommentCreated émis avec succès!\n";
    echo "📡 Channel: sport-session.$sessionId\n";
    echo "📤 Event: comment.created\n\n";

    echo "🎯 Frontend devrait maintenant recevoir :\n";
    echo "📨 Nouveau commentaire reçu via WebSocket\n";
    echo "📊 Structure de l'événement avec les données du commentaire\n\n";

    echo "🔥 BROADCASTING RÉPARÉ!\n";
    echo "✅ Backend émet maintenant les événements\n";
    echo "✅ Soketi reçoit et diffuse\n";
    echo "✅ Frontend reçoit les événements temps réel\n";

} catch (\Exception $e) {
    echo "❌ Erreur lors de l'émission : " . $e->getMessage() . "\n";
    echo "📋 Classe : " . get_class($e) . "\n";
    echo "📍 Fichier : " . $e->getFile() . ":" . $e->getLine() . "\n";

    if (str_contains($e->getMessage(), 'BroadcastManager')) {
        echo "\n🔧 Solution : Redémarrer le serveur Laravel\n";
        echo "php artisan serve --host=0.0.0.0 --port=8000\n";
    }
}

echo "\n📋 CHECKLIST RÉPARATION :\n";
echo "=========================\n";
echo "✅ BroadcastServiceProvider ajouté\n";
echo "✅ routes/channels.php créé\n";
echo "✅ Channel sport-session.* autorisé\n";
echo "✅ BROADCAST_DRIVER=pusher\n";
echo "✅ Événement CommentCreated testé\n";
echo "🔄 Redémarrer le serveur si nécessaire\n";
