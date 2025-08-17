# Laravel Clean Architecture Boilerplate

Un boilerplate Laravel moderne basé sur les principes de **Clean Architecture** avec une séparation claire des responsabilités.

## 🏗️ Architecture

Ce projet utilise une architecture en couches avec :

- **Entities** : Entités du domaine métier
- **Use Cases** : Logique métier et cas d'usage
- **Repositories** : Accès aux données avec interfaces
- **Controllers** : Contrôleurs HTTP Single Action
- **Models** : Modèles Eloquent pour l'infrastructure
- **Services** : Services métier réutilisables

## 🚀 Installation

```bash
# Cloner le projet
git clone [repository-url]

# Installer les dépendances
composer install

# Copier le fichier d'environnement
cp .env.example .env

# Générer la clé d'application
php artisan key:generate

# Configurer la base de données dans .env

# Exécuter les migrations
php artisan migrate

# Installer les dépendances frontend
npm install

# Compiler les assets
npm run build
```

## 🛠️ Développement

```bash
# Démarrer le serveur de développement
composer run dev

# Ou séparément
php artisan serve
npm run dev
```

## 🧪 Tests

```bash
# Exécuter tous les tests
php artisan test

# Exécuter les tests avec couverture
php artisan test --coverage
```

## 📁 Structure du projet

```
app/
├── Entities/           # Entités du domaine
├── Http/
│   ├── Controllers/    # Contrôleurs HTTP (Single Action)
│   ├── Middleware/     # Middleware personnalisé
│   └── Requests/       # Form Requests de validation
├── Models/             # Modèles Eloquent
├── Repositories/       # Repositories avec interfaces
├── Services/           # Services métier
└── UseCases/           # Cas d'usage

database/
├── factories/          # Factories pour les tests
├── migrations/         # Migrations de base de données
└── seeders/           # Seeders de données

tests/
├── Feature/           # Tests d'intégration
└── Unit/              # Tests unitaires
```

## 🎯 Exemples inclus

Le boilerplate inclut des exemples complets pour vous guider :

- **ExampleEntity** : Entité d'exemple avec validation métier
- **ExampleModel** : Modèle Eloquent avec relations et scopes
- **ExampleRepository** : Repository avec interface
- **FindAllExamplesUseCase** : Use Case avec logique métier
- **FindAllExamplesAction** : Contrôleur Single Action
- **Tests complets** : Tests Feature et Unit
- **Migration** : Structure de base de données
- **Factory** : Données de test

## 🔧 Configuration

### Variables d'environnement importantes

- `APP_ENV` - Environnement (local, staging, production)
- `DB_*` - Configuration de la base de données
- `AWS_*` - Configuration AWS S3
- `MAIL_*` - Configuration email

## 📚 Règles Cursor

Ce projet inclut des règles Cursor complètes dans `.cursor/rules/` pour :

- **Architecture** : Patterns et conventions
- **Contrôleurs** : Single Action Controllers
- **Use Cases** : Logique métier
- **Repositories** : Pattern Repository
- **Entités** : Domain Entities
- **Modèles** : Eloquent Models
- **Services** : Services métier
- **Routing** : Conventions RESTful
- **Base de données** : Migrations et schéma
- **Tests** : Patterns avec Pest
- **Clean Code** : Principes SOLID et DRY
- **Setup** : Configuration boilerplate

## 🚀 Création d'un nouveau module

1. **Créer l'entité** : `app/Entities/YourEntity.php`
2. **Créer le modèle** : `app/Models/YourModel.php`
3. **Créer le repository** : `app/Repositories/Your/YourRepositoryInterface.php` et `YourRepository.php`
4. **Créer le Use Case** : `app/UseCases/Your/YourUseCase.php`
5. **Créer le contrôleur** : `app/Http/Controllers/Your/YourAction.php`
6. **Créer la migration** : `database/migrations/xxx_create_your_table.php`
7. **Créer les tests** : `tests/Feature/Your/YourTest.php`
8. **Ajouter la route** : `routes/api.php`
9. **Enregistrer le binding** : `app/Providers/AppServiceProvider.php`

## 📚 API Documentation

L'API est documentée avec Postman. Importez le fichier `api-postman.json` dans Postman.

### Endpoints d'exemple

- `GET /api/health` - Vérification de santé
- `GET /api/version` - Version de l'API
- `GET /api/examples` - Liste des exemples (avec filtres)
- `GET /api/profile` - Profil utilisateur (protégé)

## 🔔 Notifications Push (Expo)

Backend inclut un service `App\Services\ExpoPushNotificationService` pour envoyer des notifications via l'API Expo (`https://exp.host/--/api/v2/push/send`).

Endpoints:
- `POST /api/push-tokens` (auth): enregistre ou met à jour un token Expo pour l'utilisateur courant. Body: `{ "token": string, "platform": "expo|ios|android", "device_id"?: string }`
- `DELETE /api/push-tokens` (auth): supprime un token. Body: `{ "token": string }`
- `POST /api/notifications/send` (auth): envoi manuel de push à un utilisateur (test).

Déclencheurs intégrés:
- Commentaire créé sur une session: push aux participants (hors auteur) avec `data.type = "comment"`
- Invitation envoyée à une session: `data.type = "session_invitation"`
- Invitation acceptée: push à l'organisateur avec `data.type = "session_update"`
- Demande d'ami créée: push au destinataire avec `data.type = "friend_request"`

Résilience:
- Envois par lots de 100, logs, invalidation des tokens renvoyés par Expo.

## 🤝 Contribution

1. Fork le projet
2. Créer une branche feature (`git checkout -b feature/AmazingFeature`)
3. Commit les changements (`git commit -m 'Add some AmazingFeature'`)
4. Push vers la branche (`git push origin feature/AmazingFeature`)
5. Ouvrir une Pull Request

## 📄 Licence

Ce projet est sous licence MIT. Voir le fichier `LICENSE` pour plus de détails.

## 🎯 Prochaines étapes

1. **Personnaliser** : Adapter les exemples à votre domaine métier
2. **Authentification** : Configurer Laravel Sanctum
3. **Validation** : Créer des Form Requests personnalisés
4. **Documentation** : Documenter votre API
5. **Déploiement** : Configurer le déploiement
