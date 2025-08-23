# Résumé de l'implémentation - Annulation et modification de session

## 📋 Fonctionnalités implémentées

**FR-20250122-003** - Modification d'une session existante
**FR-20250122-004** - Annulation complète d'une session

## 🎯 Description

### Modification d'une session existante
Permettre à l'organisateur d'une session de modifier les détails de sa session (date, heure, lieu, nombre maximum de participants).

### Annulation complète d'une session
Permettre à l'organisateur d'une session d'annuler complètement sa session.

## 🔧 Composants implémentés

### 1. Base de données
- **Migration** : `2025_08_23_181903_add_status_to_sport_sessions_table.php`
  - Ajout du champ `status` (enum: 'active', 'cancelled') à la table `sport_sessions`
- **Migration** : `2025_08_23_182827_add_new_notification_types.php`
  - Ajout des nouveaux types de notifications : `session_update`, `session_cancelled`

### 2. Entité SportSession
- **Fichier** : `app/Entities/SportSession.php`
- **Modifications** :
  - Ajout du champ `status` dans l'entité
  - Ajout de la méthode `getStatus()`
  - Mise à jour de la méthode `toArray()` pour inclure le statut

### 3. Modèle SportSessionModel
- **Fichier** : `app/Models/SportSessionModel.php`
- **Modifications** :
  - Ajout de `status` dans le tableau `$fillable`

### 4. Repository SportSessionRepository
- **Fichier** : `app/Repositories/SportSession/SportSessionRepository.php`
- **Modifications** :
  - Mise à jour de la méthode `mapToEntity()` pour inclure le statut
  - Mise à jour de la méthode `create()` pour définir le statut par défaut à 'active'

### 5. UseCase - Modification de session
- **Fichier** : `app/UseCases/SportSession/UpdateSportSessionUseCase.php`
- **Modifications** :
  - Ajout de la validation du champ `maxParticipants` (1-50)
  - Amélioration des notifications avec le nom de l'organisateur
  - Ajout du type de notification `session_update`

### 6. UseCase - Annulation de session
- **Fichier** : `app/UseCases/SportSession/CancelSportSessionUseCase.php`
- **Responsabilité** : Logique métier pour l'annulation complète d'une session
- **Fonctionnalités** :
  - Validation des permissions (organisateur uniquement)
  - Vérification que la session n'est pas déjà annulée
  - Vérification que la session n'est pas terminée
  - Mise à jour du statut de la session à 'cancelled'
  - Création de notifications pour tous les participants acceptés
  - Envoi de notifications push

### 7. Contrôleurs
- **Fichier** : `app/Http/Controllers/SportSession/UpdateSportSessionAction.php`
  - Ajout de la validation du champ `maxParticipants`
  - Mapping du champ camelCase vers snake_case
- **Fichier** : `app/Http/Controllers/SportSession/CancelSportSessionAction.php`
  - **Responsabilité** : Gestion des requêtes HTTP pour l'annulation
  - **Endpoint** : `PATCH /api/sessions/{sessionId}/cancel`
  - **Codes de réponse** :
    - `200` - Session annulée avec succès
    - `400` - Session déjà annulée ou terminée
    - `403` - Non autorisé
    - `404` - Session non trouvée

### 8. Routes
- **Fichier** : `routes/api.php`
- **Route ajoutée** : `Route::patch('/sessions/{id}/cancel', CancelSportSessionAction::class);`

### 9. Tests
- **Fichier** : `tests/Feature/SportSession/CancelSportSessionTest.php`
- **Tests couverts** :
  - ✅ Annulation réussie par l'organisateur
  - ✅ Non-organisateur ne peut pas annuler
  - ✅ Session déjà annulée ne peut pas être annulée
  - ✅ Session terminée ne peut pas être annulée
  - ✅ Session inexistante retourne 404
  - ✅ Notifications créées pour les participants acceptés
  - ✅ Participants en attente ne reçoivent pas de notifications

- **Fichier** : `tests/Feature/SportSession/UpdateSportSessionTest.php`
- **Tests couverts** :
  - ✅ Modification réussie par l'organisateur
  - ✅ Non-organisateur ne peut pas modifier
  - ✅ Validation des données invalides
  - ✅ Session terminée ne peut pas être modifiée
  - ✅ Notifications créées pour les participants acceptés
  - ✅ Participants en attente ne reçoivent pas de notifications

### 10. Factory
- **Fichier** : `database/factories/SportSessionModelFactory.php`
- **Modifications** :
  - Ajout du statut par défaut 'active'
  - Ajout de la méthode `cancelled()` pour créer des sessions annulées

## 🔄 Logique métier

### Conditions préalables pour la modification
1. L'utilisateur doit être l'organisateur de la session
2. La session ne doit pas être terminée
3. Les nouvelles données doivent être valides

### Conditions préalables pour l'annulation
1. L'utilisateur doit être l'organisateur de la session
2. La session ne doit pas être déjà annulée
3. La session ne doit pas être terminée

### Validation des données
- **date** : Doit être dans le futur
- **time** : Format HH:MM
- **location** : Max 200 caractères
- **maxParticipants** : Optionnel, entre 1 et 50

## 📡 Format de réponse

### Annulation - Succès (200)
```json
{
  "success": true,
  "message": "Session annulée avec succès",
  "data": {
    "session": {
      "id": "session-uuid",
      "sport": "tennis",
      "date": "2025-02-15",
      "time": "14:00",
      "location": "Tennis Club",
      "status": "cancelled",
      "organizer": {
        "id": "organizer-uuid",
        "fullName": "Jean Dupont"
      },
      "participants": [...]
    }
  }
}
```

### Erreurs communes
- `400` - Session déjà annulée ou terminée
- `403` - Non autorisé (pas l'organisateur)
- `404` - Session non trouvée

## 🔔 Notifications

### Types de notifications ajoutés
- `session_update` : Session modifiée par l'organisateur
- `session_cancelled` : Session annulée par l'organisateur

### Données des notifications
- **session_update** : Contient les changements (date, heure, lieu)
- **session_cancelled** : Contient les détails de la session annulée

## 📊 Impact

### Positif
- ✅ Amélioration de l'expérience utilisateur
- ✅ Plus de flexibilité pour les organisateurs
- ✅ Correction d'erreurs possibles
- ✅ Gestion propre des annulations

### Risques
- ⚠️ Notifications multiples pour les participants
- ⚠️ Annulations de dernière minute
- ⚠️ Impact sur la planification des participants

## 🔗 Liens

- **FR-20250122-003** : Modification d'une session existante
- **FR-20250122-004** : Annulation complète d'une session
- **Endpoints** : 
  - `PUT /api/sessions/{id}` - Modification
  - `PATCH /api/sessions/{id}/cancel` - Annulation
- **Tests** : 
  - `tests/Feature/SportSession/UpdateSportSessionTest.php`
  - `tests/Feature/SportSession/CancelSportSessionTest.php` 
