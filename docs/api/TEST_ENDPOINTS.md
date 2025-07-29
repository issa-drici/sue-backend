# Guide de test des endpoints API Alarrache

## 🧪 Tests rapides avec cURL

### 1. Test de santé de l'API
```bash
curl -X GET http://localhost:8000/api/health
```

### 2. Inscription d'un utilisateur
```bash
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{
    "firstName": "Jean",
    "lastName": "Dupont",
    "email": "jean.dupont@example.com",
    "password": "password123"
  }'
```

### 3. Connexion
```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "jean.dupont@example.com",
    "password": "password123"
  }'
```

### 4. Créer une session (avec token)
```bash
# Remplacez YOUR_TOKEN par le token reçu lors de la connexion
curl -X POST http://localhost:8000/api/sessions \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "sport": "tennis",
    "date": "2024-03-25",
    "time": "18:00",
    "location": "Tennis Club de Paris"
  }'
```

### 5. Récupérer les sessions
```bash
curl -X GET "http://localhost:8000/api/sessions?page=1&limit=10" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### 6. Récupérer une session spécifique
```bash
# Remplacez SESSION_ID par l'ID de la session créée
curl -X GET http://localhost:8000/api/sessions/SESSION_ID \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### 7. Récupérer les notifications
```bash
curl -X GET http://localhost:8000/api/notifications \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## 🎯 Tests avec Postman

### Collection Postman
1. Importez les endpoints dans Postman
2. Configurez les variables d'environnement :
   - `base_url`: `http://localhost:8000/api`
   - `token`: Token JWT reçu lors de la connexion

### Tests automatisés
```bash
# Installer les dépendances de test
composer install

# Exécuter les tests
php artisan test
```

## 📊 Validation des réponses

### Réponse de connexion attendue
```json
{
  "success": true,
  "data": {
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "user": {
      "id": "1",
      "email": "jean.dupont@example.com",
      "firstName": "Jean",
      "lastName": "Dupont"
    }
  },
  "message": "Connexion réussie"
}
```

### Réponse de création de session attendue
```json
{
  "success": true,
  "data": {
    "id": "uuid",
    "sport": "tennis",
    "date": "2024-03-25",
    "time": "18:00",
    "location": "Tennis Club de Paris",
    "organizer": {
      "id": "1",
      "fullName": "Jean Dupont"
    },
    "participants": [],
    "comments": []
  },
  "message": "Session créée avec succès"
}
```

## 🔍 Points de vérification

### ✅ Fonctionnalités à tester
1. **Authentification**
   - Inscription avec données valides
   - Connexion avec credentials corrects
   - Gestion des erreurs d'authentification

2. **Sessions**
   - Création de session avec données valides
   - Validation des sports supportés
   - Validation des dates (pas dans le passé)
   - Récupération avec filtres et pagination

3. **Notifications**
   - Création automatique lors de la création de session
   - Récupération des notifications utilisateur

4. **Sécurité**
   - Protection des routes avec authentification
   - Validation des données d'entrée
   - Gestion des erreurs appropriée

### ❌ Cas d'erreur à tester
1. **Données invalides**
   - Sport non supporté
   - Date dans le passé
   - Champs manquants

2. **Authentification**
   - Token invalide
   - Token expiré
   - Accès sans authentification

3. **Ressources inexistantes**
   - Session ID invalide
   - Utilisateur inexistant

## 🚀 Intégration avec l'app mobile

### Configuration de l'app mobile
```javascript
// Configuration de l'API
const API_BASE_URL = 'http://localhost:8000/api';

// Headers par défaut
const headers = {
  'Content-Type': 'application/json',
  'Authorization': `Bearer ${token}`
};

// Exemple d'appel API
const response = await fetch(`${API_BASE_URL}/sessions`, {
  method: 'GET',
  headers
});

const data = await response.json();
```

### Remplacement des mocks
1. Remplacer les appels vers les données mockées
2. Utiliser les vrais endpoints de l'API
3. Gérer l'authentification JWT
4. Adapter la gestion des erreurs

## 📝 Notes importantes

- L'API utilise des UUIDs pour les IDs
- Toutes les dates sont au format ISO 8601
- Les réponses incluent toujours un champ `success`
- Les erreurs suivent un format standardisé
- L'authentification est requise pour la plupart des endpoints 
