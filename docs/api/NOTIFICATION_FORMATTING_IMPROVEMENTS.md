# Améliorations du formatage des notifications d'invitation

## 📋 Vue d'ensemble

Ce document décrit les améliorations apportées au formatage des notifications d'invitation aux sessions sportives dans l'application Alarrache.

## 🎯 Objectifs

- **Titre informatif** : Afficher le sport dans le titre de la notification
- **Description claire** : Formater la date et l'heure en français de manière lisible
- **Cohérence** : Maintenir un format uniforme pour toutes les notifications d'invitation

## 🔧 Implémentation

### Service de formatage

Un nouveau service `DateFormatterService` a été créé pour gérer le formatage des dates et des noms de sports :

```php
use App\Services\DateFormatterService;

// Formatage de date et heure
$formatted = DateFormatterService::formatDateAndTime('2024-08-05', '10:30');
// Résultat: "lundi 5 août à 10h30"

// Génération de titre d'invitation
$title = DateFormatterService::generateInvitationTitle('tennis');
// Résultat: "Invitation Tennis"

// Génération de message complet
$message = DateFormatterService::generateInvitationMessage('tennis', '2024-08-05', '10:30');
// Résultat: "Vous avez été invité à une session de Tennis lundi 5 août à 10h30"
```

### Méthodes disponibles

#### `formatDateAndTime(string $date, string $time): string`
Formate une date et heure en français avec le format "jour jour_numéro mois à heurehminute"

#### `getSportName(string $sport): string`
Retourne le nom du sport en français avec majuscule

#### `generateInvitationTitle(string $sport): string`
Génère un titre de notification pour une invitation

#### `generateInvitationMessage(string $sport, string $date, string $time): string`
Génère un message complet de notification d'invitation

#### `generatePushInvitationTitle(string $sport): string`
Génère un titre de notification push avec emoji

#### `generatePushReinvitationTitle(string $sport): string`
Génère un titre de notification push pour une réinvitation

## 📱 Exemples de notifications

### Notification standard
```
Titre: Invitation Tennis
Message: Vous avez été invité à une session de Tennis lundi 5 août à 10h30
```

### Notification push
```
Titre: 🏃‍♂️ Invitation Football
Message: Vous avez été invité à une session de Football mardi 6 août à 14h00
```

### Réinvitation
```
Titre: 🏃‍♂️ Nouvelle invitation Golf
Message: Vous avez été invité à une session de Golf mercredi 7 août à 18h30
```

## 🏃‍♂️ Sports supportés

Les sports suivants sont supportés avec leurs noms en français :

- `tennis` → Tennis
- `golf` → Golf
- `musculation` → Musculation
- `football` → Football
- `basketball` → Basketball

Pour les sports non définis, le nom est capitalisé automatiquement.

## 📅 Formatage des dates

### Format d'entrée
- Date : `YYYY-MM-DD` (ex: `2024-08-05`)
- Heure : `HH:MM` (ex: `10:30`)

### Format de sortie
- Format français : `jour jour_numéro mois à heurehminute`
- Exemples :
  - `lundi 5 août à 10h30`
  - `mardi 6 août à 14h00`
  - `mercredi 7 août à 18h30`

## 🔄 Cas d'utilisation

### 1. Création d'une session avec participants
Lorsqu'une session est créée avec des participants, des notifications d'invitation sont envoyées automatiquement.

### 2. Invitation d'utilisateurs supplémentaires
Lorsqu'un organisateur invite de nouveaux utilisateurs à une session existante.

### 3. Réinvitation
Lorsqu'un utilisateur qui a décliné une invitation est réinvité.

## 🧪 Tests

Des tests unitaires ont été créés pour valider le bon fonctionnement du service :

```bash
php artisan test tests/Unit/DateFormatterServiceTest.php
```

### Tests disponibles
- Formatage des dates et heures
- Génération des noms de sports
- Génération des titres d'invitation
- Génération des messages complets
- Génération des titres de notifications push
- Tests avec différentes dates

## 🚀 Démonstration

Un script de démonstration est disponible pour tester le formatage :

```bash
php scripts/demo-notification-format.php
```

## 📝 Fichiers modifiés

### Nouveaux fichiers
- `app/Services/DateFormatterService.php` - Service de formatage
- `tests/Unit/DateFormatterServiceTest.php` - Tests unitaires
- `scripts/demo-notification-format.php` - Script de démonstration

### Fichiers modifiés
- `app/UseCases/SportSession/CreateSportSessionUseCase.php`
- `app/UseCases/SportSession/InviteUsersToSessionUseCase.php`
- `app/UseCases/SportSession/RespondToSessionInvitationUseCase.php`

## ✅ Validation

Les améliorations ont été validées par :
- Tests unitaires passants
- Script de démonstration fonctionnel
- Formatage cohérent pour tous les sports
- Support du français pour les dates et heures

## 🔮 Évolutions futures

- Support de nouveaux sports
- Personnalisation des messages selon l'organisateur
- Support de différents formats de date selon la locale
- Intégration avec le système de traduction Laravel
