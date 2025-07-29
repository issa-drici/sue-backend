# Endpoint d'Annulation de Demande d'Ami

## 📋 Vue d'ensemble

**URL :** `DELETE /api/users/friend-requests`  
**Méthode :** `DELETE`  
**Description :** Annuler une demande d'ami envoyée par l'utilisateur connecté

## 🔧 Spécifications

### Headers Requis

```
Authorization: Bearer <token>
Content-Type: application/json
```

### Body (JSON)

```json
{
  "target_user_id": "uuid-de-l-utilisateur-cible"
}
```

### Paramètres

- `target_user_id` (string, requis) : ID de l'utilisateur cible de la demande d'ami

## 📤 Réponses

### Réponse Succès (200)

```json
{
  "success": true,
  "data": {
    "requestId": "uuid-de-la-demande",
    "senderId": "uuid-de-l-expediteur",
    "receiverId": "uuid-du-destinataire",
    "status": "cancelled",
    "cancelledAt": "2025-01-20T10:30:00.000000Z"
  },
  "message": "Demande d'ami annulée avec succès"
}
```

### Réponse Erreur (400) - Paramètre manquant

```json
{
  "success": false,
  "error": {
    "code": "MISSING_TARGET_USER_ID",
    "message": "L'ID de l'utilisateur cible est requis"
  }
}
```

### Réponse Erreur (404) - Demande introuvable

```json
{
  "success": false,
  "error": {
    "code": "FRIEND_REQUEST_NOT_FOUND",
    "message": "Demande d'ami introuvable"
  }
}
```

### Réponse Erreur (403) - Non autorisé

```json
{
  "success": false,
  "error": {
    "code": "UNAUTHORIZED_CANCELLATION",
    "message": "Vous ne pouvez annuler que vos propres demandes d'ami"
  }
}
```

### Réponse Erreur (409) - Demande déjà traitée

```json
{
  "success": false,
  "error": {
    "code": "REQUEST_ALREADY_PROCESSED",
    "message": "Cette demande d'ami a déjà été acceptée ou refusée"
  }
}
```

## 🔄 Logique Métier

### Règles de Validation

1. **Authentification** : L'utilisateur doit être connecté
2. **Paramètre requis** : `target_user_id` doit être fourni
3. **Existence de la demande** : Une demande doit exister entre l'utilisateur connecté et l'utilisateur cible
4. **Propriétaire de la demande** : Seul l'utilisateur qui a envoyé la demande peut l'annuler
5. **Statut de la demande** : La demande doit être en statut "pending"

### Actions Effectuées

1. Vérifier l'authentification de l'utilisateur
2. Valider la présence du paramètre `target_user_id`
3. Rechercher la demande d'ami basée sur les IDs des utilisateurs
4. Vérifier que l'utilisateur connecté est le propriétaire de la demande
5. Vérifier que la demande est en statut "pending"
6. Marquer la demande comme "cancelled" et enregistrer la date d'annulation
7. Retourner la confirmation avec les détails de la demande

## 📱 Utilisation Frontend

### Exemple d'utilisation

```javascript
// Annuler une demande d'ami
const cancelFriendRequest = async (targetUserId) => {
  try {
    const response = await fetch('/api/users/friend-requests', {
      method: 'DELETE',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        target_user_id: targetUserId
      })
    });

    const data = await response.json();
    
    if (data.success) {
      console.log('Demande annulée:', data.data);
      // Mettre à jour l'interface utilisateur
      updateUIAfterCancellation(data.data);
    } else {
      console.error('Erreur:', data.error);
      // Afficher l'erreur à l'utilisateur
      showError(data.error.message);
    }
  } catch (error) {
    console.error('Erreur réseau:', error);
  }
};
```

### Intégration avec la recherche d'utilisateurs

```javascript
// Dans la liste des résultats de recherche
const handleCancelRequest = (userId) => {
  cancelFriendRequest(userId).then(() => {
    // Mettre à jour l'affichage pour montrer "Aucune relation"
    updateUserRelationshipStatus(userId, 'none');
  });
};
```

## 🔗 Endpoints Liés

- `GET /api/users/search` : Recherche d'utilisateurs avec statut de relation
- `POST /api/users/friend-requests` : Envoyer une demande d'ami
- `PATCH /api/users/friend-requests/{id}` : Accepter/refuser une demande reçue

## 🧪 Tests

### Cas de test couverts

- ✅ Annulation réussie d'une demande en attente
- ✅ Erreur si `target_user_id` manquant
- ✅ Erreur si demande inexistante
- ✅ Erreur si utilisateur non autorisé
- ✅ Erreur si demande déjà traitée
- ✅ Erreur si demande déjà annulée

### Exemple de test

```bash
# Test d'annulation réussie
curl -X DELETE http://localhost:8000/api/users/friend-requests \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"target_user_id": "uuid-cible"}'
```

## 📊 Base de Données

### Table `friend_requests`

- `id` : UUID de la demande
- `sender_id` : ID de l'expéditeur
- `receiver_id` : ID du destinataire
- `status` : Statut de la demande (pending, accepted, declined, cancelled)
- `cancelled_at` : Date d'annulation (nullable)
- `created_at` : Date de création
- `updated_at` : Date de mise à jour

### Migration

```php
Schema::table('friend_requests', function (Blueprint $table) {
    $table->timestamp('cancelled_at')->nullable()->after('status');
});
```

## 🚀 Déploiement

### Checklist

- [ ] Migration de base de données exécutée
- [ ] Tests unitaires passent
- [ ] Tests d'intégration passent
- [ ] Documentation mise à jour
- [ ] Frontend mis à jour pour utiliser le nouvel endpoint

### Notes de version

**Version :** 1.0  
**Date :** 20 Janvier 2025  
**Changements :**
- Nouvel endpoint d'annulation de demande d'ami
- Utilisation des IDs utilisateurs au lieu de l'ID de demande
- Gestion complète des erreurs et validations
- Support du statut "cancelled" dans la base de données 
