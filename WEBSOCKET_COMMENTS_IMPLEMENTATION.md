# Implémentation WebSocket pour Commentaires en Temps Réel - Sessions Sportives

## 📋 Vue d'ensemble

### Objectif
Implémenter un système de commentaires en temps réel pour les sessions sportives utilisant WebSocket, permettant aux participants d'échanger des messages instantanément pendant une session.

### Architecture Cible
- **Backend :** Laravel avec Laravel WebSockets (solution gratuite)
- **Frontend :** Expo React Native avec Socket.IO client
- **Base de données :** PostgreSQL (existante)
- **Authentification :** Sanctum tokens

## 🏗️ Architecture Technique

### 1. Choix Technologiques

#### WebSocket Server
**Laravel WebSockets (Solution Gratuite)**
- Avantages :
  - Intégration native avec Laravel
  - Compatible avec Pusher API
  - Gratuit et open-source
  - Facile à déployer
  - Support des canaux privés et de présence
  - Contrôle total sur l'infrastructure
  - Pas de dépendance externe

#### Client WebSocket
**Socket.IO Client pour Expo**
- Compatible avec React Native
- Reconnexion automatique
- Gestion des événements
- Support des rooms/channels

### 2. Structure des Données

#### Tables de Base de Données

**Table `sport_session_comments` (existante)**
```sql
- id (UUID, primary key)
- sport_session_id (UUID, foreign key)
- user_id (UUID, foreign key)
- content (TEXT)
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)
- deleted_at (TIMESTAMP, soft delete)
```

**Nouvelles Tables Requises**

**Table `sport_session_comment_mentions`**
```sql
- id (UUID, primary key)
- comment_id (UUID, foreign key)
- mentioned_user_id (UUID, foreign key)
- created_at (TIMESTAMP)
```

### 3. Canaux WebSocket

#### Structure des Canaux

**Canal Public : `sport-session.{sessionId}`**
- Accès : Tous les participants de la session
- Événements :
  - `comment.created`
  - `comment.updated`
  - `comment.deleted`
  - `user.typing`
  - `user.online`
  - `user.offline`

**Canal Privé : `private-sport-session.{sessionId}`**
- Accès : Participants authentifiés uniquement
- Événements :
  - `comment.created`
  - `comment.updated`
  - `comment.deleted`

**Canal de Présence : `presence-sport-session.{sessionId}`**
- Accès : Participants authentifiés uniquement
- Fonctionnalités :
  - Liste des utilisateurs en ligne
  - Notifications de connexion/déconnexion
  - Indicateurs de frappe

## 🔧 Implémentation Backend

### 1. Installation et Configuration

#### Dépendances Requises
```bash
composer require pusher/pusher-php-server
composer require beyondcode/laravel-websockets
```

#### Configuration Laravel WebSockets
- Configuration dans `config/websockets.php`
- Configuration dans `config/broadcasting.php`
- Variables d'environnement dans `.env`

#### Configuration Broadcasting
- Driver : `pusher` ou `websockets`
- Configuration des canaux privés
- Configuration de l'authentification

### 2. Structure des Fichiers

#### Contrôleurs
```
app/Http/Controllers/SportSession/Comment/
├── CreateCommentAction.php
├── UpdateCommentAction.php
├── DeleteCommentAction.php
└── GetSessionCommentsAction.php
```

#### Use Cases
```
app/UseCases/SportSession/Comment/
├── CreateCommentUseCase.php
├── UpdateCommentUseCase.php
├── DeleteCommentUseCase.php
└── GetSessionCommentsUseCase.php
```

#### Repositories
```
app/Repositories/SportSession/Comment/
├── CommentRepository.php
├── CommentRepositoryInterface.php
├── CommentMentionRepository.php
└── CommentMentionRepositoryInterface.php
```

#### Models
```
app/Models/
├── SportSessionCommentModel.php (existant)
└── SportSessionCommentMentionModel.php
```

#### Events
```
app/Events/SportSession/
├── CommentCreated.php
├── CommentUpdated.php
├── CommentDeleted.php
├── UserTyping.php
└── UserOnline.php
```

#### Listeners
```
app/Listeners/SportSession/
├── BroadcastCommentCreated.php
├── BroadcastCommentUpdated.php
├── BroadcastCommentDeleted.php
├── SendCommentNotification.php
└── UpdateCommentCount.php
```

### 3. Authentification WebSocket

#### Middleware d'Authentification
- Création d'un middleware `WebSocketAuth`
- Validation des tokens Sanctum
- Gestion des canaux privés
- Vérification des permissions de session

#### Configuration des Canaux Privés
- Routes dans `routes/channels.php`
- Vérification des participants de session
- Gestion des permissions

### 4. Gestion des Événements

#### Événements de Commentaires
- `CommentCreated` : Nouveau commentaire
- `CommentUpdated` : Modification de commentaire
- `CommentDeleted` : Suppression de commentaire

#### Événements de Présence
- `UserTyping` : Indicateur de frappe
- `UserOnline` : Connexion utilisateur
- `UserOffline` : Déconnexion utilisateur

### 5. API REST Endpoints

#### Endpoints de Commentaires
```
POST   /api/sessions/{sessionId}/comments
GET    /api/sessions/{sessionId}/comments
PUT    /api/sessions/{sessionId}/comments/{commentId}
DELETE /api/sessions/{sessionId}/comments/{commentId}
```

#### Endpoints de Présence
```
POST   /api/sessions/{sessionId}/presence/join
POST   /api/sessions/{sessionId}/presence/leave
POST   /api/sessions/{sessionId}/presence/typing
GET    /api/sessions/{sessionId}/presence/users
```

## 📱 API Endpoints Disponibles

### 1. Endpoints de Commentaires

#### Créer un commentaire
```
POST /api/sessions/{sessionId}/comments
Content-Type: application/json
Authorization: Bearer {token}

{
  "content": "Super session !",
  "mentions": ["user_id_1", "user_id_2"] // optionnel
}

Response:
{
  "success": true,
  "data": {
    "id": "uuid",
    "content": "Super session !",
    "user": {
      "id": "user_uuid",
      "firstname": "John",
      "lastname": "Doe",
      "avatar": null
    },
    "mentions": [
      {
        "id": "user_id_1",
        "firstname": "Jane",
        "lastname": "Smith"
      }
    ],
    "created_at": "2025-01-20T10:30:00.000000Z"
  },
  "message": "Commentaire créé avec succès"
}
```

#### Récupérer les commentaires d'une session
```
GET /api/sessions/{sessionId}/comments?page=1&limit=20
Authorization: Bearer {token}

Response:
{
  "success": true,
  "data": [
    {
      "id": "comment_uuid",
      "content": "Super session !",
      "user": {
        "id": "user_uuid",
        "firstname": "John",
        "lastname": "Doe",
        "avatar": null
      },
      "mentions": [...],
      "created_at": "2025-01-20T10:30:00.000000Z",
      "updated_at": "2025-01-20T10:30:00.000000Z"
    }
  ],
  "pagination": {
    "page": 1,
    "limit": 20,
    "total": 45,
    "totalPages": 3
  }
}
```

#### Modifier un commentaire
```
PUT /api/sessions/{sessionId}/comments/{commentId}
Content-Type: application/json
Authorization: Bearer {token}

{
  "content": "Commentaire modifié",
  "mentions": ["user_id_1"]
}

Response:
{
  "success": true,
  "data": {
    "id": "comment_uuid",
    "content": "Commentaire modifié",
    "user": {...},
    "mentions": [...],
    "updated_at": "2025-01-20T10:35:00.000000Z"
  },
  "message": "Commentaire modifié avec succès"
}
```

#### Supprimer un commentaire
```
DELETE /api/sessions/{sessionId}/comments/{commentId}
Authorization: Bearer {token}

Response:
{
  "success": true,
  "message": "Commentaire supprimé avec succès"
}
```

### 2. Endpoints de Présence

#### Rejoindre une session (présence en ligne)
```
POST /api/sessions/{sessionId}/presence/join
Authorization: Bearer {token}

Response:
{
  "success": true,
  "data": {
    "sessionId": "session_uuid",
    "userId": "user_uuid",
    "joinedAt": "2025-01-20T10:30:00.000000Z"
  },
  "message": "Utilisateur connecté à la session"
}
```

#### Quitter une session
```
POST /api/sessions/{sessionId}/presence/leave
Authorization: Bearer {token}

Response:
{
  "success": true,
  "data": {
    "sessionId": "session_uuid",
    "userId": "user_uuid",
    "leftAt": "2025-01-20T10:35:00.000000Z"
  },
  "message": "Utilisateur déconnecté de la session"
}
```

#### Indiquer que l'utilisateur tape
```
POST /api/sessions/{sessionId}/presence/typing
Authorization: Bearer {token}

{
  "isTyping": true
}

Response:
{
  "success": true,
  "data": {
    "sessionId": "session_uuid",
    "userId": "user_uuid",
    "isTyping": true,
    "timestamp": "2025-01-20T10:30:00.000000Z"
  }
}
```

#### Récupérer les utilisateurs en ligne
```
GET /api/sessions/{sessionId}/presence/users
Authorization: Bearer {token}

Response:
{
  "success": true,
  "data": [
    {
      "id": "user_uuid",
      "firstname": "John",
      "lastname": "Doe",
      "avatar": null,
      "isTyping": false,
      "lastSeen": "2025-01-20T10:30:00.000000Z"
    }
  ],
  "total": 5
}
```

### 3. Événements WebSocket Disponibles

#### Canaux de Diffusion
- **Canal Public :** `sport-session.{sessionId}`
- **Canal Privé :** `private-sport-session.{sessionId}`
- **Canal de Présence :** `presence-sport-session.{sessionId}`

#### Événements Émis par l'API

**Événements de Commentaires :**
```json
// Nouveau commentaire
{
  "event": "comment.created",
  "data": {
    "comment": {
      "id": "comment_uuid",
      "content": "Super session !",
      "user": {...},
      "mentions": [...],
      "created_at": "2025-01-20T10:30:00.000000Z"
    }
  }
}

// Commentaire modifié
{
  "event": "comment.updated",
  "data": {
    "comment": {
      "id": "comment_uuid",
      "content": "Commentaire modifié",
      "user": {...},
      "mentions": [...],
      "updated_at": "2025-01-20T10:35:00.000000Z"
    }
  }
}

// Commentaire supprimé
{
  "event": "comment.deleted",
  "data": {
    "commentId": "comment_uuid",
    "deletedAt": "2025-01-20T10:40:00.000000Z"
  }
}
```

**Événements de Présence :**
```json
// Utilisateur en train de taper
{
  "event": "user.typing",
  "data": {
    "userId": "user_uuid",
    "user": {
      "id": "user_uuid",
      "firstname": "John",
      "lastname": "Doe"
    },
    "isTyping": true,
    "timestamp": "2025-01-20T10:30:00.000000Z"
  }
}

// Utilisateur connecté
{
  "event": "user.online",
  "data": {
    "userId": "user_uuid",
    "user": {
      "id": "user_uuid",
      "firstname": "John",
      "lastname": "Doe"
    },
    "joinedAt": "2025-01-20T10:30:00.000000Z"
  }
}

// Utilisateur déconnecté
{
  "event": "user.offline",
  "data": {
    "userId": "user_uuid",
    "user": {
      "id": "user_uuid",
      "firstname": "John",
      "lastname": "Doe"
    },
    "leftAt": "2025-01-20T10:35:00.000000Z"
  }
}
```

### 4. Codes d'Erreur

#### Erreurs de Commentaires
```json
// Commentaire non trouvé
{
  "success": false,
  "error": {
    "code": "COMMENT_NOT_FOUND",
    "message": "Commentaire non trouvé"
  }
}

// Permission refusée
{
  "success": false,
  "error": {
    "code": "FORBIDDEN",
    "message": "Vous n'êtes pas autorisé à modifier ce commentaire"
  }
}

// Contenu invalide
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Le contenu du commentaire est requis et doit contenir entre 1 et 1000 caractères"
  }
}
```

#### Erreurs de Présence
```json
// Session non trouvée
{
  "success": false,
  "error": {
    "code": "SESSION_NOT_FOUND",
    "message": "Session non trouvée"
  }
}

// Utilisateur non participant
{
  "success": false,
  "error": {
    "code": "NOT_PARTICIPANT",
    "message": "Vous devez être participant de cette session"
  }
}
```

## 🔒 Sécurité et Permissions

### 1. Authentification
- Validation des tokens Sanctum
- Vérification des permissions de session
- Gestion des sessions expirées

### 2. Autorisation
- Vérification des participants de session
- Permissions de lecture/écriture
- Gestion des sessions privées/publiques

### 3. Validation des Données
- Validation des contenus de commentaires
- Protection contre les attaques XSS
- Limitation de la taille des messages
- Filtrage des contenus inappropriés

### 4. Rate Limiting
- Limitation du nombre de commentaires par minute
- Protection contre le spam

## 📊 Performance et Scalabilité

### 1. Optimisations Base de Données
- Index sur les colonnes fréquemment utilisées
- Pagination des commentaires
- Optimisation des requêtes N+1

### 2. Optimisations WebSocket
- Compression des messages
- Batching des événements
- Gestion des connexions inactives
- Load balancing des serveurs WebSocket

### 3. Monitoring
- Métriques de performance
- Logs des événements
- Alertes en cas de problème
- Monitoring des connexions actives

## 🧪 Tests

### 1. Tests Unitaires
- Tests des Use Cases
- Tests des Repositories
- Tests des Events
- Tests des Validations

### 2. Tests d'Intégration
- Tests des API endpoints
- Tests des WebSocket events
- Tests d'authentification
- Tests de permissions

### 3. Tests E2E
- Tests de flux complets
- Tests de reconnexion
- Tests de performance
- Tests de charge

## 🚀 Déploiement sur VPS avec Coolify

### 1. Configuration Coolify
- **Plateforme :** VPS avec Coolify
- **Build System :** Nixpacks (configuration existante)
- **Process Manager :** Supervisor (déjà configuré)
- **Web Server :** Nginx + PHP-FPM (déjà configuré)

### 2. Configuration WebSocket pour Production
- **Port WebSocket :** 6001 (par défaut Laravel WebSockets)
- **Process WebSocket :** Ajout d'un worker supervisor pour Laravel WebSockets
- **Reverse Proxy :** Configuration Nginx pour proxy WebSocket

### 3. Variables d'Environnement Coolify
```env
# Broadcasting
BROADCAST_DRIVER=pusher
PUSHER_APP_ID=laravel_websockets_app_id
PUSHER_APP_KEY=laravel_websockets_app_key
PUSHER_APP_SECRET=laravel_websockets_app_secret
PUSHER_HOST=127.0.0.1
PUSHER_PORT=6001
PUSHER_SCHEME=http
PUSHER_APP_CLUSTER=mt1

# WebSocket Server
LARAVEL_WEBSOCKETS_SSL_LOCAL_CERT=null
LARAVEL_WEBSOCKETS_SSL_LOCAL_PK=null
LARAVEL_WEBSOCKETS_SSL_PASSPHRASE=null
LARAVEL_WEBSOCKETS_SSL_VERIFY_PEER=false
```

### 4. Configuration Nginx pour WebSocket
- Ajout de la configuration WebSocket dans le template Nginx
- Proxy des connexions WebSocket vers le serveur Laravel WebSockets
- Support SSL/TLS pour les connexions sécurisées

### 5. Process Supervisor pour WebSocket
- Ajout d'un worker `worker-websocket.conf` dans la configuration
- Gestion automatique du redémarrage du serveur WebSocket
- Logs dédiés pour le monitoring

### 6. Monitoring et Logs
- Logs WebSocket dans `/var/log/worker-websocket.log`
- Métriques de performance via Laravel WebSockets Dashboard
- Intégration avec les logs existants de Coolify

## 📋 Checklist d'Implémentation

### Phase 1 : Infrastructure
- [ ] Installation des dépendances WebSocket
- [ ] Configuration Laravel WebSockets
- [ ] Configuration broadcasting
- [ ] Création des migrations de base de données
- [ ] Configuration de l'authentification WebSocket

### Phase 2 : Backend Core
- [ ] Création des Models
- [ ] Implémentation des Repositories
- [ ] Création des Use Cases
- [ ] Implémentation des Contrôleurs
- [ ] Création des Events et Listeners

### Phase 3 : WebSocket Events
- [ ] Implémentation des événements de commentaires
- [ ] Implémentation des événements de présence
- [ ] Gestion des canaux privés
- [ ] Tests des événements

### Phase 4 : API REST
- [ ] Implémentation des endpoints CRUD
- [ ] Implémentation des endpoints de présence
- [ ] Tests des endpoints

### Phase 5 : Configuration Production
- [ ] Configuration Laravel WebSockets pour Coolify
- [ ] Ajout du worker WebSocket dans supervisor
- [ ] Configuration Nginx pour proxy WebSocket
- [ ] Tests de déploiement

### Phase 6 : Tests et Optimisation
- [ ] Tests unitaires
- [ ] Tests d'intégration
- [ ] Tests de performance
- [ ] Tests WebSocket en production
- [ ] Optimisations

### Phase 7 : Documentation API
- [ ] Documentation des endpoints
- [ ] Documentation des événements WebSocket
- [ ] Exemples d'utilisation
- [ ] Guide d'intégration pour les clients

## 🎯 Considérations Spéciales

### 1. Gestion des Déconnexions
- Sauvegarde des messages non envoyés
- Reconnexion automatique
- Synchronisation des messages manqués

### 2. Gestion des Sessions Longues
- Optimisation mémoire
- Nettoyage des connexions inactives
- Gestion des timeouts

### 3. Accessibilité
- Support des lecteurs d'écran
- Navigation au clavier
- Indicateurs visuels et sonores

### 4. Internationalisation
- Support multi-langues
- Formatage des dates/heures
- Direction du texte (RTL/LTR)

## 📚 Ressources et Documentation

### Documentation Officielle
- Laravel WebSockets : https://beyondco.de/docs/laravel-websockets/
- Laravel Broadcasting : https://laravel.com/docs/broadcasting
- Pusher Protocol : https://pusher.com/docs/channels/library_auth_reference/pusher-websockets-protocol

### Outils de Développement
- Laravel WebSockets Dashboard (`/laravel-websockets`)
- Postman pour tests API
- WebSocket Client pour tests (browser console)

### Monitoring et Debugging
- Laravel Telescope
- Laravel WebSockets Dashboard
- Logs supervisor dans `/var/log/worker-websocket.log`
- Nginx access/error logs 
