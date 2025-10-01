# API Sports Préférés - Documentation Backend

## Vue d'ensemble

Ce document détaille les endpoints de gestion des sports préférés utilisateur pour l'application Alarrache.

## Base URL
```
https://api.alarrache.com/api
```

## Endpoints

### 1. PUT /users/sports-preferences

**Description :** Mettre à jour les sports préférés de l'utilisateur connecté

**URL :** `/users/sports-preferences`

**Méthode :** `PUT`

**Headers :**
```
Authorization: Bearer <token>
Content-Type: application/json
```

**Body :**
```json
{
  "sports_preferences": ["tennis", "football", "basketball"]
}
```

**Réponse Succès (200) :**
```json
{
  "success": true,
  "message": "Sports préférés mis à jour avec succès",
  "data": {
    "sports_preferences": ["tennis", "football", "basketball"]
  }
}
```

**Réponse Erreur (400) - Sports invalides :**
```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Sports invalides ou utilisateur non trouvé"
  }
}
```

**Réponse Erreur (400) - Données invalides :**
```json
{
  "success": false,
  "error": {
    "code": "INVALID_DATA",
    "message": "Les sports préférés doivent être un tableau"
  }
}
```

**Réponse Erreur (401) - Non autorisé :**
```json
{
  "message": "Unauthenticated."
}
```

### 2. GET /users/profile (modifié)

**Description :** Récupérer le profil de l'utilisateur connecté (inclut maintenant les sports préférés)

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
    "sports_preferences": ["tennis", "football", "basketball"],
    "stats": {
      "sessionsCreated": 12,
      "sessionsParticipated": 45
    }
  }
}
```

## Validation des sports

### Sports valides
Les sports suivants sont acceptés (48 sports au total) :

**Sports de raquette :**
- `tennis`, `padel`, `badminton`, `squash`, `ping-pong`, `volleyball`, `basketball`, `handball`

**Sports aquatiques :**
- `natation`, `surf`, `planche-à-voile`, `kayak`, `aviron`, `aquafitness`, `sauvetage-sportif`, `bodyboard`

**Sports d'endurance :**
- `course`, `cyclisme`, `randonnée`, `marche-nordique`, `marche-sportive`, `triathlon`

**Arts martiaux :**
- `boxe`, `jiu-jitsu-brésilien`, `aïkido`, `judo`, `karaté`

**Sports de glisse :**
- `ski`, `snowboard`, `skateboard`, `stand-up-paddle`

**Sports collectifs :**
- `football`, `rugby`, `hockey`, `baseball`, `volleyball`, `handball`

**Sports de bien-être :**
- `yoga`, `pilates`, `danse`

**Sports de précision :**
- `golf`, `tir-à-l-arc`, `pétanque`

**Autres :**
- `musculation`, `escalade`, `équitation`, `gymnastique`, `athlétisme`, `bowling`, `pêche`

**Liste complète (ordre alphabétique) :**
`aïkido`, `aquafitness`, `athlétisme`, `aviron`, `badminton`, `baseball`, `basketball`, `bodyboard`, `bowling`, `boxe`, `course`, `cyclisme`, `danse`, `équitation`, `escalade`, `football`, `golf`, `gymnastique`, `handball`, `hockey`, `jiu-jitsu-brésilien`, `judo`, `karaté`, `kayak`, `marche-nordique`, `marche-sportive`, `musculation`, `natation`, `padel`, `pêche`, `pétanque`, `pilates`, `ping-pong`, `planche-à-voile`, `randonnée`, `rugby`, `sauvetage-sportif`, `ski`, `skateboard`, `snowboard`, `squash`, `stand-up-paddle`, `surf`, `tennis`, `tir-à-l-arc`, `triathlon`, `volleyball`, `yoga`

### Règles de validation
1. **Sports valides uniquement** : Seuls les sports listés ci-dessus sont acceptés
2. **Maximum 5 sports** : Un utilisateur ne peut pas avoir plus de 5 sports préférés
3. **Tableau requis** : Le champ `sports_preferences` doit être un tableau
4. **Ordre préservé** : L'ordre des sports est préservé tel que fourni

### Exemples de validation

#### ✅ Valide
```json
{
  "sports_preferences": ["tennis", "football"]
}
```

#### ✅ Valide (5 sports maximum)
```json
{
  "sports_preferences": ["tennis", "golf", "musculation", "football", "basketball"]
}
```

#### ✅ Valide (nouveaux sports)
```json
{
  "sports_preferences": ["natation", "yoga", "padel", "escalade", "surf"]
}
```

#### ❌ Invalide (sport non reconnu)
```json
{
  "sports_preferences": ["invalid-sport", "tennis"]
}
```

#### ❌ Invalide (trop de sports)
```json
{
  "sports_preferences": ["tennis", "golf", "musculation", "football", "basketball", "natation"]
}
```

#### ❌ Invalide (pas un tableau)
```json
{
  "sports_preferences": "tennis"
}
```

## Codes de réponse

| Code | Description |
|------|-------------|
| 200 | Succès |
| 400 | Données invalides (sports non reconnus, trop de sports, format incorrect) |
| 401 | Non autorisé (token manquant ou invalide) |
| 422 | Erreur de validation |
| 500 | Erreur serveur |

## Cas d'usage

### 1. Définir ses sports préférés
```bash
curl -X PUT https://api.alarrache.com/api/users/sports-preferences \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"sports_preferences": ["tennis", "football"]}'
```

### 2. Récupérer son profil avec sports préférés
```bash
curl -X GET https://api.alarrache.com/api/users/profile \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### 3. Mettre à jour ses sports préférés
```bash
curl -X PUT https://api.alarrache.com/api/users/sports-preferences \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"sports_preferences": ["tennis", "golf", "basketball"]}'
```

## Impact sur le mobile

### Écrans concernés
- `app/create-session.tsx` - Affichage prioritaire des sports préférés
- `app/(onboarding)/` - Éventuel écran de sélection des sports (optionnel)

### Hooks/Composants à créer
- `hooks/useSportsPreferences.ts` - Gestion des sports préférés
- `services/users/updateSportsPreferences.ts` - Service API
- Modification de `services/users/getUserProfile.ts`

### Tests à implémenter
- Test de récupération des sports préférés
- Test de mise à jour des sports préférés
- Test de validation des sports
- Test d'affichage prioritaire dans create-session

## Notes techniques

### Base de données
- Le champ `sports_preferences` est stocké en JSON dans la table `users`
- Le champ est nullable (peut être null si aucun sport préféré défini)
- L'ordre des sports est préservé

### Performance
- Aucun impact sur les performances existantes
- Les sports préférés sont récupérés avec le profil utilisateur
- Validation côté serveur pour garantir l'intégrité des données

### Sécurité
- Authentification requise pour tous les endpoints
- Validation stricte des sports acceptés
- Limitation du nombre de sports pour éviter les abus
