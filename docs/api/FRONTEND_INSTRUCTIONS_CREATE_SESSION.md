# Instructions Frontend - Création d'une Session Sportive

## Endpoint

**URL :** `POST /api/sessions`

**Méthode :** `POST`

**Authentification :** Requise (Bearer Token)

**Headers :**
```
Authorization: Bearer <token>
Content-Type: application/json
```

## Format des Données

### Champs Requis

| Champ | Type | Format | Description | Exemple |
|-------|------|--------|-------------|---------|
| `sport` | string | Valeur exacte (voir liste) | Type de sport | `"tennis"` |
| `date` | string | `YYYY-MM-DD` | Date de la session | `"2024-03-25"` |
| `startTime` | string | `HH:MM` (24h) | Heure de début | `"18:00"` |
| `endTime` | string | `HH:MM` (24h) | Heure de fin | `"20:00"` |
| `location` | string | Max 255 caractères | Lieu de la session | `"Tennis Club de Paris"` |

### Champs Optionnels

| Champ | Type | Contraintes | Description | Exemple |
|-------|------|-------------|-------------|---------|
| `maxParticipants` | integer | 1-100 | Nombre maximum de participants | `4` |
| `pricePerPerson` | number | ≥ 0 | Prix par personne | `15.50` |
| `participantIds` | array | Tableau d'UUIDs | IDs des utilisateurs à inviter | `["uuid-1", "uuid-2"]` |

## Règles de Validation IMPORTANTES

### ⚠️ Format des Heures

**CRITIQUE :** Les heures doivent être au format `HH:MM` en 24 heures, **sans secondes**.

✅ **Correct :**
- `"18:00"`
- `"09:30"`
- `"23:59"`

❌ **Incorrect :**
- `"18:00:00"` (avec secondes)
- `"6:00 PM"` (format 12h)
- `"18h00"` (format français)
- `"18.00"` (point au lieu de deux-points)

### ⚠️ Validation Heure de Fin

**L'heure de fin DOIT être strictement supérieure à l'heure de début.**

✅ **Correct :**
- `startTime: "18:00"`, `endTime: "20:00"` ✅
- `startTime: "09:00"`, `endTime: "10:30"` ✅

❌ **Incorrect :**
- `startTime: "18:00"`, `endTime: "18:00"` ❌ (égalité)
- `startTime: "20:00"`, `endTime: "18:00"` ❌ (fin avant début)

### ⚠️ Format de Date

La date doit être au format `YYYY-MM-DD` et **ne peut pas être dans le passé**.

✅ **Correct :**
- `"2024-03-25"` (si aujourd'hui est le 24 mars ou avant)
- `"2024-12-31"`

❌ **Incorrect :**
- `"25/03/2024"` (format français)
- `"2024-03-24"` (si aujourd'hui est le 25 mars)
- `"03-25-2024"` (format américain)

## Exemple de Requête Complète

```json
{
  "sport": "tennis",
  "date": "2024-03-25",
  "startTime": "18:00",
  "endTime": "20:00",
  "location": "Tennis Club de Paris",
  "maxParticipants": 4,
  "pricePerPerson": 15.50,
  "participantIds": [
    "550e8400-e29b-41d4-a716-446655440000",
    "550e8400-e29b-41d4-a716-446655440001"
  ]
}
```

## Exemple de Requête Minimale

```json
{
  "sport": "tennis",
  "date": "2024-03-25",
  "startTime": "18:00",
  "endTime": "20:00",
  "location": "Tennis Club de Paris"
}
```

## Réponses

### Succès (201)

```json
{
  "success": true,
  "data": {
    "id": "550e8400-e29b-41d4-a716-446655440000",
    "sport": "tennis",
    "date": "2024-03-25",
    "startTime": "18:00",
    "endTime": "20:00",
    "location": "Tennis Club de Paris",
    "maxParticipants": 4,
    "pricePerPerson": 15.50,
    "status": "active",
    "organizer": {
      "id": "user-id",
      "fullName": "Jean Dupont"
    },
    "participants": [
      {
        "id": "user-id",
        "fullName": "Jean Dupont",
        "status": "accepted"
      }
    ],
    "comments": []
  },
  "message": "Session créée avec succès"
}
```

### Erreur de Validation (400)

```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Données invalides",
    "details": {
      "endTime": [
        "L'heure de fin doit être après l'heure de début"
      ]
    }
  }
}
```

**Autres erreurs de validation possibles :**
- `"date": ["The date does not match the format Y-m-d."]`
- `"startTime": ["The start time does not match the format H:i."]`
- `"endTime": ["The end time does not match the format H:i."]`
- `"sport": ["The selected sport is invalid."]`
- `"location": ["The location field is required."]`

## Liste des Sports Supportés

Les sports doivent être envoyés **exactement** comme indiqué ci-dessous (minuscules, avec accents et tirets) :

```
aïkido, aquafitness, athlétisme, aviron, badminton, baseball, basketball, 
bodyboard, bowling, boxe, course, cyclisme, danse, équitation, escalade, 
football, golf, gymnastique, handball, hockey, jiu-jitsu-brésilien, judo, 
karaté, kayak, marche-nordique, marche-sportive, musculation, natation, 
padel, pêche, pétanque, pilates, ping-pong, planche-à-voile, randonnée, 
rugby, sauvetage-sportif, ski, skateboard, snowboard, squash, 
stand-up-paddle, surf, tennis, tir-à-l-arc, triathlon, volleyball, yoga
```

## Code d'Exemple (JavaScript/TypeScript)

### Avec Fetch API

```javascript
async function createSession(sessionData) {
  // Validation côté client avant envoi
  if (!isValidTimeFormat(sessionData.startTime) || 
      !isValidTimeFormat(sessionData.endTime)) {
    throw new Error('Format d\'heure invalide. Utilisez HH:MM (ex: 18:00)');
  }

  if (new Date(sessionData.endTime) <= new Date(sessionData.startTime)) {
    throw new Error('L\'heure de fin doit être après l\'heure de début');
  }

  const response = await fetch('/api/sessions', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      sport: sessionData.sport,
      date: formatDate(sessionData.date), // Format: YYYY-MM-DD
      startTime: formatTime(sessionData.startTime), // Format: HH:MM
      endTime: formatTime(sessionData.endTime), // Format: HH:MM
      location: sessionData.location,
      maxParticipants: sessionData.maxParticipants || null,
      pricePerPerson: sessionData.pricePerPerson || null,
      participantIds: sessionData.participantIds || []
    })
  });

  const data = await response.json();

  if (!response.ok) {
    if (data.error?.code === 'VALIDATION_ERROR') {
      // Afficher les erreurs de validation
      console.error('Erreurs de validation:', data.error.details);
      throw new Error('Données invalides: ' + JSON.stringify(data.error.details));
    }
    throw new Error(data.error?.message || 'Erreur lors de la création');
  }

  return data.data;
}

// Fonctions utilitaires
function formatTime(time) {
  // Convertit "18:00:00" ou "6:00 PM" en "18:00"
  // Assurez-vous que le format final est HH:MM
  const date = new Date(`2000-01-01T${time}`);
  const hours = String(date.getHours()).padStart(2, '0');
  const minutes = String(date.getMinutes()).padStart(2, '0');
  return `${hours}:${minutes}`;
}

function formatDate(date) {
  // Convertit une date en format YYYY-MM-DD
  const d = new Date(date);
  const year = d.getFullYear();
  const month = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}

function isValidTimeFormat(time) {
  // Vérifie le format HH:MM
  return /^([0-1][0-9]|2[0-3]):[0-5][0-9]$/.test(time);
}
```

### Avec Axios

```javascript
import axios from 'axios';

async function createSession(sessionData) {
  try {
    const response = await axios.post('/api/sessions', {
      sport: sessionData.sport,
      date: formatDate(sessionData.date),
      startTime: formatTime(sessionData.startTime),
      endTime: formatTime(sessionData.endTime),
      location: sessionData.location,
      maxParticipants: sessionData.maxParticipants || null,
      pricePerPerson: sessionData.pricePerPerson || null,
      participantIds: sessionData.participantIds || []
    }, {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      }
    });

    return response.data.data;
  } catch (error) {
    if (error.response?.status === 400) {
      const errorData = error.response.data;
      if (errorData.error?.code === 'VALIDATION_ERROR') {
        // Gérer les erreurs de validation
        console.error('Erreurs:', errorData.error.details);
        throw new Error('Validation failed: ' + JSON.stringify(errorData.error.details));
      }
    }
    throw error;
  }
}
```

## Points d'Attention pour le Frontend

1. **Format des heures** : Toujours utiliser `HH:MM` (24h), jamais de secondes
2. **Validation avant envoi** : Vérifier que `endTime > startTime` côté client
3. **Format de date** : Toujours `YYYY-MM-DD`, jamais de format localisé
4. **Sports** : Utiliser exactement les valeurs de la liste (minuscules, avec accents)
5. **Champs optionnels** : Envoyer `null` ou omettre le champ, mais ne pas envoyer de chaîne vide
6. **Gestion d'erreurs** : Toujours vérifier `error.code === 'VALIDATION_ERROR'` pour afficher les détails

## Comportement Automatique du Backend

- L'utilisateur connecté est automatiquement ajouté comme organisateur
- L'organisateur est automatiquement ajouté comme participant avec le statut `"accepted"`
- Les utilisateurs dans `participantIds` sont ajoutés avec le statut `"pending"` (invitation)
- Le sport est automatiquement ajouté aux préférences de l'organisateur

## Dépannage

### Erreur "validation.after" sur endTime

**Cause :** La règle de validation Laravel `after:startTime` ne fonctionne pas avec des heures au format `H:i`.

**Solution :** Le backend fait maintenant une validation personnalisée. Assurez-vous que :
- `startTime` et `endTime` sont au format `HH:MM` (ex: `"18:00"`)
- `endTime` est strictement supérieur à `startTime`

### Erreur "date_format"

**Cause :** Le format de date ou d'heure ne correspond pas.

**Solution :** 
- Date : `YYYY-MM-DD` (ex: `"2024-03-25"`)
- Heure : `HH:MM` (ex: `"18:00"`)

### Erreur "sport invalide"

**Cause :** Le sport n'est pas dans la liste ou a une casse/format incorrect.

**Solution :** Utiliser exactement les valeurs de la liste (minuscules, avec accents et tirets).

