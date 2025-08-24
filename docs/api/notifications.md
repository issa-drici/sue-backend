# API Notifications - Documentation Complète

## 📋 Vue d'ensemble

Ce document détaille tous les endpoints de gestion des notifications pour l'application Alarrache, basé sur l'analyse complète du code backend.

## 🌐 Base URL
```
https://api.alarrache.com/api
```

## 🔐 Authentification

Tous les endpoints de notifications nécessitent une authentification via Bearer Token :
```
Authorization: Bearer <token>
Content-Type: application/json
```

## 📊 Types de notifications supportés

### Types disponibles (basé sur les migrations)
- **invitation** : Invitation à une session sportive
- **reminder** : Rappel de session
- **update** : Mise à jour générale
- **comment** : Nouveau commentaire sur une session
- **session_update** : Modification d'une session
- **session_cancelled** : Session annulée

## 🚀 Endpoints disponibles

### 1. GET /notifications

**Description :** Récupérer la liste paginée des notifications de l'utilisateur connecté

**URL :** `GET /notifications`

**Authentification :** ✅ Requise (Bearer Token)

**Query Parameters :**
- `page` (int, optionnel) : Numéro de page (défaut: 1)
- `limit` (int, optionnel) : Nombre d'éléments par page (défaut: 20, max: 50)

**Réponse Succès (200) :**
```json
{
  "success": true,
  "data": [
    {
      "id": "550e8400-e29b-41d4-a716-446655440000",
      "user_id": "550e8400-e29b-41d4-a716-446655440001",
      "type": "invitation",
      "title": "Nouvelle invitation",
      "message": "Jean Dupont vous invite à une session de tennis",
      "session_id": "550e8400-e29b-41d4-a716-446655440002",
      "created_at": "2024-03-20T10:00:00+00:00",
      "read": false,
      "push_sent": true,
      "push_sent_at": "2024-03-20T10:00:00+00:00",
      "push_data": {
        "sessionTitle": "Match de tennis",
        "organizerName": "Jean Dupont"
      }
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

**Réponses d'erreur :**
- `401` : Token invalide ou expiré
- `500` : Erreur serveur interne

**Exemple d'utilisation :**
```bash
curl -X GET "https://api.alarrache.com/api/notifications?page=1&limit=20" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json"
```

---

### 2. GET /notifications/unread-count

**Description :** Récupérer le nombre de notifications non lues

**URL :** `GET /notifications/unread-count`

**Authentification :** ✅ Requise (Bearer Token)

**Réponse Succès (200) :**
```json
{
  "success": true,
  "data": {
    "unreadCount": 15
  }
}
```

**Réponses d'erreur :**
- `401` : Token invalide ou expiré
- `500` : Erreur serveur interne

**Exemple d'utilisation :**
```bash
curl -X GET "https://api.alarrache.com/api/notifications/unread-count" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

### 3. PATCH /notifications/{id}/read

**Description :** Marquer une notification spécifique comme lue

**URL :** `PATCH /notifications/{id}/read`

**Authentification :** ✅ Requise (Bearer Token)

**Path Parameters :**
- `id` (string, requis) : UUID de la notification

**Réponse Succès (200) :**
```json
{
  "success": true,
  "data": {
    "id": "550e8400-e29b-41d4-a716-446655440000",
    "user_id": "550e8400-e29b-41d4-a716-446655440001",
    "type": "invitation",
    "title": "Nouvelle invitation",
    "message": "Jean Dupont vous invite à une session de tennis",
    "session_id": "550e8400-e29b-41d4-a716-446655440002",
    "created_at": "2024-03-20T10:00:00+00:00",
    "read": true,
    "push_sent": true,
    "push_sent_at": "2024-03-20T10:00:00+00:00",
    "push_data": null
  },
  "message": "Notification marquée comme lue"
}
```

**Réponses d'erreur :**
- `401` : Token invalide ou expiré
- `403` : Accès non autorisé (notification n'appartient pas à l'utilisateur)
- `404` : Notification non trouvée
- `500` : Erreur serveur interne

**Exemple d'utilisation :**
```bash
curl -X PATCH "https://api.alarrache.com/api/notifications/550e8400-e29b-41d4-a716-446655440000/read" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json"
```

---

### 4. PATCH /notifications/read-all

**Description :** Marquer toutes les notifications de l'utilisateur comme lues

**URL :** `PATCH /notifications/read-all`

**Authentification :** ✅ Requise (Bearer Token)

**Réponse Succès (200) :**
```json
{
  "success": true,
  "data": {
    "updatedCount": 15
  },
  "message": "Toutes les notifications ont été marquées comme lues"
}
```

**Réponses d'erreur :**
- `401` : Token invalide ou expiré
- `500` : Erreur serveur interne

**Exemple d'utilisation :**
```bash
curl -X PATCH "https://api.alarrache.com/api/notifications/read-all" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json"
```

---

### 5. DELETE /notifications/{id}

**Description :** Supprimer une notification spécifique

**URL :** `DELETE /notifications/{id}`

**Authentification :** ✅ Requise (Bearer Token)

**Path Parameters :**
- `id` (string, requis) : UUID de la notification

**Réponse Succès (200) :**
```json
{
  "success": true,
  "message": "Notification supprimée"
}
```

**Réponses d'erreur :**
- `401` : Token invalide ou expiré
- `403` : Accès non autorisé (notification n'appartient pas à l'utilisateur)
- `404` : Notification non trouvée
- `500` : Erreur serveur interne

**Exemple d'utilisation :**
```bash
curl -X DELETE "https://api.alarrache.com/api/notifications/550e8400-e29b-41d4-a716-446655440000" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

### 6. POST /notifications/push

**Description :** Envoyer une notification push (endpoint de test/développement)

**URL :** `POST /notifications/push`

**Authentification :** ✅ Requise (Bearer Token)

**Body Parameters :**
- `userId` (string, requis) : ID de l'utilisateur destinataire
- `notification` (object, requis) : Données de la notification

**Exemple de body :**
```json
{
  "userId": "550e8400-e29b-41d4-a716-446655440001",
  "notification": {
    "type": "invitation",
    "title": "Nouvelle invitation",
    "message": "Jean Dupont vous invite à une session de tennis"
  }
}
```

**Réponse Succès (201) :**
```json
{
  "success": true,
  "data": {
    "userId": "550e8400-e29b-41d4-a716-446655440001",
    "notification": {
      "type": "invitation",
      "title": "Nouvelle invitation",
      "message": "Jean Dupont vous invite à une session de tennis"
    },
    "sent": true
  },
  "message": "Notification push envoyée"
}
```

**Réponses d'erreur :**
- `400` : Données invalides (userId et notification requis)
- `401` : Token invalide ou expiré
- `500` : Erreur serveur interne

---

### 7. POST /notifications/send

**Description :** Envoyer une notification push via Expo (production)

**URL :** `POST /notifications/send`

**Authentification :** ✅ Requise (Bearer Token)

**Body Parameters :**
- `recipientId` (string, requis) : UUID de l'utilisateur destinataire
- `title` (string, requis, max: 255) : Titre de la notification
- `body` (string, requis, max: 1000) : Corps de la notification
- `data` (object, optionnel) : Données supplémentaires

**Exemple de body :**
```json
{
  "recipientId": "550e8400-e29b-41d4-a716-446655440001",
  "title": "Nouvelle invitation",
  "body": "Jean Dupont vous invite à une session de tennis",
  "data": {
    "sessionId": "550e8400-e29b-41d4-a716-446655440002",
    "type": "invitation"
  }
}
```

**Réponse Succès (200) :**
```json
{
  "success": true,
  "data": {
    "recipientId": "550e8400-e29b-41d4-a716-446655440001",
    "tokensCount": 2,
    "title": "Nouvelle invitation",
    "body": "Jean Dupont vous invite à une session de tennis",
    "data": {
      "sessionId": "550e8400-e29b-41d4-a716-446655440002",
      "type": "invitation"
    },
    "result": {
      "success": true,
      "invalid_tokens": []
    }
  },
  "message": "Notification push envoyée avec succès"
}
```

**Réponses d'erreur :**
- `400` : Données invalides
- `401` : Token invalide ou expiré
- `404` : Aucun token push trouvé pour cet utilisateur
- `500` : Erreur lors de l'envoi de la notification push

---

## 🔧 Endpoints Push Tokens (liés aux notifications)

### 8. POST /push-tokens

**Description :** Enregistrer ou mettre à jour un token Expo pour l'utilisateur

**URL :** `POST /push-tokens`

**Authentification :** ✅ Requise (Bearer Token)

**Body Parameters :**
- `token` (string, requis) : Token Expo (format: ExponentPushToken[...])
- `platform` (string, optionnel) : Plateforme (expo|ios|android)
- `device_id` (string, optionnel) : ID unique de l'appareil

**Exemple de body :**
```json
{
  "token": "ExponentPushToken[xxxxxxxxxxxxxxxxxxxxxx]",
  "platform": "expo",
  "device_id": "device-123"
}
```

**Réponse Succès (200) :**
```json
{
  "success": true,
  "message": "Token push enregistré"
}
```

**Réponses d'erreur :**
- `400` : Données invalides
- `401` : Token invalide ou expiré
- `500` : Erreur serveur interne

---

### 9. DELETE /push-tokens

**Description :** Supprimer un token Expo

**URL :** `DELETE /push-tokens`

**Authentification :** ✅ Requise (Bearer Token)

**Body Parameters :**
- `token` (string, requis) : Token Expo à supprimer

**Exemple de body :**
```json
{
  "token": "ExponentPushToken[xxxxxxxxxxxxxxxxxxxxxx]"
}
```

**Réponse Succès (200) :**
```json
{
  "success": true,
  "message": "Token push supprimé"
}
```

**Réponses d'erreur :**
- `400` : Données invalides
- `401` : Token invalide ou expiré
- `500` : Erreur serveur interne

---

## 📊 Structure des données

### Notification Entity
```json
{
  "id": "string (UUID)",
  "user_id": "string (UUID)",
  "type": "invitation | reminder | update | comment | session_update | session_cancelled",
  "title": "string",
  "message": "string",
  "session_id": "string (UUID) | null",
  "created_at": "string (ISO 8601)",
  "read": "boolean",
  "push_sent": "boolean",
  "push_sent_at": "string (ISO 8601) | null",
  "push_data": "object | null"
}
```

### Types de notifications détaillés

#### invitation
**Déclencheur :** Invitation à une session sportive
**Données typiques :**
```json
{
  "session_id": "550e8400-e29b-41d4-a716-446655440002",
  "push_data": {
    "sessionTitle": "Match de tennis",
    "organizerName": "Jean Dupont",
    "sessionDate": "2024-03-25",
    "sessionTime": "18:00"
  }
}
```

#### reminder
**Déclencheur :** Rappel de session (24h avant)
**Données typiques :**
```json
{
  "session_id": "550e8400-e29b-41d4-a716-446655440002",
  "push_data": {
    "sessionTitle": "Match de tennis",
    "sessionDate": "2024-03-25",
    "sessionTime": "18:00",
    "location": "Tennis Club"
  }
}
```

#### update
**Déclencheur :** Mise à jour générale
**Données typiques :**
```json
{
  "push_data": {
    "message": "Mise à jour de l'application disponible"
  }
}
```

#### comment
**Déclencheur :** Nouveau commentaire sur une session
**Données typiques :**
```json
{
  "session_id": "550e8400-e29b-41d4-a716-446655440002",
  "push_data": {
    "sessionTitle": "Match de tennis",
    "commentAuthor": "Marie Martin",
    "commentText": "Super session !"
  }
}
```

#### session_update
**Déclencheur :** Modification d'une session
**Données typiques :**
```json
{
  "session_id": "550e8400-e29b-41d4-a716-446655440002",
  "push_data": {
    "sessionTitle": "Match de tennis",
    "changes": ["time", "location"],
    "organizerName": "Jean Dupont"
  }
}
```

#### session_cancelled
**Déclencheur :** Session annulée
**Données typiques :**
```json
{
  "session_id": "550e8400-e29b-41d4-a716-446655440002",
  "push_data": {
    "sessionTitle": "Match de tennis",
    "organizerName": "Jean Dupont",
    "reason": "Mauvais temps"
  }
}
```

## 🚨 Codes d'erreur

| Code | Description | HTTP Status |
|------|-------------|-------------|
| `NOTIFICATION_NOT_FOUND` | Notification non trouvée | 404 |
| `FORBIDDEN` | Accès non autorisé | 403 |
| `UNAUTHORIZED` | Token invalide ou manquant | 401 |
| `VALIDATION_ERROR` | Erreur de validation des données | 400 |
| `INTERNAL_ERROR` | Erreur serveur interne | 500 |
| `NO_TOKENS_FOUND` | Aucun token push trouvé | 404 |
| `PUSH_SEND_ERROR` | Erreur lors de l'envoi push | 500 |
| `TOKEN_SAVE_ERROR` | Erreur lors de l'enregistrement du token | 500 |
| `TOKEN_NOT_FOUND_OR_ALREADY_DELETED` | Token non trouvé ou déjà supprimé | 404 |

## 🔄 Logique métier

### Règles de sécurité
- Seul l'utilisateur peut voir et modifier ses propres notifications
- Vérification de propriété avant modification/suppression
- Authentification requise pour tous les endpoints

### Règles de pagination
- Pagination automatique pour la liste des notifications
- Tri par date de création (plus récentes en premier)
- Limite par défaut : 20 éléments
- Limite maximale : 50 éléments

### Règles de notifications push
- Enregistrement automatique des tokens Expo
- Nettoyage automatique des tokens invalides
- Support multi-appareils par utilisateur
- Données supplémentaires dans `push_data`

### Règles de suppression
- Suppression en cascade des notifications liées aux sessions supprimées
- Suppression en cascade des notifications liées aux utilisateurs supprimés
- Conservation des données de push pour audit

## 🧪 Tests recommandés

### Tests unitaires
- Validation des types de notifications
- Logique de marquage comme lue
- Gestion des erreurs d'authentification
- Pagination des résultats

### Tests d'intégration
- Création automatique de notifications lors d'actions utilisateur
- Envoi de notifications push
- Gestion des tokens invalides
- Comptage des notifications non lues

### Tests de performance
- Temps de réponse pour les requêtes fréquentes
- Gestion de la pagination avec de gros volumes
- Envoi de notifications push en masse

## 📱 Intégration mobile

### Endpoints prioritaires pour le mobile
1. `GET /notifications/unread-count` - Badge notifications
2. `GET /notifications` - Liste des notifications
3. `PATCH /notifications/{id}/read` - Marquer comme lue
4. `PATCH /notifications/read-all` - Marquer toutes comme lues
5. `POST /push-tokens` - Enregistrer token push

### Gestion des erreurs côté mobile
- Retry automatique pour les erreurs réseau
- Cache local des notifications
- Synchronisation des états lues/non lues
- Gestion des tokens push expirés

---

**Dernière mise à jour :** 22/01/2025
**Version :** 2.0
**Statut :** ✅ Complète et à jour 
