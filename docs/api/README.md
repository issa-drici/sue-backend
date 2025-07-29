# API Alarrache - Documentation Générale

## Vue d'ensemble

Cette documentation couvre l'ensemble de l'API backend pour l'application Alarrache, une plateforme de gestion de sessions de sport.

## Architecture

### Base URL
```
https://api.alarrache.com/api
```

### Structure des réponses

Toutes les réponses suivent le format standard :

**Succès :**
```json
{
  "success": true,
  "data": { ... },
  "message": "Message optionnel"
}
```

**Erreur :**
```json
{
  "success": false,
  "error": {
    "code": "ERROR_CODE",
    "message": "Message d'erreur",
    "details": { ... }
  }
}
```

## Modules API

### 1. [Authentication](./auth.md)
- Gestion de l'authentification JWT
- Inscription et connexion des utilisateurs
- Rafraîchissement des tokens

### 2. [Sessions](./sessions.md)
- Création et gestion des sessions de sport
- Invitations et participations
- Commentaires sur les sessions

### 3. [Users](./users.md)
- Gestion des profils utilisateurs
- Système d'amis et demandes d'amis
- Recherche d'utilisateurs

### 4. [Notifications](./notifications.md)
- Notifications en temps réel
- Gestion des notifications non lues
- Types de notifications automatiques

## Guides de développement

### [Guide Développeur Frontend Mobile](./FRONTEND_DEVELOPER_GUIDE.md)
- Guide complet pour l'intégration mobile
- Exemples de code React Native/Flutter
- Bonnes pratiques et gestion d'erreurs
- Hooks personnalisés et utilitaires

## Codes d'erreur communs

| Code | Description | HTTP Status |
|------|-------------|-------------|
| `UNAUTHORIZED` | Token invalide ou manquant | 401 |
| `FORBIDDEN` | Accès non autorisé | 403 |
| `NOT_FOUND` | Ressource non trouvée | 404 |
| `VALIDATION_ERROR` | Erreur de validation des données | 400 |
| `CONFLICT` | Conflit de données | 409 |
| `INTERNAL_ERROR` | Erreur serveur | 500 |

## Authentification

### JWT Token
- **Algorithme :** HS256
- **Durée de vie :** 24 heures
- **Refresh token :** 7 jours

### Headers requis
```
Authorization: Bearer <token>
Content-Type: application/json
```

## Entités principales

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

### SportSession
```json
{
  "id": "string",
  "sport": "tennis | golf | musculation | football | basketball",
  "date": "string (YYYY-MM-DD)",
  "time": "string (HH:MM)",
  "location": "string",
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

## Logique métier

### Sports supportés
- tennis
- golf
- musculation
- football
- basketball

### Statuts de participants
- **pending** : Invitation en attente
- **accepted** : Invitation acceptée
- **declined** : Invitation refusée

### Types de notifications
- **invitation** : Invitation à une session
- **reminder** : Rappel de session
- **update** : Session modifiée

### Statuts d'utilisateur
- **online** : Utilisateur en ligne
- **offline** : Utilisateur hors ligne

## Sécurité

### Validation
- Sanitisation des entrées
- Validation côté serveur
- Protection contre les injections

### Autorisations
- Vérification du token JWT sur tous les endpoints
- Contrôle d'accès basé sur les rôles
- Rate limiting sur les endpoints sensibles

### Confidentialité
- Les données sensibles sont hashées
- Les emails ne sont visibles que pour les amis
- Les profils publics limitent les informations

## Performance

### Pagination
Tous les endpoints de liste supportent la pagination :
```
?page=1&limit=20
```

### Cache
- Cache Redis pour les sessions fréquemment consultées
- Cache des profils utilisateurs
- Cache des listes d'amis

## Tests

### Tests unitaires
- Validation des données
- Logique métier
- Gestion des erreurs

### Tests d'intégration
- Flux complets d'utilisation
- Authentification
- Relations entre entités

### Tests de performance
- Temps de réponse
- Tests de charge
- Tests de stress

## Déploiement

### Variables d'environnement
```env
DATABASE_URL=postgresql://user:password@localhost:5432/alarrache
JWT_SECRET=your-secret-key
JWT_REFRESH_SECRET=your-refresh-secret
REDIS_URL=redis://localhost:6379
NODE_ENV=production
PORT=3000
```

### Docker
```dockerfile
FROM node:18-alpine
WORKDIR /app
COPY package*.json ./
RUN npm ci --only=production
COPY . .
EXPOSE 3000
CMD ["npm", "start"]
```

## Monitoring

### Logs
- Logs structurés en JSON
- Niveaux : error, warn, info, debug
- Rotation automatique des logs

### Métriques
- Temps de réponse des endpoints
- Taux d'erreur
- Nombre d'utilisateurs actifs

### Alertes
- Erreurs 5xx
- Temps de réponse > 2s
- Utilisation CPU > 80%
- Espace disque < 20%

## Documentation des endpoints

Pour une documentation détaillée de chaque module, consultez :
- [Documentation Authentication](./auth.md)
- [Documentation Sessions](./sessions.md)
- [Documentation Users](./users.md)
- [Documentation Notifications](./notifications.md) 
