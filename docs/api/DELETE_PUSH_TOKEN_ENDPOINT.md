# 🔔 Endpoint DELETE /api/push-tokens - Documentation Technique

**Version :** 1.0  
**Date :** 22 Janvier 2025  
**Statut :** ✅ Implémenté et Sécurisé  

---

## 📋 Vue d'Ensemble

L'endpoint `DELETE /api/push-tokens` permet à un utilisateur authentifié de supprimer un token de notification push de la base de données. Cet endpoint a été **sécurisé** pour empêcher qu'un utilisateur supprime le token d'un autre utilisateur.

---

## 🔗 Informations de l'Endpoint

### URL
```
DELETE /api/push-tokens
```

### Authentification
- **Requis :** ✅ Oui
- **Type :** Bearer Token (Sanctum)
- **Middleware :** `auth:sanctum`

### Headers Requis
```http
Authorization: Bearer {token}
Content-Type: application/json
Accept: application/json
```

---

## 📤 Payload de Requête

### Structure
```json
{
  "token": "ExponentPushToken[xxxxxxxxxxxxxxxxxxxxxx]"
}
```

### Paramètres
| Paramètre | Type | Requis | Description | Exemple |
|-----------|------|--------|-------------|---------|
| `token` | string | ✅ Oui | Token Expo à supprimer | `"ExponentPushToken[abc123...]"` |

### Validation
- **token** : `required|string|max:255`
- **Format** : Doit être un token Expo valide
- **Longueur** : Maximum 255 caractères

---

## 📥 Réponses

### ✅ Succès (200)
```json
{
  "success": true
}
```

### ❌ Erreurs

#### 400 - Erreur de Validation
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

#### 401 - Non Authentifié
```json
{
  "message": "Unauthenticated."
}
```

#### 404 - Token Non Trouvé ou Non Autorisé
```json
{
  "success": false,
  "error": "TOKEN_NOT_FOUND_OR_ALREADY_DELETED"
}
```

#### 500 - Erreur Interne
```json
{
  "success": false,
  "error": "INTERNAL_ERROR"
}
```

---

## 🔒 Sécurité

### Vérifications de Sécurité
1. **Authentification** : L'utilisateur doit être connecté
2. **Autorisation** : Le token doit appartenir à l'utilisateur connecté
3. **Validation** : Le token doit exister en base de données
4. **Propriété** : Vérification que `token.user_id === user.id`

### Code de Sécurité
```php
// 1. Récupération de l'utilisateur connecté
$userId = $request->user()->id;

// 2. Vérification de l'existence du token
$existingToken = $this->pushTokenRepository->findByToken($token);

// 3. Vérification de propriété
if (!$existingToken || $existingToken->getUserId() !== $userId) {
    return ['success' => false, 'error' => 'TOKEN_NOT_FOUND_OR_ALREADY_DELETED'];
}

// 4. Suppression sécurisée
$deleted = $this->pushTokenRepository->deleteToken($token);
```

---

## 🔄 Flux de Données

### 1. Requête Client
```http
DELETE /api/push-tokens
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
Content-Type: application/json

{
  "token": "ExponentPushToken[abc123def456ghi789]"
}
```

### 2. Validation Laravel
```php
// Validation des données
$data = $request->validate([
    'token' => 'required|string|max:255',
]);
```

### 3. Vérification de Sécurité
```php
// Récupération de l'utilisateur
$userId = $request->user()->id;

// Vérification de propriété du token
$existingToken = $this->pushTokenRepository->findByToken($data['token']);
if (!$existingToken || $existingToken->getUserId() !== $userId) {
    // Token n'existe pas ou n'appartient pas à l'utilisateur
    return response()->json(['success' => false, 'error' => 'TOKEN_NOT_FOUND_OR_ALREADY_DELETED'], 404);
}
```

### 4. Suppression en Base
```php
// Suppression du token
$deleted = $this->pushTokenRepository->deleteToken($data['token']);

if (!$deleted) {
    return response()->json(['success' => false, 'error' => 'TOKEN_NOT_FOUND_OR_ALREADY_DELETED'], 404);
}
```

### 5. Réponse de Succès
```json
{
  "success": true
}
```

---

## 🧪 Exemples d'Utilisation

### Exemple 1 : Suppression Réussie
```bash
curl -X DELETE "https://api.alarrache.com/api/push-tokens" \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..." \
  -H "Content-Type: application/json" \
  -d '{
    "token": "ExponentPushToken[abc123def456ghi789]"
  }'
```

**Réponse :**
```json
{
  "success": true
}
```

### Exemple 2 : Token d'Autre Utilisateur
```bash
curl -X DELETE "https://api.alarrache.com/api/push-tokens" \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..." \
  -H "Content-Type: application/json" \
  -d '{
    "token": "ExponentPushToken[autre-utilisateur-token]"
  }'
```

**Réponse :**
```json
{
  "success": false,
  "error": "TOKEN_NOT_FOUND_OR_ALREADY_DELETED"
}
```

### Exemple 3 : Token Inexistant
```bash
curl -X DELETE "https://api.alarrache.com/api/push-tokens" \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..." \
  -H "Content-Type: application/json" \
  -d '{
    "token": "ExponentPushToken[token-inexistant]"
  }'
```

**Réponse :**
```json
{
  "success": false,
  "error": "TOKEN_NOT_FOUND_OR_ALREADY_DELETED"
}
```

---

## 🔍 Cas d'Usage

### 1. Déconnexion Utilisateur
```javascript
// Frontend - Déconnexion
async function logout() {
  const token = await getStoredPushToken();
  
  if (token) {
    await fetch('/api/push-tokens', {
      method: 'DELETE',
      headers: {
        'Authorization': `Bearer ${userToken}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ token })
    });
  }
  
  // Puis déconnexion normale
  await logoutUser();
}
```

### 2. Changement d'Utilisateur sur Même Appareil
```javascript
// Frontend - Changement d'utilisateur
async function switchUser(newUserToken) {
  // 1. Supprimer le token de l'ancien utilisateur
  const oldToken = await getStoredPushToken();
  if (oldToken) {
    await deletePushToken(oldToken);
  }
  
  // 2. Se connecter avec le nouvel utilisateur
  await loginUser(newUserToken);
  
  // 3. Enregistrer le nouveau token
  const newPushToken = await getNewPushToken();
  await registerPushToken(newPushToken);
}
```

### 3. Désactivation des Notifications
```javascript
// Frontend - Désactivation des notifications
async function disableNotifications() {
  const token = await getStoredPushToken();
  
  if (token) {
    const response = await fetch('/api/push-tokens', {
      method: 'DELETE',
      headers: {
        'Authorization': `Bearer ${userToken}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ token })
    });
    
    if (response.ok) {
      await removeStoredPushToken();
      showMessage('Notifications désactivées');
    }
  }
}
```

---

## 🚨 Gestion des Erreurs

### Erreurs Communes et Solutions

#### 1. Token Non Trouvé
```json
{
  "success": false,
  "error": "TOKEN_NOT_FOUND_OR_ALREADY_DELETED"
}
```
**Cause :** Le token n'existe pas ou n'appartient pas à l'utilisateur  
**Solution :** Vérifier que le token est correct et appartient à l'utilisateur connecté

#### 2. Non Authentifié
```json
{
  "message": "Unauthenticated."
}
```
**Cause :** Token d'authentification manquant ou invalide  
**Solution :** S'assurer que l'utilisateur est connecté avec un token valide

#### 3. Erreur de Validation
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
**Cause :** Données de requête invalides  
**Solution :** Vérifier le format et la présence du token

---

## 📊 Monitoring et Logs

### Logs Générés
```php
// Succès
Log::info('Push token deleted successfully', [
    'user_id' => $userId,
    'token' => substr($token, 0, 20) . '...' // Masqué pour sécurité
]);

// Erreur
Log::error('Error deleting push token', [
    'user_id' => $userId,
    'token' => substr($token, 0, 20) . '...',
    'error' => $e->getMessage()
]);
```

### Métriques à Surveiller
- **Taux de succès** : > 95%
- **Temps de réponse** : < 500ms
- **Erreurs 404** : Tokens non trouvés
- **Erreurs 401** : Problèmes d'authentification

---

## 🔧 Maintenance

### Nettoyage des Tokens
```php
// Commande Artisan pour nettoyer les tokens inactifs
php artisan push-tokens:cleanup
```

### Vérification de l'Intégrité
```php
// Vérifier les tokens orphelins
SELECT * FROM push_tokens 
WHERE user_id NOT IN (SELECT id FROM users);
```

---

## 🧪 Tests

### Tests Automatisés
```bash
# Tests fonctionnels
php artisan test tests/Feature/PushToken/PushTokenSecurityTest.php

# Tests unitaires
php artisan test tests/Unit/PushToken/DeletePushTokenUseCaseTest.php
```

### Scénarios de Test
1. ✅ Suppression réussie du token de l'utilisateur
2. ✅ Échec lors de la suppression du token d'un autre utilisateur
3. ✅ Échec lors de la suppression d'un token inexistant
4. ✅ Échec sans authentification
5. ✅ Gestion des erreurs de validation

---

## 📚 Références

- **Documentation API** : `/docs/api/FR-20250122-001_IMPLEMENTATION.md`
- **Tests** : `/tests/Feature/PushToken/PushTokenSecurityTest.php`
- **Use Case** : `/app/UseCases/PushToken/DeletePushTokenUseCase.php`
- **Controller** : `/app/Http/Controllers/PushToken/DeletePushTokenAction.php`

---

**Dernière mise à jour :** 22 Janvier 2025  
**Version de l'API :** 1.0  
**Maintenu par :** Équipe Backend
