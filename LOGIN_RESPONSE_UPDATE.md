# Mise à jour de la réponse de connexion - Ajout de firstname et lastname

## 📋 Résumé des modifications

Cette mise à jour modifie l'endpoint `/api/login` pour qu'il retourne `firstname` et `lastname` dans la réponse utilisateur, au lieu de `full_name`, afin d'être cohérent avec l'endpoint `/api/register`.

## 🔧 Modifications apportées

### 1. Contrôleur d'authentification
**Fichier :** `app/Http/Controllers/Auth/AuthenticatedSessionController.php`

**Avant :**
```php
'user' => $user->only('id', 'email', 'full_name', 'role'),
```

**Après :**
```php
'user' => $user->only('id', 'email', 'firstname', 'lastname', 'role'),
```

### 2. Repository UserProfile
**Fichier :** `app/Repositories/UserProfile/UserProfileRepository.php`

**Avant :**
```php
->select('users.id as user_id', 'users.full_name')
```

**Après :**
```php
->select('users.id as user_id', 'users.firstname', 'users.lastname')
```

### 3. Contrôleur FindUserByIdAction
**Fichier :** `app/Http/Controllers/User/FindUserByIdAction.php`

**Avant :**
```php
'full_name' => 'John Doe',
```

**Après :**
```php
'firstname' => 'John',
'lastname' => 'Doe',
```

### 4. Tests
**Fichier :** `test_endpoints.php`

**Avant :**
```php
'full_name' => 'Test User',
```

**Après :**
```php
'firstname' => 'Test',
'lastname' => 'User',
```

## 📊 Comparaison des réponses

### Réponse de connexion AVANT :
```json
{
  "message": "Connexion réussie",
  "token": "3|SE2CMrpPKR1lFHD2keaqlScE4znF6ouZSralFFdw3a709976",
  "user": {
    "id": "9f8fee90-222c-4df7-ad0d-a205991b1ae2",
    "email": "test@example.com",
    "full_name": null,
    "role": "player"
  }
}
```

### Réponse de connexion APRÈS :
```json
{
  "message": "Connexion réussie",
  "token": "3|SE2CMrpPKR1lFHD2keaqlScE4znF6ouZSralFFdw3a709976",
  "user": {
    "id": "9f8fee90-222c-4df7-ad0d-a205991b1ae2",
    "email": "test@example.com",
    "firstname": "Test",
    "lastname": "User",
    "role": "player"
  }
}
```

## ✅ Avantages de cette modification

1. **Cohérence** : Les réponses de connexion et d'inscription sont maintenant identiques
2. **Simplicité frontend** : Plus besoin de logique de normalisation complexe
3. **Données utiles** : `firstname` et `lastname` sont plus utiles que `full_name` (souvent null)
4. **Maintenance** : Code plus simple à maintenir

## 🧪 Test de validation

Un script de test a été créé : `test_login_response.php`

Pour exécuter le test :
```bash
php test_login_response.php
```

Ce test vérifie :
- ✅ La présence de `firstname` et `lastname` dans la réponse
- ✅ L'absence de `full_name` dans la réponse
- ✅ La cohérence avec l'endpoint d'inscription

## 📱 Impact sur le frontend

Cette modification permet de :
- Supprimer la logique de normalisation complexe
- Avoir une cohérence entre les réponses de connexion et d'inscription
- Éviter les valeurs par défaut comme "Utilisateur Anonyme"
- Simplifier le code d'authentification

## 🔄 Compatibilité

Cette modification est **rétrocompatible** car :
- Les nouveaux champs (`firstname`, `lastname`) sont ajoutés
- L'ancien champ (`full_name`) est supprimé mais n'était pas utilisé de manière critique
- La structure générale de la réponse reste la même

## 📅 Date de mise en production

**Date :** 5 août 2025
**Statut :** ✅ Implémenté et testé
**Priorité :** Haute 
