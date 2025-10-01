# API Users - Documentation Backend

## Vue d'ensemble

Ce document détaille tous les endpoints de gestion des utilisateurs et des relations d'amis pour l'application Alarrache.

## Base URL
```
https://api.alarrache.com/api
```

## Endpoints

### 1. GET /users/profile

**Description :** Récupérer le profil de l'utilisateur connecté

**URL :** `/users/profile`

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
    "firstname": "John",
    "lastname": "Doe",
    "email": "john.doe@example.com",
    "avatar": "https://i.pravatar.cc/150?img=1",
    "stats": {
      "sessionsCreated": 12,
      "sessionsParticipated": 45
    }
  }
}
```

### 2. PUT /users/profile

**Description :** Mettre à jour le profil de l'utilisateur

**URL :** `/users/profile`

**Méthode :** `PUT`

**Headers :**
```
Authorization: Bearer <token>
Content-Type: application/json
```

**Body :**
```json
{
  "firstname": "John",
  "lastname": "Smith",
  "avatar": "https://i.pravatar.cc/150?img=1"
}
```

**Réponse Succès (200) :**
```json
{
  "success": true,
  "data": {
    "id": "1",
    "firstname": "John",
    "lastname": "Smith",
    "email": "john.doe@example.com",
    "avatar": "https://i.pravatar.cc/150?img=1",
    "stats": {
      "sessionsCreated": 12,
      "sessionsParticipated": 45
    }
  },
  "message": "Profil mis à jour avec succès"
}
```

### 3. GET /users/friends

**Description :** Récupérer la liste d'amis de l'utilisateur

**URL :** `/users/friends`

**Méthode :** `GET`

**Headers :**
```
Authorization: Bearer <token>
```

**Réponse Succès (200) :**
```json
{
  "success": true,
  "data": [
    {
      "id": "2",
      "firstname": "Jean",
      "lastname": "Dupont",
      "avatar": null,
      "status": "online"
    },
    {
      "id": "3",
      "firstname": "Marie",
      "lastname": "Martin",
      "avatar": null,
      "status": "offline",
      "lastSeen": "2024-03-20T15:30:00Z"
    },
    {
      "id": "4",
      "firstname": "Pierre",
      "lastname": "Durand",
      "avatar": "https://i.pravatar.cc/150?img=3",
      "status": "online"
    }
  ]
}
```

### 4. GET /users/friend-requests

**Description :** Récupérer les demandes d'amis reçues

**URL :** `/users/friend-requests`

**Méthode :** `GET`

**Headers :**
```
Authorization: Bearer <token>
```

**Réponse Succès (200) :**
```json
{
  "success": true,
  "data": [
    {
      "id": "5",
      "firstname": "Emma",
      "lastname": "Leroy",
      "avatar": "https://i.pravatar.cc/150?img=5",
      "mutualFriends": 3
    },
    {
      "id": "6",
      "firstname": "Hugo",
      "lastname": "Moreau",
      "avatar": "https://i.pravatar.cc/150?img=6",
      "mutualFriends": 7
    },
    {
      "id": "7",
      "firstname": "Léa",
      "lastname": "Petit",
      "avatar": "https://i.pravatar.cc/150?img=7",
      "mutualFriends": 2
    }
  ]
}
```

### 5. POST /users/friend-requests

**Description :** Envoyer une demande d'ami

**URL :** `/users/friend-requests`

**Méthode :** `POST`

**Headers :**
```
Authorization: Bearer <token>
Content-Type: application/json
```

**Body :**
```json
{
  "userId": "4"
}
```

**Réponse Succès (201) :**
```json
{
  "success": true,
  "data": {
    "id": "2",
    "firstname": "Alice",
    "lastname": "Brown",
    "avatar": "https://i.pravatar.cc/150?img=4",
    "mutualFriends": 2
  },
  "message": "Demande d'ami envoyée"
}
```

**Réponse Erreur (409) :**
```json
{
  "success": false,
  "error": {
    "code": "FRIEND_REQUEST_EXISTS",
    "message": "Une demande d'ami existe déjà"
  }
}
```

### 6. PATCH /users/friend-requests/{id}

**Description :** Répondre à une demande d'ami

**URL :** `/users/friend-requests/{id}`

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
    "id": "5",
    "firstname": "Emma",
    "lastname": "Leroy",
    "avatar": "https://i.pravatar.cc/150?img=5",
    "mutualFriends": 3,
    "status": "accepted"
  },
  "message": "Demande d'ami acceptée"
}
```

### 7. GET /users/search

**Description :** Rechercher des utilisateurs

**URL :** `/users/search?q=john`

**Méthode :** `GET`

**Headers :**
```
Authorization: Bearer <token>
```

**Query Parameters :**
```
q=john&page=1&limit=20
```

**Réponse Succès (200) :**
```json
{
  "success": true,
  "data": [
    {
      "id": "2",
      "firstname": "John",
      "lastname": "Smith",
      "avatar": "https://i.pravatar.cc/150?img=2",
      "email": "john.smith@example.com",
      "isFriend": false,
      "hasPendingRequest": true,
      "mutualFriends": 3
    },
    {
      "id": "5",
      "firstname": "Johnny",
      "lastname": "Doe",
      "avatar": "https://i.pravatar.cc/150?img=5",
      "email": "johnny.doe@example.com",
      "isFriend": true,
      "hasPendingRequest": false,
      "mutualFriends": 7
    }
  ],
  "pagination": {
    "page": 1,
    "limit": 20,
    "total": 15,
    "totalPages": 1
  }
}
```

### 8. POST /users/update-email

**Description :** Mettre à jour l'email de l'utilisateur

**URL :** `/users/update-email`

**Méthode :** `POST`

**Headers :**
```
Authorization: Bearer <token>
Content-Type: application/json
```

**Body :**
```json
{
  "newEmail": "newemail@example.com",
  "currentEmail": "john.doe@example.com"
}
```

**Réponse Succès (200) :**
```json
{
  "success": true,
  "message": "Email mis à jour avec succès"
}
```

**Réponse Erreur (409) :**
```json
{
  "success": false,
  "error": {
    "code": "EMAIL_ALREADY_EXISTS",
    "message": "Cet email est déjà utilisé"
  }
}
```

### 9. POST /users/update-password

**Description :** Mettre à jour le mot de passe de l'utilisateur

**URL :** `/users/update-password`

**Méthode :** `POST`

**Headers :**
```
Authorization: Bearer <token>
Content-Type: application/json
```

**Body :**
```json
{
  "currentPassword": "oldpassword123",
  "newPassword": "newpassword123"
}
```

**Réponse Succès (200) :**
```json
{
  "success": true,
  "message": "Mot de passe mis à jour avec succès"
}
```

**Réponse Erreur (400) :**
```json
{
  "success": false,
  "error": {
    "code": "INVALID_CURRENT_PASSWORD",
    "message": "Mot de passe actuel incorrect"
  }
}
```

### 10. DELETE /users

**Description :** Supprimer le compte utilisateur

**URL :** `/users`

**Méthode :** `DELETE`

**Headers :**
```
Authorization: Bearer <token>
```

**Réponse Succès (200) :**
```json
{
  "success": true,
  "message": "Compte supprimé avec succès"
}
```

## Codes d'erreur

| Code | Description |
|------|-------------|
| `USER_NOT_FOUND` | Utilisateur non trouvé |
| `VALIDATION_ERROR` | Erreur de validation des données |
| `EMAIL_ALREADY_EXISTS` | Email déjà utilisé |
| `FRIEND_REQUEST_EXISTS` | Demande d'ami déjà existante |
| `INVALID_CURRENT_PASSWORD` | Mot de passe actuel incorrect |
| `UNAUTHORIZED` | Token invalide ou manquant |
| `FORBIDDEN` | Accès non autorisé |

## Validation

### Mise à jour du profil
- **firstname** : Optionnel, 2-50 caractères
- **lastname** : Optionnel, 2-50 caractères
- **avatar** : Optionnel, URL valide

### Demande d'ami
- **userId** : Requis, UUID valide, différent de l'utilisateur connecté

### Réponse à une demande d'ami
- **response** : Requis, "accept" ou "decline"

### Mise à jour d'email
- **newEmail** : Requis, format email valide, unique
- **currentEmail** : Requis, doit correspondre à l'email actuel

### Mise à jour de mot de passe
- **currentPassword** : Requis, doit correspondre au mot de passe actuel
- **newPassword** : Requis, minimum 6 caractères

## Entités

### User
```json
{
  "id": "string",
  "firstname": "string",
  "lastname": "string",
  "email": "string",
  "avatar": "string | null"
}
```

### UserProfile
```json
{
  "id": "string",
  "firstname": "string",
  "lastname": "string",
  "email": "string",
  "avatar": "string | null",
  "stats": {
    "sessionsCreated": "number",
    "sessionsParticipated": "number",
    "favoriteSport": "string"
  }
}
```

### Friend
```json
{
  "id": "string",
  "firstname": "string",
  "lastname": "string",
  "avatar": "string | null",
  "status": "online | offline",
  "lastSeen": "string | null"
}
```

### FriendRequest
```json
{
  "id": "string",
  "firstname": "string",
  "lastname": "string",
  "avatar": "string",
  "mutualFriends": "number"
}
```

## Logique métier

### Statuts d'utilisateur
- **online** : Utilisateur en ligne
- **offline** : Utilisateur hors ligne

### Statuts d'amitié
- **pending** : Demande en attente
- **accepted** : Amitié acceptée
- **declined** : Demande refusée

### Règles métier
1. Un utilisateur ne peut pas s'envoyer une demande d'ami à lui-même
2. Une seule demande d'ami active entre deux utilisateurs
3. Quand une demande est acceptée, elle devient une amitié
4. Les amis peuvent voir les sessions et profils de leurs amis
5. La suppression d'un compte supprime toutes ses relations
6. Un utilisateur ne peut pas modifier l'email d'un autre utilisateur
7. La recherche d'utilisateurs exclut l'utilisateur connecté
8. Les mots de passe sont toujours hashés avant stockage
9. Les stats utilisateur sont calculées automatiquement

## Sécurité

### Validation des données
- Sanitisation des entrées
- Validation côté serveur
- Protection contre les injections

### Autorisations
- Seul l'utilisateur peut modifier son propre profil
- Vérification du token JWT sur tous les endpoints
- Rate limiting sur les endpoints sensibles

### Confidentialité
- Les emails ne sont visibles que pour les amis
- Les profils publics ne montrent que les informations de base
- Les demandes d'ami sont privées 

# API Users

## GET /api/users/{userId}

Récupère le profil d'un utilisateur spécifique par son ID.

### Authentification
- **Requis** : Token Bearer (Sanctum)

### Paramètres
- `userId` (path parameter) : ID de l'utilisateur dont on veut récupérer le profil

### Réponse

#### Succès (200)
```json
{
  "success": true,
  "data": {
    "id": "123",
    "firstname": "Jean",
    "lastname": "Dupont",
    "email": "jean.dupont@example.com",
    "avatar": "https://example.com/avatar.jpg",
    "sports_preferences": ["tennis", "football", "basketball"],
    "stats": {
      "sessionsCreated": 5,
      "sessionsParticipated": 12
    },
    "isAlreadyFriend": false,
    "hasPendingRequest": false,
    "relationshipStatus": "none"
  }
}
```

#### Erreur (404)
```json
{
  "success": false,
  "message": "Utilisateur non trouvé"
}
```

#### Erreur (401)
```json
{
  "success": false,
  "message": "Non autorisé"
}
```

### Description des champs

#### Données utilisateur
- `id` : Identifiant unique de l'utilisateur
- `firstname` : Prénom de l'utilisateur
- `lastname` : Nom de famille de l'utilisateur
- `email` : Adresse email de l'utilisateur
- `avatar` : URL de l'avatar de l'utilisateur (peut être null)
- `sports_preferences` : Liste des sports préférés de l'utilisateur (tableau de chaînes)
- `isAlreadyFriend` : Indique si l'utilisateur connecté a déjà cet utilisateur en ami
- `hasPendingRequest` : Indique s'il y a une demande d'ami en attente
- `relationshipStatus` : Statut de la relation entre les utilisateurs

#### Sports préférés
- `sports_preferences` : Tableau des sports préférés de l'utilisateur
- **Type** : `array` de `string`
- **Valeurs possibles** : Voir la liste des sports valides (48 sports supportés)
- **Valeur par défaut** : `[]` (tableau vide) si l'utilisateur n'a pas de préférences
- **Exemple** : `["tennis", "football", "basketball"]`

#### Statistiques
- `stats.sessionsCreated` : Nombre de sessions sportives créées par l'utilisateur
- `stats.sessionsParticipated` : Nombre de sessions sportives auxquelles l'utilisateur a participé (statut "accepted")

#### Relations
- `isAlreadyFriend` : `boolean` - Indique si l'utilisateur connecté est ami avec l'utilisateur demandé
- `hasPendingRequest` : `boolean` - Indique s'il y a une demande d'ami en attente
- `relationshipStatus` : `string` - Statut de la relation (`none`, `pending`, `received`, `accepted`, `declined`, `cancelled`)

### Exemples d'utilisation

#### cURL
```bash
curl -X GET "https://api.example.com/api/users/123" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

#### JavaScript (Fetch)
```javascript
const response = await fetch('/api/users/123', {
  method: 'GET',
  headers: {
    'Authorization': 'Bearer YOUR_TOKEN',
    'Accept': 'application/json'
  }
});

const data = await response.json();
```

### Notes
- Cet endpoint est utilisé dans la modal de profil utilisateur
- Les statistiques sont calculées en temps réel
- L'avatar peut être null si l'utilisateur n'en a pas défini
- Tous les utilisateurs authentifiés peuvent accéder aux profils des autres utilisateurs
- Le champ `isAlreadyFriend` retourne `false` si l'utilisateur consulte son propre profil
- Le champ `isAlreadyFriend` est utilisé pour afficher le bon bouton dans l'interface ("Ajouter en ami" ou "Déjà dans vos amis") 
