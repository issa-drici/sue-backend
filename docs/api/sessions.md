# API Sessions - Documentation Backend

## Vue d'ensemble

Ce document détaille tous les endpoints de gestion des sessions de sport pour l'application Alarrache.

## Base URL
```
https://api.alarrache.com/api
```

## Endpoints

### 1. GET /sessions

**Description :** Récupérer toutes les sessions de l'utilisateur connecté (sessions passées et futures)

**URL :** `/sessions`

**Méthode :** `GET`

**Headers :**
```
Authorization: Bearer <token>
```

**Query Parameters :**
```
?page=1&limit=20&sport=tennis&date=2024-01-15
```

**Tri :** Sessions triées par date croissante (de la plus proche à la plus éloignée)

### 2. GET /sessions/history

**Description :** Récupérer uniquement les sessions passées de l'utilisateur connecté (historique)

**URL :** `/sessions/history`

**Méthode :** `GET`

**Headers :**
```
Authorization: Bearer <token>
```

**Query Parameters :**
```
?page=1&limit=20&sport=tennis
```

**Réponse Succès (200) :**
```json
{
  "success": true,
  "data": [
    {
      "id": "1",
      "sport": "tennis",
      "date": "2024-03-25",
      "time": "18:00",
      "location": "Tennis Club de Paris",
      "organizer": {
        "id": "1",
        "firstname": "Jean",
        "lastname": "Dupont"
      },
      "participants": [
        {
          "id": "1",
          "firstname": "Jean",
          "lastname": "Dupont",
          "status": "accepted"
        },
        {
          "id": "2",
          "firstname": "Marie",
          "lastname": "Martin",
          "status": "pending"
        }
      ],
      "comments": [
        {
          "id": "1",
          "userId": "1",
          "firstname": "Jean",
          "lastname": "Dupont",
          "content": "N'oubliez pas vos raquettes !",
          "createdAt": "2024-03-20T10:00:00Z"
        }
      ]
    },
    {
      "id": "2",
      "sport": "golf",
      "date": "2024-03-26",
      "time": "14:00",
      "location": "Golf Club de Versailles",
      "organizer": {
        "id": "2",
        "firstname": "Marie",
        "lastname": "Martin"
      },
      "participants": [
        {
          "id": "1",
          "firstname": "Jean",
          "lastname": "Dupont",
          "status": "accepted"
        },
        {
          "id": "2",
          "firstname": "Marie",
          "lastname": "Martin",
          "status": "accepted"
        }
      ],
      "comments": []
    }
  ],
  "pagination": {
    "page": 1,
    "limit": 20,
    "total": 50,
    "totalPages": 3
  }
}
```

### 2. GET /sessions/{id}

**Description :** Récupérer une session spécifique

**URL :** `/sessions/{id}`

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
    "id": "1",
    "sport": "tennis",
    "date": "2024-03-25",
    "time": "18:00",
    "location": "Tennis Club de Paris",
    "organizer": {
      "id": "1",
      "firstname": "Jean",
      "lastname": "Dupont"
    },
    "participants": [
      {
        "id": "1",
        "firstname": "Jean",
        "lastname": "Dupont",
        "status": "accepted"
      },
      {
        "id": "2",
        "firstname": "Marie",
        "lastname": "Martin",
        "status": "pending"
      }
    ],
    "comments": [
      {
        "id": "1",
        "userId": "1",
        "firstname": "Jean",
        "lastname": "Dupont",
        "content": "N'oubliez pas vos raquettes !",
        "createdAt": "2024-03-20T10:00:00Z"
      }
    ]
  }
}
```

**Réponse Erreur (404) :**
```json
{
  "success": false,
  "error": {
    "code": "SESSION_NOT_FOUND",
    "message": "Session non trouvée"
  }
}
```

### 3. POST /sessions

**Description :** Créer une nouvelle session

**URL :** `/sessions`

**Méthode :** `POST`

**Headers :**
```
Authorization: Bearer <token>
Content-Type: application/json
```

**Body :**
```json
{
  "sport": "tennis",
  "date": "2024-03-25",
  "time": "18:00",
  "location": "Tennis Club de Paris"
}
```

**Réponse Succès (201) :**
```json
{
  "success": true,
  "data": {
    "id": "1",
    "sport": "tennis",
    "date": "2024-03-25",
    "time": "18:00",
    "location": "Tennis Club de Paris",
    "organizer": {
      "id": "1",
      "firstname": "Jean",
      "lastname": "Dupont"
    },
    "participants": [
      {
        "id": "1",
        "firstname": "Jean",
        "lastname": "Dupont",
        "status": "accepted"
      }
    ],
    "comments": []
  },
  "message": "Session créée avec succès"
}
```

### 4. POST /sessions/{id}/invite

**Description :** Inviter des utilisateurs à une session

**URL :** `/sessions/{id}/invite`

**Méthode :** `POST`

**Headers :**
```
Authorization: Bearer <token>
Content-Type: application/json
```

**Body :**
```json
{
  "userIds": ["2", "3", "4"]
}
```

**Réponse Succès (201) :**
```json
{
  "success": true,
  "data": {
    "sessionId": "1",
    "invitedUsers": [
      {
        "id": "2",
        "firstname": "Marie",
        "lastname": "Martin",
        "email": "marie@example.com"
      },
      {
        "id": "3",
        "firstname": "Bob",
        "lastname": "Johnson",
        "email": "bob@example.com"
      }
    ],
    "errors": []
  },
  "message": "2 utilisateur(s) invité(s) avec succès"
}
```

**Réponse Erreur (403) :**
```json
{
  "success": false,
  "error": {
    "code": "FORBIDDEN",
    "message": "Vous n'êtes pas autorisé à inviter des utilisateurs à cette session"
  }
}
```

**Réponse Erreur (404) :**
```json
{
  "success": false,
  "error": {
    "code": "SESSION_NOT_FOUND",
    "message": "Session non trouvée"
  }
}
```

**Réponse Erreur (400) :**
```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Données invalides",
    "details": {
      "userIds": ["Le champ userIds est requis"]
    }
  }
}
```

### 5. PUT /sessions/{id}

**Description :** Mettre à jour une session existante

**URL :** `/sessions/{id}`

**Méthode :** `PUT`

**Headers :**
```
Authorization: Bearer <token>
Content-Type: application/json
```

**Body :**
```json
{
  "sport": "tennis",
  "date": "2024-03-26",
  "time": "19:00",
  "location": "Tennis Club de Lyon"
}
```

**Réponse Succès (200) :**
```json
{
  "success": true,
  "data": {
    "id": "1",
    "sport": "tennis",
    "date": "2024-03-26",
    "time": "19:00",
    "location": "Tennis Club de Lyon",
    "organizer": {
      "id": "1",
      "firstname": "Jean",
      "lastname": "Dupont"
    },
    "participants": [
      {
        "id": "1",
        "firstname": "Jean",
        "lastname": "Dupont",
        "status": "accepted"
      },
      {
        "id": "2",
        "firstname": "Marie",
        "lastname": "Martin",
        "status": "pending"
      }
    ],
    "comments": []
  },
  "message": "Session mise à jour"
}
```

**Réponse Erreur (403) :**
```json
{
  "success": false,
  "error": {
    "code": "FORBIDDEN",
    "message": "Vous n'êtes pas autorisé à modifier cette session"
  }
}
```

### 6. DELETE /sessions/{id}

**Description :** Supprimer une session

**URL :** `/sessions/{id}`

**Méthode :** `DELETE`

**Headers :**
```
Authorization: Bearer <token>
```

**Réponse Succès (200) :**
```json
{
  "success": true,
  "message": "Session supprimée"
}
```

**Réponse Erreur (403) :**
```json
{
  "success": false,
  "error": {
    "code": "FORBIDDEN",
    "message": "Vous n'êtes pas autorisé à supprimer cette session"
  }
}
```

### 7. PATCH /sessions/{id}/respond

**Description :** Répondre à une invitation de session

**URL :** `/sessions/{id}/respond`

**Méthode :** `PATCH`

**Headers :**
```
Authorization: Bearer <token>
Content-Type: application/json
```

**Body :**
```json
{
  "response": "accept"
}
```

**Réponse Succès (200) :**
```json
{
  "success": true,
  "data": {
    "id": "1",
    "sport": "tennis",
    "participants": [
      {
        "id": "1",
        "firstname": "Jean",
        "lastname": "Dupont",
        "status": "accepted"
      },
      {
        "id": "2",
        "firstname": "Marie",
        "lastname": "Martin",
        "status": "accepted"
      }
    ]
  },
  "message": "Invitation acceptée"
}
```

**Réponse Erreur (403) :**
```json
{
  "success": false,
  "error": {
    "code": "FORBIDDEN",
    "message": "Vous n'êtes pas invité à cette session"
  }
}
```

**Réponse Erreur (400) :**
```json
{
  "success": false,
  "error": {
    "code": "INVALID_RESPONSE",
    "message": "Réponse invalide. Utilisez 'accept' ou 'decline'"
  }
}
```

**Réponse Erreur (400) - Limite de participants atteinte :**
```json
{
  "success": false,
  "error": {
    "code": "PARTICIPANT_LIMIT_REACHED",
    "message": "Impossible d'accepter l'invitation : la session a atteint sa limite de participants"
  }
}
```

**Commentaires système :** Lorsqu'un utilisateur accepte ou refuse une invitation, un commentaire système est automatiquement ajouté à la session avec le message :
- Acceptation : `"a accepté l'invitation à cette session ✅"`
- Refus : `"a décliné l'invitation à cette session ❌"`

Ce commentaire système déclenche également un événement WebSocket `comment.created` sur le canal `sport-session.{sessionId}` pour l'affichage en temps réel.

### 8. POST /sessions/{id}/comments

**Description :** Ajouter un commentaire à une session

**URL :** `/sessions/{id}/comments`

**Méthode :** `POST`

**Headers :**
```
Authorization: Bearer <token>
Content-Type: application/json
```

**Body :**
```json
{
  "content": "Super session !"
}
```

**Réponse Succès (201) :**
```json
{
  "success": true,
  "data": {
    "id": "2",
    "userId": "3",
    "firstname": "Bob",
    "lastname": "Johnson",
    "content": "Super session !",
    "createdAt": "2024-03-20T11:00:00Z"
  },
  "message": "Commentaire ajouté"
}
```

## Codes d'erreur

| Code | Description |
|------|-------------|
| `SESSION_NOT_FOUND` | Session non trouvée |
| `VALIDATION_ERROR` | Erreur de validation des données |
| `FORBIDDEN` | Accès non autorisé |
| `SESSION_FULL` | Session complète |
| `INVALID_RESPONSE` | Réponse d'invitation invalide |
| `UNAUTHORIZED` | Token invalide ou manquant |
| `USER_NOT_FOUND` | Utilisateur non trouvé |
| `ALREADY_INVITED` | Utilisateur déjà invité |
| `ALREADY_PARTICIPANT` | Utilisateur déjà participant |

## Validation

### Création/Mise à jour de session
- **sport** : Requis, valeurs autorisées : tennis, golf, musculation, football, basketball
- **date** : Requis, format YYYY-MM-DD, doit être dans le futur
- **time** : Requis, format HH:MM
- **location** : Requis, max 200 caractères

### Invitation
- **userIds** : Requis, tableau d'IDs utilisateurs valides
- **userIds[]** : Chaque ID doit correspondre à un utilisateur existant

### Réponse à invitation
- **response** : Requis, valeurs autorisées : "accept", "decline"

### Commentaires
- **content** : Requis, 1-500 caractères

## Entités

### SportSession
```json
{
  "id": "string",
  "sport": "tennis | golf | musculation | football | basketball",
  "date": "string (YYYY-MM-DD)",
  "time": "string (HH:MM)",
  "location": "string",
  "status": "active | cancelled | completed",
  "maxParticipants": "integer | null",
  "organizer": {
    "id": "string",
    "firstname": "string",
    "lastname": "string"
  },
  "participants": [
    {
      "id": "string",
      "firstname": "string",
      "lastname": "string",
      "status": "pending | accepted | declined"
    }
  ],
  "comments": [
    {
      "id": "string",
      "userId": "string",
      "firstname": "string",
      "lastname": "string",
      "content": "string",
      "createdAt": "string (ISO 8601)"
    }
  ]
}
```

### SessionParticipant
```json
{
  "id": "string",
  "firstname": "string",
  "lastname": "string",
  "status": "pending | accepted | declined"
}
```

### SessionComment
```json
{
  "id": "string",
  "userId": "string",
  "firstname": "string",
  "lastname": "string",
  "content": "string",
  "createdAt": "string (ISO 8601)"
}
```

## Logique métier

### Statuts de sessions
- **active** : Session normale, en cours
- **cancelled** : Session annulée par l'organisateur
- **completed** : Session terminée

### Statuts de participants
- **pending** : Invitation en attente
- **accepted** : Invitation acceptée
- **declined** : Invitation refusée

### Règles métier
1. Seul l'organisateur peut modifier/supprimer sa session
2. Seul l'organisateur peut inviter des utilisateurs à sa session
3. Les participants peuvent commenter même s'ils n'ont pas encore accepté
4. Les sessions passées ne peuvent plus être modifiées
5. L'organisateur est automatiquement ajouté comme participant accepté
6. Les commentaires sont publics pour tous les participants
7. Un utilisateur ne peut pas être invité s'il est déjà participant
8. Un utilisateur peut être réinvité s'il a décliné une invitation précédente
9. Seuls les utilisateurs invités peuvent répondre aux invitations

## Sports supportés
- tennis
- golf
- musculation
- football
- basketball 

# Endpoints des Sessions Sportives

## Créer une session sportive

**URL :** `POST /api/sessions`

**Méthode :** POST

**Authentification :** Requise (token Bearer)

### Paramètres de la requête

| Champ | Type | Requis | Description | Valeur par défaut |
|-------|------|--------|-------------|-------------------|
| `sport` | string | ✅ | Type de sport | - |
| `date` | string (Y-m-d) | ✅ | Date de la session | - |
| `time` | string (H:i) | ✅ | Heure de la session | - |
| `location` | string | ✅ | Lieu de la session | - |
| `maxParticipants` | integer \| null | ❌ | Nombre maximum de participants (null = illimité) | null |
| `participantIds` | array | ❌ | Liste des UUIDs des utilisateurs à inviter | [] |

### Sports supportés
- `tennis`
- `golf`
- `musculation`
- `football`
- `basketball`

### Exemple de requête

```json
{
    "sport": "tennis",
    "date": "2024-01-15",
    "time": "18:00",
    "location": "Tennis Club de Paris",
    "maxParticipants": 4,
    "participantIds": [
        "123e4567-e89b-12d3-a456-426614174000",
        "456e7890-e89b-12d3-a456-426614174001"
    ]
}
```

### Exemple de réponse (201 Created)

```json
{
    "success": true,
    "data": {
        "id": "789e0123-e89b-12d3-a456-426614174002",
        "sport": "tennis",
        "date": "2024-01-15",
        "time": "18:00:00",
        "location": "Tennis Club de Paris",
        "maxParticipants": 4,
        "organizer": {
            "id": "123e4567-e89b-12d3-a456-426614174000",
            "fullName": "John Doe"
        },
        "participants": [
            {
                "id": "123e4567-e89b-12d3-a456-426614174000",
                "fullName": "John Doe",
                "status": "accepted"
            }
        ],
        "comments": []
    },
    "message": "Session créée avec succès"
}
```

### Codes de réponse

- `201` : Session créée avec succès
- `400` : Erreur de validation ou données invalides
- `401` : Non authentifié
- `500` : Erreur interne du serveur

### Notes importantes

1. **Organisateur automatique** : L'utilisateur connecté devient automatiquement l'organisateur de la session
2. **Participant par défaut** : L'organisateur est automatiquement ajouté comme participant avec le statut "accepted"
3. **Participants invités** : Les utilisateurs dans `participantIds` sont ajoutés avec le statut "pending"
4. **Validation** : 
   - `maxParticipants` doit être entre 1 et 100, ou null (illimité)
   - `date` doit être aujourd'hui ou dans le futur
   - `participantIds` doit contenir des UUIDs valides
5. **Limite de participants** : Si `maxParticipants` est null, il n'y a pas de limite au nombre de participants

## Inviter des utilisateurs à une session existante

**URL :** `POST /api/sessions/{id}/invite`

**Méthode :** POST

**Authentification :** Requise (token Bearer)

### Paramètres de route
- `{id}` : L'ID de la session sportive (UUID)

### Corps de la requête

```json
{
    "userIds": ["uuid1", "uuid2", "uuid3"]
}
```

### Exemple de réponse (201 Created)

```json
{
    "success": true,
    "data": {
        "sessionId": "789e0123-e89b-12d3-a456-426614174002",
        "invitedUsers": [
            {
                "id": "123e4567-e89b-12d3-a456-426614174000",
                "firstname": "John",
                "lastname": "Doe",
                "email": "john@example.com"
            }
        ],
        "errors": [],
        "newInvitations": 1,
        "reinvitations": 0
    },
    "message": "1 utilisateur(s) invité(s) avec succès"
}
```

### Exemple de réponse avec réinvitation (201 Created)

```json
{
    "success": true,
    "data": {
        "sessionId": "789e0123-e89b-12d3-a456-426614174002",
        "invitedUsers": [
            {
                "id": "123e4567-e89b-12d3-a456-426614174000",
                "firstname": "John",
                "lastname": "Doe",
                "email": "john@example.com"
            }
        ],
        "errors": [],
        "newInvitations": 0,
        "reinvitations": 1
    },
    "message": "1 utilisateur(s) réinvité(s) avec succès"
}
```

### Codes de réponse

- `201` : Invitations envoyées avec succès
- `400` : Erreur de validation
- `403` : Accès interdit (l'utilisateur n'est pas le créateur de la session)
- `404` : Session ou utilisateur non trouvé
- `500` : Erreur interne du serveur

### Comportement de réinvitation

L'API supporte la réinvitation d'utilisateurs qui ont précédemment décliné une invitation :

- **Nouvelle invitation** : Si l'utilisateur n'a jamais été invité, une nouvelle invitation est créée
- **Réinvitation** : Si l'utilisateur a décliné une invitation précédente, son statut est remis à "pending"
- **Invitation en cours** : Si l'utilisateur a déjà une invitation en attente, aucune action n'est effectuée
- **Participant actif** : Si l'utilisateur participe déjà à la session, l'invitation est rejetée

Les notifications push et les messages sont adaptés selon le type d'invitation (nouvelle vs réinvitation). 

## Répondre à une invitation de session

**URL :** `PATCH /api/sessions/{id}/respond`

**Méthode :** PATCH

**Authentification :** Requise (token Bearer)

### Paramètres de route
- `{id}` : L'ID de la session sportive (UUID)

### Corps de la requête

```json
{
    "response": "accept"
}
```

**Valeurs possibles pour `response` :**
- `accept` : Accepter l'invitation
- `decline` : Décliner l'invitation

### Exemple de réponse (200 OK)

```json
{
    "success": true,
    "data": {
        "id": "789e0123-e89b-12d3-a456-426614174002",
        "sport": "tennis",
        "participants": [
            {
                "id": "123e4567-e89b-12d3-a456-426614174000",
                "fullName": "John Doe",
                "status": "accepted"
            }
        ]
    },
    "message": "Invitation acceptée"
}
```

### Codes de réponse

- `200` : Réponse traitée avec succès
- `400` : Erreur de validation ou limite de participants atteinte
- `403` : Utilisateur non invité à la session
- `404` : Session non trouvée
- `500` : Erreur interne du serveur

### Validation de la limite de participants

Si la session a une limite de participants (`maxParticipants`), l'acceptation d'une invitation sera bloquée si la limite est atteinte.

**Exemple d'erreur (400 Bad Request) :**
```json
{
    "success": false,
    "error": {
        "code": "PARTICIPANT_LIMIT_REACHED",
        "message": "Impossible d'accepter l'invitation : la session a atteint sa limite de 2 participants"
    }
}
```

### Notes importantes

1. **Limite de participants** : Si `maxParticipants` est défini, l'acceptation sera bloquée une fois la limite atteinte
2. **Pas de limite** : Si `maxParticipants` est `null`, il n'y a pas de limite au nombre de participants
3. **Statuts** : 
   - `accepted` : Participant a accepté l'invitation
   - `declined` : Participant a décliné l'invitation
   - `pending` : Participant n'a pas encore répondu 

## Comportement des sessions refusées

### Visibilité des sessions refusées

Les sessions où l'utilisateur a refusé l'invitation (`status: 'declined'`) sont **complètement cachées** des listes pour cet utilisateur.

### Logique de filtrage

Les endpoints suivants appliquent ce filtrage :
- `GET /sessions` - Liste des sessions de l'utilisateur
- `GET /sessions/my-participations` - Sessions où l'utilisateur participe

**Règles de visibilité :**
- ✅ **Sessions acceptées** (`status: 'accepted'`) : Toujours visibles
- ✅ **Sessions en attente** (`status: 'pending'`) : Toujours visibles  
- ❌ **Sessions refusées** (`status: 'declined'`) : **Jamais visibles** dans les listes
- ✅ **Sessions organisées** : Toujours visibles (même si refusées par d'autres)

### Comportement détaillé

1. **Listes de sessions** : Les sessions refusées n'apparaissent plus du tout
2. **Détails de session** : Restent accessibles via `GET /sessions/{id}` 
3. **Organisateur** : Voit toujours toutes ses sessions, même refusées par d'autres

### Exemple de comportement

```json
// Avant refus
{
    "sessions": [
        {
            "id": "session-1",
            "sport": "tennis",
            "participants": [
                {"id": "user-1", "status": "accepted"},
                {"id": "user-2", "status": "pending"}
            ]
        }
    ]
}

// Après refus par user-2
{
    "sessions": [
        {
            "id": "session-1", 
            "sport": "tennis",
            "participants": [
                {"id": "user-1", "status": "accepted"}
                // user-2 n'apparaît plus dans la liste
            ]
        }
    ]
}
``` 

# API Sessions Sportives

## Endpoints

### Annuler sa participation à une session

**PATCH** `/api/sessions/{sessionId}/cancel-participation`

Permet à un utilisateur qui a accepté une invitation à une session d'annuler sa participation.

#### Headers requis
```
Authorization: Bearer {token}
Content-Type: application/json
Accept: application/json
```

#### Body de la requête
```json
{
  "status": "declined"
}
```
*Note : Le body est optionnel car le statut est toujours "declined" pour cette action.*

#### Codes de réponse

**200 - Participation annulée avec succès**
```json
{
  "success": true,
  "message": "Participation annulée avec succès",
  "data": {
    "session": {
      "id": "session-uuid",
      "sport": "tennis",
      "date": "2025-02-15",
      "time": "14:00",
      "location": "Tennis Club",
      "participants": [
        {
          "id": "user-uuid",
          "firstname": "Jean",
          "lastname": "Dupont",
          "status": "declined"
        }
      ]
    }
  }
}
```

**400 - Utilisateur n'a pas accepté l'invitation**
```json
{
  "success": false,
  "message": "Vous n'avez pas accepté l'invitation à cette session",
  "error": "USER_NOT_ACCEPTED"
}
```

**403 - Non autorisé**
```json
{
  "success": false,
  "message": "Vous n'êtes pas autorisé à annuler votre participation à cette session",
  "error": "UNAUTHORIZED"
}
```

**404 - Session non trouvée**
```json
{
  "success": false,
  "message": "Session non trouvée",
  "error": "SESSION_NOT_FOUND"
}
```

**409 - Session terminée**
```json
{
  "success": false,
  "message": "Impossible d'annuler la participation à une session terminée",
  "error": "SESSION_ENDED"
}
```

#### Conditions préalables
1. L'utilisateur doit être un participant de la session avec le statut `accepted`
2. La session ne doit pas être terminée
3. L'utilisateur ne doit pas être l'organisateur de la session

#### Actions effectuées
1. Vérifier les permissions et conditions
2. Mettre à jour le statut du participant de `accepted` à `declined`
3. Libérer une place dans la session (si limite de participants configurée)
4. Créer des notifications pour tous les participants actifs
5. Envoyer des notifications push si configurées
6. Ajouter un commentaire système dans la session
7. Retourner la session mise à jour

#### Notifications créées
- **Type** : `session_update`
- **Titre** : "Participation annulée"
- **Message** : "[Nom Prénom] a annulé sa participation à la session de [sport]"
- **Destinataires** : Tous les participants avec le statut `accepted` (sauf celui qui annule)
- **Données** : 
  ```json
  {
    "type": "session_update",
    "session_id": "session-uuid",
    "user_id": "user-uuid",
    "action": "participation_cancelled",
    "previous_status": "accepted",
    "new_status": "declined"
  }
  ```

#### Commentaire système créé
- **Contenu** : "[Nom Prénom] a annulé sa participation à cette session."
- **Auteur** : L'utilisateur qui annule sa participation
- **Type** : Commentaire système informatif 
