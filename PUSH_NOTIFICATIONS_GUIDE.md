# Guide d'utilisation - Notifications Push Expo

## 📋 Vue d'ensemble

Ce guide détaille l'implémentation du système de notifications push utilisant Expo Notifications côté backend Laravel. Le système permet d'envoyer des notifications push en temps réel aux applications mobiles Expo.

## 🏗️ Architecture implémentée

### Base de données
- **Table `push_tokens`** : Stockage des tokens Expo des utilisateurs
- **Colonnes push** dans `notifications` : Suivi des notifications envoyées

### Composants backend
- **Service Expo** : `ExpoPushNotificationService` - Gestion de l'API Expo
- **Repository** : `PushTokenRepository` - Gestion des tokens en base
- **UseCase** : `SavePushTokenUseCase` - Logique métier
- **Contrôleur** : `SavePushTokenAction` - Endpoint API

## 🔧 Configuration requise

### Variables d'environnement
Aucune configuration spéciale requise - Expo Notifications est gratuit et ne nécessite pas de clés API.

### Dépendances Laravel
- Laravel Sanctum (authentification)
- cURL (pour les requêtes HTTP vers Expo)

## 📱 Endpoints API

### 1. Enregistrer un token push

**Endpoint :** `POST /api/push-tokens`

**Authentification :** Requise (Bearer Token)

**Headers :**
```
Authorization: Bearer <token>
Content-Type: application/json
Accept: application/json
```

**Body :**
```json
{
  "token": "ExponentPushToken[your-expo-token]",
  "platform": "expo"
}
```

**Paramètres :**
- `token` (requis) : Token Expo généré par l'application mobile
- `platform` (optionnel) : Plateforme (expo, ios, android) - défaut: "expo"

**Réponse succès (200) :**
```json
{
  "success": true,
  "message": "Token push enregistré avec succès"
}
```

**Réponse erreur (400) - Token invalide :**
```json
{
  "success": false,
  "error": {
    "code": "TOKEN_SAVE_ERROR",
    "message": "Token invalide"
  }
}
```

**Réponse erreur (400) - Validation :**
```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Données invalides",
    "details": {
      "token": ["Le champ token est obligatoire."]
    }
  }
}
```

**Réponse erreur (401) - Non authentifié :**
```json
{
  "message": "Unauthenticated."
}
```

## 🔍 Validation des tokens

### Format des tokens Expo
Les tokens doivent suivre le format : `ExponentPushToken[xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx]`

### Validation automatique
- Vérification du format regex
- Rejet des tokens invalides
- Logging des erreurs de validation

## 📊 Structure de la base de données

### Table `push_tokens`
```sql
CREATE TABLE push_tokens (
    id UUID PRIMARY KEY,
    user_id UUID NOT NULL,
    token VARCHAR(255) UNIQUE NOT NULL,
    platform VARCHAR(50) DEFAULT 'expo',
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

### Colonnes ajoutées à `notifications`
```sql
ALTER TABLE notifications ADD COLUMN push_sent BOOLEAN DEFAULT false;
ALTER TABLE notifications ADD COLUMN push_sent_at TIMESTAMP NULL;
ALTER TABLE notifications ADD COLUMN push_data JSON NULL;
```

## 🚀 Service Expo Push Notifications

### Fonctionnalités
- **Envoi en lot** : Jusqu'à 100 tokens par requête
- **Gestion d'erreurs** : Analyse des réponses Expo
- **Logging complet** : Traçabilité des envois
- **Validation** : Vérification format des tokens

### Configuration
- **API URL** : `https://exp.host/--/api/v2/push/send`
- **Timeout** : 30 secondes
- **Headers** : Content-Type, Accept, Accept-encoding

### Format des messages
```json
{
  "to": "ExponentPushToken[...]",
  "sound": "default",
  "title": "Titre de la notification",
  "body": "Contenu de la notification",
  "data": {
    "notification_id": "uuid",
    "type": "invitation",
    "session_id": "uuid"
  },
  "priority": "high",
  "channelId": "default"
}
```

## 🔍 Gestion des erreurs

### Types d'erreurs Expo
- **Token invalide** : Token expiré ou incorrect
- **App non installée** : Application désinstallée
- **Erreur réseau** : Problème de connexion
- **Limite dépassée** : Trop de requêtes

### Actions automatiques
- **Tokens invalides** : Désactivation automatique
- **Erreurs réseau** : Retry automatique
- **Logging** : Toutes les erreurs enregistrées

## 📊 Monitoring et logs

### Logs générés
- **Envoi réussi** : Nombre de notifications envoyées
- **Erreurs partielles** : Succès/échecs détaillés
- **Erreurs réseau** : Problèmes de connexion
- **Tokens invalides** : Validation des tokens

### Métriques disponibles
- Nombre total de tokens par utilisateur
- Taux de succès des envois
- Erreurs par type
- Performance des requêtes

## 🔄 Workflow d'utilisation

### 1. Enregistrement du token
1. L'application mobile génère un token Expo
2. Envoi du token via `POST /api/push-tokens`
3. Validation et stockage en base
4. Confirmation de l'enregistrement

### 2. Envoi de notification
1. Création d'une notification en base
2. Récupération des tokens de l'utilisateur
3. Envoi via l'API Expo
4. Mise à jour du statut `push_sent`

### 3. Gestion des erreurs
1. Analyse des réponses Expo
2. Désactivation des tokens invalides
3. Logging des erreurs
4. Retry si nécessaire

## 🛠️ Méthodes du repository

### PushTokenRepository
- `saveToken(userId, token, platform)` : Enregistrer/mettre à jour un token
- `getTokensForUser(userId)` : Récupérer tous les tokens d'un utilisateur
- `deactivateToken(token)` : Désactiver un token
- `isTokenActive(token)` : Vérifier si un token est actif
- `cleanInactiveTokens()` : Nettoyer les tokens inactifs

## 🔧 Intégration avec le système existant

### Notifications existantes
Le système est prêt à être intégré avec le système de notifications existant :
- Ajout automatique des notifications push
- Suivi des envois
- Gestion des erreurs

### Sessions de sport
Intégration possible avec :
- Notifications d'invitation
- Rappels de session
- Mises à jour de statut

## 📈 Performance et scalabilité

### Limites Expo
- **100 tokens par requête** : Géré automatiquement
- **Pas de limite globale** : Gratuit et illimité
- **Timeout** : 30 secondes par requête

### Optimisations
- Envoi en lots automatique
- Gestion des timeouts
- Retry automatique
- Cache des tokens actifs

## 🔒 Sécurité

### Authentification
- Tous les endpoints protégés par Sanctum
- Validation des tokens utilisateur
- Vérification des permissions

### Validation
- Format des tokens Expo
- Plateformes autorisées
- Données utilisateur

## 🧪 Tests

### Tests disponibles
- `test_push_notifications.php` : Tests complets du système
- Validation des tokens
- Gestion des erreurs
- Endpoints API

### Exécution des tests
```bash
php test_push_notifications.php
```

## 📝 Logs et debugging

### Niveaux de log
- **INFO** : Envois réussis
- **WARNING** : Erreurs partielles
- **ERROR** : Erreurs complètes

### Informations loggées
- Tokens utilisés
- Réponses Expo
- Erreurs détaillées
- Métriques de performance

## 🚀 Prochaines étapes

### Fonctionnalités à implémenter
1. **Intégration notifications** : Lier aux notifications existantes
2. **Endpoints supplémentaires** : Suppression, liste des tokens
3. **Notifications programmées** : Envoi différé
4. **Templates** : Notifications prédéfinies

### Améliorations possibles
1. **Webhook Expo** : Réception des erreurs
2. **Métriques avancées** : Dashboard de monitoring
3. **Notifications groupées** : Envoi à plusieurs utilisateurs
4. **A/B testing** : Tests de différents formats

## 📞 Support

### En cas de problème
1. Vérifier les logs Laravel
2. Tester l'endpoint avec un token valide
3. Vérifier la connectivité réseau
4. Consulter la documentation Expo

### Ressources utiles
- [Documentation Expo Push Notifications](https://docs.expo.dev/push-notifications/overview/)
- [API Expo Push](https://docs.expo.dev/push-notifications/sending-notifications/)
- [Laravel Logging](https://laravel.com/docs/logging)

---

**Note :** Ce système est entièrement gratuit et ne nécessite aucune configuration supplémentaire côté backend. L'intégration côté frontend Expo est documentée dans la documentation officielle d'Expo. 
