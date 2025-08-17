# Notifications Push (Expo) — Backend

## Modèle de données

Table `push_tokens`:
- `id` (uuid, pk)
- `user_id` (uuid, fk users, cascade)
- `token` (string unique, format `ExponentPushToken[...]`)
- `platform` (`expo|ios|android`)
- `device_id` (string nullable)
- `last_seen_at` (timestamp nullable)
- `is_active` (bool)
- `timestamps`

## Endpoints

- POST `/api/push-tokens` (auth)
  - Body: `{ "token": "ExponentPushToken[...]", "platform": "ios|android|expo", "device_id"?: "string" }`
  - Réponse: `{ "success": true }`

- DELETE `/api/push-tokens` (auth)
  - Body: `{ "token": "ExponentPushToken[...]" }`
  - Réponse: `{ "success": true }`

- POST `/api/notifications/send` (auth)
  - Body: `{ "recipientId": "uuid", "title": "string", "body": "string", "data"?: { ... } }`
  - Usage: test / QA

## Service d’envoi

`App\Services\ExpoPushNotificationService`
- Envoi chunké par 100 vers `https://exp.host/--/api/v2/push/send`
- Logs: succès/erreurs + payload de réponse Expo
- Détection de tokens invalides (`DeviceNotRegistered`, `InvalidPushToken`, etc.) et retour dans `invalid_tokens`

## Déclencheurs (MVP)

- Commentaire créé
  - Dest: participants (hors auteur)
  - Title: "{authorName} a commenté"
  - Data: `{ "type": "comment", "session_id": "...", "notification_id": "..." }`

- Invitation créée
  - Dest: invité
  - Data: `{ "type": "session_invitation", ... }`

- Invitation acceptée
  - Dest: créateur
  - Data: `{ "type": "session_update", ... }`

- Demande d’ami
  - Dest: utilisateur ciblé
  - Data: `{ "type": "friend_request", ... }`

## Sécurité

- Endpoints protégés par Sanctum
- Validation token Expo backend: `^ExponentPushToken\[.+\]$`

## Notes iOS

Compte Apple Developer requis et capabilities activées pour les tests sur device réel.


