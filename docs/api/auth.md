# API Authentication - Documentation Backend

## Vue d'ensemble

Ce document détaille tous les endpoints d'authentification nécessaires pour l'application Alarrache.

## Base URL
```
https://api.alarrache.com/api
```

## Endpoints

### 1. POST /auth/login

**Description :** Authentification d'un utilisateur existant

**URL :** `/auth/login`

**Méthode :** `POST`

**Headers :**
```
Content-Type: application/json
```

**Body :**
```json
{
  "email": "user@example.com",
  "password": "password123"
}
```

**Réponse Succès (200) :**
```json
{
  "success": true,
  "data": {
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "user": {
      "id": "1",
      "email": "user@example.com",
      "firstName": "John",
      "lastName": "Doe",
      "avatar": "https://i.pravatar.cc/150?img=1"
    }
  },
  "message": "Connexion réussie"
}
```

**Réponse Erreur (401) :**
```json
{
  "success": false,
  "error": {
    "code": "INVALID_CREDENTIALS",
    "message": "Email ou mot de passe incorrect"
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
      "email": "Format d'email invalide",
      "password": "Le mot de passe est requis"
    }
  }
}
```

**Validation :**
- Email : Format email valide, requis
- Password : Minimum 6 caractères, requis

### 2. POST /auth/register

**Description :** Inscription d'un nouvel utilisateur

**URL :** `/auth/register`

**Méthode :** `POST`

**Headers :**
```
Content-Type: application/json
```

**Body :**
```json
{
  "firstName": "John",
  "lastName": "Doe",
  "email": "user@example.com",
  "password": "password123"
}
```

**Réponse Succès (201) :**
```json
{
  "success": true,
  "data": {
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "user": {
      "id": "1",
      "email": "user@example.com",
      "firstName": "John",
      "lastName": "Doe",
      "avatar": null
    }
  },
  "message": "Inscription réussie"
}
```

**Réponse Erreur (409) :**
```json
{
  "success": false,
  "error": {
    "code": "EMAIL_ALREADY_EXISTS",
    "message": "Un compte avec cet email existe déjà"
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
      "firstName": "Le prénom est requis",
      "lastName": "Le nom est requis",
      "email": "Format d'email invalide",
      "password": "Le mot de passe doit contenir au moins 6 caractères"
    }
  }
}
```

**Validation :**
- firstName : Requis, 2-50 caractères
- lastName : Requis, 2-50 caractères
- email : Format email valide, unique, requis
- password : Minimum 6 caractères, requis

### 3. POST /auth/logout

**Description :** Déconnexion de l'utilisateur

**URL :** `/auth/logout`

**Méthode :** `POST`

**Headers :**
```
Authorization: Bearer <token>
Content-Type: application/json
```

**Body :** (vide)
```json
{}
```

**Réponse Succès (200) :**
```json
{
  "success": true,
  "message": "Déconnexion réussie"
}
```

**Réponse Erreur (401) :**
```json
{
  "success": false,
  "error": {
    "code": "UNAUTHORIZED",
    "message": "Token invalide ou expiré"
  }
}
```

### 4. POST /auth/refresh

**Description :** Rafraîchissement du token d'authentification

**URL :** `/auth/refresh`

**Méthode :** `POST`

**Headers :**
```
Authorization: Bearer <token>
Content-Type: application/json
```

**Body :** (vide)
```json
{}
```

**Réponse Succès (200) :**
```json
{
  "success": true,
  "data": {
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
  },
  "message": "Token rafraîchi"
}
```

**Réponse Erreur (401) :**
```json
{
  "success": false,
  "error": {
    "code": "TOKEN_EXPIRED",
    "message": "Token expiré"
  }
}
```

## Codes d'erreur

| Code | Description |
|------|-------------|
| `INVALID_CREDENTIALS` | Email ou mot de passe incorrect |
| `EMAIL_ALREADY_EXISTS` | Email déjà utilisé |
| `VALIDATION_ERROR` | Erreur de validation des données |
| `UNAUTHORIZED` | Token invalide ou manquant |
| `TOKEN_EXPIRED` | Token expiré |
| `INTERNAL_ERROR` | Erreur serveur |

## Entités

### LoginCredentials
```json
{
  "email": "string",
  "password": "string"
}
```

### RegisterData
```json
{
  "firstName": "string",
  "lastName": "string",
  "email": "string",
  "password": "string"
}
```

### AuthResponse
```json
{
  "token": "string",
  "user": {
    "id": "string",
    "email": "string",
    "firstName": "string",
    "lastName": "string",
    "avatar": "string | null"
  }
}
```

## Sécurité

### JWT Token
- **Algorithme :** HS256
- **Durée de vie :** 24 heures
- **Refresh token :** 7 jours
- **Secret :** Variable d'environnement sécurisée

### Hashage des mots de passe
- **Algorithme :** bcrypt
- **Salt rounds :** 12

### Validation
- Sanitisation des entrées
- Validation côté serveur
- Protection contre les injections
- Rate limiting sur les endpoints sensibles

## Tests recommandés

### Tests unitaires
- Validation des données d'entrée
- Hashage des mots de passe
- Génération et validation des tokens JWT
- Gestion des erreurs

### Tests d'intégration
- Flux complet d'inscription/connexion
- Gestion des tokens expirés
- Rate limiting
- Validation des réponses

### Tests de sécurité
- Injection
- XSS
- CSRF
- Brute force sur les mots de passe 