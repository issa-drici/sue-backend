# 📱 Résumé API Notifications - Équipe Mobile

## 🎯 Informations générales

- **ID Demande :** Q-20250122-001
- **Date :** 22/01/2025
- **Statut :** ✅ Complète
- **Documentation complète :** `docs/api/notifications.md`

## 🚀 Endpoints disponibles

### Endpoints principaux (priorité haute)

| Endpoint | Méthode | Description | Authentification |
|----------|---------|-------------|------------------|
| `/notifications` | GET | Liste paginée des notifications | ✅ Bearer Token |
| `/notifications/unread-count` | GET | Nombre de notifications non lues | ✅ Bearer Token |
| `/notifications/{id}/read` | PATCH | Marquer une notification comme lue | ✅ Bearer Token |
| `/notifications/read-all` | PATCH | Marquer toutes comme lues | ✅ Bearer Token |
| `/notifications/{id}` | DELETE | Supprimer une notification | ✅ Bearer Token |

### Endpoints push (priorité moyenne)

| Endpoint | Méthode | Description | Authentification |
|----------|---------|-------------|------------------|
| `/push-tokens` | POST | Enregistrer token Expo | ✅ Bearer Token |
| `/push-tokens` | DELETE | Supprimer token Expo | ✅ Bearer Token |
| `/notifications/send` | POST | Envoyer notification push | ✅ Bearer Token |

## 📊 Types de notifications

### Types supportés
- **invitation** : Invitation à une session
- **reminder** : Rappel de session
- **update** : Mise à jour générale
- **comment** : Nouveau commentaire
- **session_update** : Modification de session
- **session_cancelled** : Session annulée

### Structure de réponse
```json
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
```

## 🔐 Authentification

Tous les endpoints nécessitent :
```
Authorization: Bearer <token>
Content-Type: application/json
```

## 📋 Paramètres de requête

### GET /notifications
- `page` (int, optionnel) : Numéro de page (défaut: 1)
- `limit` (int, optionnel) : Éléments par page (défaut: 20, max: 50)

### POST /push-tokens
- `token` (string, requis) : Token Expo
- `platform` (string, optionnel) : expo|ios|android
- `device_id` (string, optionnel) : ID appareil

## 🚨 Codes d'erreur

| Code | Description | HTTP Status |
|------|-------------|-------------|
| `NOTIFICATION_NOT_FOUND` | Notification non trouvée | 404 |
| `FORBIDDEN` | Accès non autorisé | 403 |
| `UNAUTHORIZED` | Token invalide | 401 |
| `VALIDATION_ERROR` | Données invalides | 400 |
| `NO_TOKENS_FOUND` | Aucun token push | 404 |

## 📱 Implémentation mobile recommandée

### 1. Badge notifications
```typescript
// Polling toutes les 30 secondes
GET /notifications/unread-count
```

### 2. Liste notifications
```typescript
// Pagination infinie
GET /notifications?page=1&limit=20
```

### 3. Marquer comme lue
```typescript
// Au clic sur notification
PATCH /notifications/{id}/read
```

### 4. Enregistrer token push
```typescript
// Au démarrage de l'app
POST /push-tokens
{
  "token": "ExponentPushToken[...]",
  "platform": "expo"
}
```

## 🧪 Tests à effectuer

### Tests de base
- [ ] Récupération liste notifications
- [ ] Comptage notifications non lues
- [ ] Marquage comme lue
- [ ] Marquage toutes comme lues
- [ ] Suppression notification

### Tests push
- [ ] Enregistrement token Expo
- [ ] Suppression token Expo
- [ ] Réception notifications push

### Tests d'erreur
- [ ] Token expiré (401)
- [ ] Notification inexistante (404)
- [ ] Accès non autorisé (403)
- [ ] Données invalides (400)

## 📊 Métriques à surveiller

- Temps de réponse des endpoints
- Taux d'erreur 401/403/404
- Nombre de notifications push envoyées
- Taux de succès des tokens push

## 🔗 Liens utiles

- **Documentation complète :** `docs/api/notifications.md`
- **Tests backend :** `tests/Feature/Notification/`
- **Repository :** `app/Repositories/Notification/`
- **Use Cases :** `app/UseCases/Notification/`

---

**Contact :** Équipe Backend
**Dernière mise à jour :** 22/01/2025
