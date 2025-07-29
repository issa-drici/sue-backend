# 🚀 Guide de Migration - Adapter la Codebase

## 📋 **Modifications OBLIGATOIRES**

### 1. **Configuration de Base**

#### **composer.json**
```json
{
    "name": "votre-nom/votre-projet",
    "description": "Description de votre projet",
    "keywords": ["votre", "mots", "cles"],
    "license": "MIT"
}
```

#### **config/app.php**
```php
'name' => env('APP_NAME', 'Votre Projet'),
'url' => env('APP_URL', 'http://localhost'),
'timezone' => env('APP_TIMEZONE', 'Europe/Paris'),
'locale' => env('APP_LOCALE', 'fr'),
'fallback_locale' => env('APP_FALLBACK_LOCALE', 'fr'),
'faker_locale' => env('APP_FAKER_LOCALE', 'fr_FR'),
```

### 2. **Variables d'Environnement (.env)**

```bash
# Application
APP_NAME="Votre Projet"
APP_ENV=local
APP_KEY=base64:VOTRE_CLE_GENEREE
APP_DEBUG=true
APP_URL=http://localhost:8000
APP_TIMEZONE=Europe/Paris
APP_LOCALE=fr
APP_FALLBACK_LOCALE=fr
APP_FAKER_LOCALE=fr_FR

# Base de données
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=votre_projet_db
DB_USERNAME=votre_username
DB_PASSWORD=votre_password

# Cache et Session
CACHE_DRIVER=file
SESSION_DRIVER=file
SESSION_LIFETIME=120

# Queue
QUEUE_CONNECTION=sync

# Mail
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@votreprojet.com"
MAIL_FROM_NAME="${APP_NAME}"

# AWS S3 (si utilisé)
AWS_ACCESS_KEY_ID=votre_access_key
AWS_SECRET_ACCESS_KEY=votre_secret_key
AWS_DEFAULT_REGION=eu-west-3
AWS_BUCKET=votre-bucket
AWS_USE_PATH_STYLE_ENDPOINT=false

# Sanctum
SANCTUM_STATEFUL_DOMAINS=localhost:3000
SESSION_DOMAIN=localhost
```

### 3. **Génération de la Clé d'Application**

```bash
php artisan key:generate
```

### 4. **Base de Données**

```bash
# Créer la base de données
mysql -u root -p -e "CREATE DATABASE votre_projet_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Exécuter les migrations
php artisan migrate

# Optionnel : Seeders
php artisan db:seed
```

## 🔄 **Modifications RECOMMANDÉES**

### 1. **README.md**
- Changer le titre et la description
- Adapter les exemples à votre domaine
- Mettre à jour les URLs et noms de projet

### 2. **Namespace et Organisation**
- Garder la structure Clean Architecture
- Adapter les exemples à votre domaine métier
- Créer vos propres entités, use cases, repositories

### 3. **Tests**
- Adapter les tests d'exemple à votre domaine
- Créer des tests pour vos nouvelles fonctionnalités

## 🗑️ **Éléments à SUPPRIMER/ADAPTER**

### 1. **Exemples**
Les fichiers d'exemple sont là pour vous guider. Vous pouvez :
- **Les garder** comme référence
- **Les supprimer** une fois que vous avez créé vos propres modules
- **Les adapter** à votre domaine métier

### 2. **Fonctionnalités Spécifiques**
- **Support Requests** : Adapter ou supprimer selon vos besoins
- **Version Check** : Adapter les versions à votre app
- **File Management** : Adapter selon vos besoins de fichiers

### 3. **Configuration AWS S3**
Si vous n'utilisez pas AWS S3 :
```bash
# Supprimer le package
composer remove league/flysystem-aws-s3-v3

# Supprimer le service
rm app/Services/S3Service.php

# Retirer les variables AWS du .env
```

## 🎯 **Étapes de Migration**

### **Étape 1 : Configuration de Base**
```bash
# 1. Copier .env.example vers .env
cp .env.example .env

# 2. Modifier .env avec vos valeurs
nano .env

# 3. Générer la clé d'application
php artisan key:generate

# 4. Installer les dépendances
composer install
```

### **Étape 2 : Base de Données**
```bash
# 1. Créer la base de données
mysql -u root -p -e "CREATE DATABASE votre_projet_db;"

# 2. Exécuter les migrations
php artisan migrate

# 3. Vérifier que tout fonctionne
php artisan test
```

### **Étape 3 : Personnalisation**
```bash
# 1. Modifier composer.json
# 2. Adapter config/app.php
# 3. Mettre à jour README.md
# 4. Créer vos premiers modules
```

### **Étape 4 : Tests**
```bash
# 1. Tester l'API
curl http://localhost:8000/api/health

# 2. Exécuter les tests
php artisan test

# 3. Tester l'authentification
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{"name":"Test","email":"test@test.com","password":"password"}'
```

## 🔧 **Commandes Utiles**

```bash
# Vérifier la configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Nettoyer le cache
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# Optimiser pour la production
php artisan optimize

# Vérifier les routes
php artisan route:list

# Tester l'API
php artisan serve
curl http://localhost:8000/api/health
```

## ⚠️ **Points d'Attention**

### 1. **Sécurité**
- ✅ Changer `APP_KEY` (généré automatiquement)
- ✅ Configurer `APP_DEBUG=false` en production
- ✅ Utiliser des mots de passe forts pour la DB
- ✅ Configurer HTTPS en production

### 2. **Performance**
- ✅ Configurer le cache Redis en production
- ✅ Optimiser les requêtes de base de données
- ✅ Utiliser la compression gzip

### 3. **Monitoring**
- ✅ Configurer les logs
- ✅ Ajouter des métriques
- ✅ Surveiller les erreurs

## 🎉 **Validation Finale**

Après migration, vérifiez que :

- ✅ L'application démarre sans erreur
- ✅ Les migrations s'exécutent correctement
- ✅ Les tests passent
- ✅ L'API répond correctement
- ✅ L'authentification fonctionne
- ✅ Les fichiers d'exemple sont adaptés ou supprimés

## 📚 **Ressources**

- [Documentation Laravel](https://laravel.com/docs)
- [Laravel Sanctum](https://laravel.com/docs/sanctum)
- [Clean Architecture](https://blog.cleancoder.com/uncle-bob/2012/08/13/the-clean-architecture.html)
- [Laravel Testing](https://laravel.com/docs/testing)

---

**🎯 Votre codebase est maintenant prête pour le développement !** 
