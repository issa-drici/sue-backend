# API Notifications - Documentation Backend

## Vue d'ensemble

Ce document détaille tous les endpoints de gestion des notifications pour l'application Alarrache.

## Base URL
```
https://api.alarrache.com/api
```

## Endpoints

### 0. Push Tokens

#### POST /push-tokens

Enregistrer ou mettre à jour un token Expo pour l'utilisateur authentifié.

Headers:
```
Authorization: Bearer <token>
Content-Type: application/json
```

Body:
```json
{ "token": "ExponentPushToken[xxxx]", "platform": "expo|ios|android", "device_id": "optional" }
```

Réponse 200:
```json
{ "success": true }
```

Erreurs: `VALIDATION_ERROR`, `TOKEN_SAVE_ERROR`

#### DELETE /push-tokens

Supprimer un token Expo.

Body:
```json
{ "token": "ExponentPushToken[xxxx]" }
```

Réponse 200:
```json
{ "success": true }
```

Erreurs: `VALIDATION_ERROR`, `TOKEN_NOT_FOUND_OR_ALREADY_DELETED`

---

### 1. GET /notifications

**Description :** Récupérer toutes les notifications de l'utilisateur

**URL :** `/notifications`

**Méthode :** `GET`

**Headers :**
```
Authorization: Bearer <token>
```

**Query Parameters :**
```
?page=1&limit=20&unreadOnly=true
```

**Réponse Succès (200) :**
```json
{
  "success": true,
  "data": [
    {
      "id": "1",
      "type": "invitation",
      "title": "Nouvelle invitation",
      "message": "Jean Dupont vous invite à une session de tennis",
      "sessionId": "1",
      "createdAt": "2024-03-20T10:00:00Z",
      "read": false
    },
    {
      "id": "2",
      "type": "invitation",
      "title": "Nouvelle invitation",
      "message": "Marie Martin vous invite à une session de football",
      "sessionId": "2",
      "createdAt": "2024-03-20T09:00:00Z",
      "read": false
    },
    {
      "id": "3",
      "type": "reminder",
      "title": "Rappel de session",
      "message": "Votre session de tennis commence dans 1 heure",
      "sessionId": "3",
      "createdAt": "2024-03-20T08:00:00Z",
      "read": true
    },
    {
      "id": "4",
      "type": "update",
      "title": "Session modifiée",
      "message": "La session de golf a été reportée à 16h00",
      "sessionId": "2",
      "createdAt": "2024-03-20T07:00:00Z",
      "read": false
    }
  ],
  "pagination": {
    "page": 1,
    "limit": 20,
    "total": 45,
    "totalPages": 3
  },
  "unreadCount": 15
}
```

### 2. PATCH /notifications/{id}/read

**Description :** Marquer une notification comme lue

**URL :** `/notifications/{id}/read`

**Méthode :** `PATCH`

**Headers :**
```
Authorization: Bearer <token>
Content-Type: application/json
```

**Body :**
```json
{
  "read": true
}
```

**Réponse Succès (200) :**
```json
{
  "success": true,
  "data": {
    "id": "1",
    "type": "invitation",
    "title": "Nouvelle invitation",
    "message": "Jean Dupont vous invite à une session de tennis",
    "sessionId": "1",
    "createdAt": "2024-03-20T10:00:00Z",
    "read": true
  },
  "message": "Notification marquée comme lue"
}
```

**Réponse Erreur (404) :**
```json
{
  "success": false,
  "error": {
    "code": "NOTIFICATION_NOT_FOUND",
    "message": "Notification non trouvée"
  }
}
```

### 3. PATCH /notifications/read-all

**Description :** Marquer toutes les notifications comme lues

**URL :** `/notifications/read-all`

**Méthode :** `PATCH`

**Headers :**
```
Authorization: Bearer <token>
Content-Type: application/json
```

**Body :**
```json
{
  "read": true
}
```

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

### 4. DELETE /notifications/{id}

**Description :** Supprimer une notification

**URL :** `/notifications/{id}`

**Méthode :** `DELETE`

**Headers :**
```
Authorization: Bearer <token>
```

**Réponse Succès (200) :**
```json
{
  "success": true,
  "message": "Notification supprimée"
}
```

### 5. GET /notifications/unread-count

**Description :** Récupérer le nombre de notifications non lues

**URL :** `/notifications/unread-count`

**Méthode :** `GET`

**Headers :**
```
Authorization: Bearer <token>
```

**Réponse Succès (200) :**
```json
{
  "success": true,
  "data": {
    "count": 15
  }
}
```

## Types de notifications

### 1. invitation
**Déclencheur :** Invitation à une session
**Données :**
```json
{
  "sessionId": "1",
  "sessionTitle": "Match de tennis",
  "organizerId": "1",
  "organizerName": "Jean Dupont"
}
```

### 2. reminder
**Déclencheur :** Rappel de session (24h avant)
**Données :**
```json
{
  "sessionId": "1",
  "sessionTitle": "Match de tennis",
  "sessionDate": "2024-03-25",
  "sessionTime": "18:00"
}
```

### 3. update
**Déclencheur :** Session modifiée
**Données :**
```json
{
  "sessionId": "1",
  "sessionTitle": "Match de tennis",
  "changes": ["time", "location"],
  "organizerId": "1",
  "organizerName": "Jean Dupont"
}
```

## Codes d'erreur

| Code | Description |
|------|-------------|
| `NOTIFICATION_NOT_FOUND` | Notification non trouvée |
| `UNAUTHORIZED` | Token invalide ou manquant |
| `FORBIDDEN` | Accès non autorisé |
| `VALIDATION_ERROR` | Erreur de validation des données |

## Validation

### Marquer comme lue
- **read** : Requis, boolean

## Entités

### Notification
```json
{
  "id": "string",
  "type": "invitation | reminder | update",
  "title": "string",
  "message": "string",
  "sessionId": "string",
  "createdAt": "string (ISO 8601)",
  "read": "boolean"
}
```

### NotificationType
```json
"invitation" | "reminder" | "update"
```

## Logique métier

### Règles de génération automatique

#### Sessions
1. **invitation** : Quand un utilisateur est invité à une session
2. **reminder** : 24h avant le début d'une session (notifier tous les participants acceptés)
3. **update** : Quand une session est modifiée (notifier tous les participants)

### Règles de suppression
- Les notifications sont automatiquement supprimées après 30 jours
- Les notifications liées à une session supprimée sont supprimées
- Les notifications liées à un utilisateur supprimé sont supprimées

### Règles d'affichage
- Seul l'utilisateur peut voir ses propres notifications
- Les notifications sont triées par date de création (plus récentes en premier)
- Le badge de notifications non lues est mis à jour en temps réel

## Webhooks (optionnel)

### Endpoint pour les notifications push
```
POST /notifications/push
```

**Headers :**
```
Authorization: Bearer <token>
Content-Type: application/json
```

**Body :**
```json
{
  "userId": "1",
  "notification": {
    "type": "invitation",
    "title": "Nouvelle invitation",
    "message": "Jean Dupont vous invite à une session de tennis"
  }
}
```

## Tests recommandés

### Tests unitaires
- Génération des différents types de notifications
- Validation des données de notification
- Logique de marquage comme lue

### Tests d'intégration
- Création automatique de notifications lors d'actions utilisateur
- Suppression automatique des notifications obsolètes
- Comptage des notifications non lues

### Tests de performance
- Pagination des notifications
- Temps de réponse pour les requêtes fréquentes 
