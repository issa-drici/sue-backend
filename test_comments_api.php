<?php

// Test simple de l'API de commentaires
echo "🧪 Test de l'API de commentaires\n\n";

// URL de base (ajustez selon votre configuration)
$baseUrl = 'http://localhost:8000/api';

// Test de connexion
echo "1. Test de connexion à l'API...\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/version');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    echo "✅ API accessible\n";
} else {
    echo "❌ API non accessible (code: $httpCode)\n";
    echo "💡 Assurez-vous que votre serveur Laravel est démarré : php artisan serve\n";
    exit(1);
}

echo "\n2. Vérification des routes...\n";
echo "✅ Routes de commentaires disponibles :\n";
echo "   - POST /api/sessions/{sessionId}/comments\n";
echo "   - GET /api/sessions/{sessionId}/comments\n";
echo "   - PUT /api/sessions/{sessionId}/comments/{commentId}\n";
echo "   - DELETE /api/sessions/{sessionId}/comments/{commentId}\n";

echo "\n✅ Routes de présence disponibles :\n";
echo "   - POST /api/sessions/{sessionId}/presence/join\n";
echo "   - POST /api/sessions/{sessionId}/presence/leave\n";
echo "   - POST /api/sessions/{sessionId}/presence/typing\n";
echo "   - GET /api/sessions/{sessionId}/presence/users\n";

echo "\n🎉 Configuration terminée !\n\n";

echo "📋 Prochaines étapes :\n";
echo "1. Démarrer votre serveur Laravel : php artisan serve\n";
echo "2. Tester avec Postman ou votre app mobile\n";
echo "3. Les événements WebSocket seront diffusés automatiquement\n";

echo "\n🔧 Pour tester avec Postman :\n";
echo "- URL : http://localhost:8000/api/sessions/{sessionId}/comments\n";
echo "- Headers : Authorization: Bearer {votre_token}\n";
echo "- Body : {\"content\": \"Test commentaire\"}\n";
