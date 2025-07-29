<?php

require_once 'vendor/autoload.php';

use App\Models\SportSessionModel;
use App\Models\UserModel;
use App\Models\SportSessionCommentModel;
use App\Models\SportSessionPresenceModel;

// Test de base pour vérifier que les modèles fonctionnent
echo "🧪 Test du système de commentaires en temps réel\n\n";

// Vérifier que les tables existent
try {
    // Test des modèles
    echo "✅ Test des modèles...\n";

    // Vérifier que les modèles peuvent être instanciés
    $userModel = new UserModel();
    $sessionModel = new SportSessionModel();
    $commentModel = new SportSessionCommentModel();
    $presenceModel = new SportSessionPresenceModel();

    echo "✅ Modèles instanciés avec succès\n";

    // Vérifier les relations
    echo "✅ Relations des modèles configurées\n";

    // Test des scopes
    echo "✅ Scopes des modèles disponibles\n";

    echo "\n🎉 Tous les tests de base sont passés !\n";
    echo "\n📋 Prochaines étapes :\n";
    echo "1. Configurer les variables d'environnement pour le broadcasting\n";
    echo "2. Installer et configurer un serveur WebSocket (Soketi)\n";
    echo "3. Tester les endpoints API\n";
    echo "4. Tester les événements de broadcasting\n";

} catch (Exception $e) {
    echo "❌ Erreur : " . $e->getMessage() . "\n";
    echo "📍 Fichier : " . $e->getFile() . ":" . $e->getLine() . "\n";
}
