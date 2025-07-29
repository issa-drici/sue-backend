#!/bin/bash

# 🚀 Script de Configuration pour Nouveau Projet Laravel
# Usage: ./setup-new-project.sh "Nom du Projet" "Description du Projet"

set -e

PROJECT_NAME=${1:-"Mon Projet"}
PROJECT_DESCRIPTION=${2:-"Description de mon projet"}
PROJECT_SLUG=$(echo "$PROJECT_NAME" | tr '[:upper:]' '[:lower:]' | sed 's/[^a-z0-9]/-/g' | sed 's/-\+/-/g' | sed 's/^-\|-$//g' | sed 's/-\{3,\}/-/g')

echo "🚀 Configuration du projet: $PROJECT_NAME"
echo "📝 Description: $PROJECT_DESCRIPTION"
echo "🔗 Slug: $PROJECT_SLUG"
echo ""

# 1. Vérifier que .env existe
if [ ! -f .env ]; then
    echo "❌ Fichier .env non trouvé. Copiez .env.example vers .env d'abord."
    exit 1
fi

# 2. Générer la clé d'application
echo "🔑 Génération de la clé d'application..."
php artisan key:generate

# 3. Mettre à jour composer.json
echo "📦 Mise à jour de composer.json..."
sed -i.bak "s|\"name\": \"laravel/laravel\"|\"name\": \"$PROJECT_SLUG/$PROJECT_SLUG\"|" composer.json
sed -i.bak "s|\"description\": \"The skeleton application for the Laravel framework.\"|\"description\": \"$PROJECT_DESCRIPTION\"|" composer.json

# 4. Mettre à jour .env
echo "⚙️  Mise à jour du fichier .env..."
sed -i.bak "s|APP_NAME=Laravel|APP_NAME=\"$PROJECT_NAME\"|" .env
sed -i.bak "s|APP_TIMEZONE=UTC|APP_TIMEZONE=Europe/Paris|" .env
sed -i.bak "s|APP_LOCALE=en|APP_LOCALE=fr|" .env
sed -i.bak "s|APP_FALLBACK_LOCALE=en|APP_FALLBACK_LOCALE=fr|" .env
sed -i.bak "s|APP_FAKER_LOCALE=en_US|APP_FAKER_LOCALE=fr_FR|" .env

# 5. Nettoyer les fichiers de backup
rm -f composer.json.bak .env.bak

# 6. Installer les dépendances
echo "📚 Installation des dépendances..."
composer install

# 7. Nettoyer le cache
echo "🧹 Nettoyage du cache..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# 8. Vérifier la configuration
echo "✅ Vérification de la configuration..."
php artisan config:cache

echo ""
echo "🎉 Configuration terminée !"
echo ""
echo "📋 Prochaines étapes :"
echo "1. Configurer votre base de données dans .env"
echo "2. Exécuter : php artisan migrate"
echo "3. Tester : php artisan test"
echo "4. Démarrer : php artisan serve"
echo ""
echo "🔧 Variables importantes à configurer dans .env :"
echo "   - DB_DATABASE=votre_base_de_donnees"
echo "   - DB_USERNAME=votre_username"
echo "   - DB_PASSWORD=votre_password"
echo "   - MAIL_FROM_ADDRESS=votre@email.com"
echo ""
echo "📚 Consultez MIGRATION_GUIDE.md pour plus de détails"
