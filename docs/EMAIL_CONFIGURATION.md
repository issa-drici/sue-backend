# Configuration des Emails

Ce guide explique comment configurer l'envoi d'emails dans l'application Laravel, ainsi que comment personnaliser les templates d'email.

## Variables d'environnement requises

### Variables obligatoires

Ajoutez ces variables dans votre fichier `.env` :

```env
# Configuration du mailer (choisir un : smtp, mailgun, ses, postmark, resend, log)
MAIL_MAILER=smtp

# Adresse email expéditrice
MAIL_FROM_ADDRESS=noreply@alarrache.com
MAIL_FROM_NAME="Alarrache"

# URL du frontend (pour les liens généraux, ex: vérification d'email)
FRONTEND_URL=https://alarrache.com

# URL spécifique pour la réinitialisation de mot de passe (obligatoire)
# Utile si votre page de réinitialisation est sur un sous-domaine ou chemin différent
PASSWORD_RESET_URL=https://auth.alarrache.com
```

### Configuration SMTP (recommandé pour le développement)

Si vous utilisez SMTP, ajoutez ces variables :

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=votre_username
MAIL_PASSWORD=votre_password
MAIL_ENCRYPTION=tls
```

**Exemples de services SMTP :**
- **Mailtrap** (développement/test) : `smtp.mailtrap.io:2525`
- **Gmail** : `smtp.gmail.com:587` (nécessite un mot de passe d'application)
- **SendGrid** : `smtp.sendgrid.net:587`
- **Mailgun** : `smtp.mailgun.org:587`

### Configuration Mailgun

```env
MAIL_MAILER=mailgun
MAILGUN_DOMAIN=votre-domaine.mailgun.org
MAILGUN_SECRET=votre_secret_key
MAILGUN_ENDPOINT=api.mailgun.net
```

### Configuration AWS SES

```env
MAIL_MAILER=ses
AWS_ACCESS_KEY_ID=votre_access_key
AWS_SECRET_ACCESS_KEY=votre_secret_key
AWS_DEFAULT_REGION=us-east-1
```

### Configuration Postmark

```env
MAIL_MAILER=postmark
POSTMARK_TOKEN=votre_token
```

### Configuration Resend

```env
MAIL_MAILER=resend
RESEND_KEY=votre_api_key
```

### Mode développement (log uniquement)

Pour le développement local, vous pouvez utiliser le mode "log" qui écrit les emails dans les logs au lieu de les envoyer :

```env
MAIL_MAILER=log
```

Les emails seront écrits dans `storage/logs/laravel.log`.

### Configuration des URLs

L'application utilise deux variables d'environnement pour les URLs :

- **`FRONTEND_URL`** : URL principale de votre application frontend (utilisée pour les liens généraux, ex: vérification d'email)
- **`PASSWORD_RESET_URL`** : URL spécifique pour la page de réinitialisation de mot de passe (obligatoire)

**Cas d'usage pour `PASSWORD_RESET_URL` :**
- Si votre page de réinitialisation est sur un sous-domaine différent : `https://auth.alarrache.com`
- Si elle est sur un chemin différent : `https://alarrache.com/auth`
- Si elle est sur un domaine complètement différent : `https://reset.alarrache.com`

**Exemple de configuration :**
```env
# URL principale du frontend
FRONTEND_URL=https://alarrache.com

# URL spécifique pour la réinitialisation (obligatoire)
PASSWORD_RESET_URL=https://auth.alarrache.com
```

## Types d'emails envoyés

L'application envoie actuellement les emails suivants :

1. **Réinitialisation de mot de passe** (`/forgot-password`)
   - Envoyé quand un utilisateur demande une réinitialisation
   - Contient un lien vers le frontend pour réinitialiser le mot de passe

2. **Vérification d'email** (`/email/verification-notification`)
   - Envoyé pour vérifier l'adresse email d'un nouvel utilisateur
   - Contient un lien de vérification

## Personnalisation des templates d'email

### Méthode 1 : Publier et modifier les templates Laravel

Laravel utilise des templates par défaut pour les emails. Pour les personnaliser :

1. **Publier les templates** :

```bash
php artisan vendor:publish --tag=laravel-notifications
```

Cela créera les templates dans `resources/views/vendor/notifications/`.

2. **Personnaliser le template de réinitialisation de mot de passe** :

Créez le fichier `resources/views/vendor/notifications/email.blade.php` :

```blade
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Notification' }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #4CAF50;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            background-color: #f9f9f9;
            padding: 30px;
            border: 1px solid #ddd;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            padding: 20px;
            color: #666;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Alarrache</h1>
    </div>
    <div class="content">
        {{ $slot }}
    </div>
    <div class="footer">
        <p>&copy; {{ date('Y') }} Alarrache. Tous droits réservés.</p>
    </div>
</body>
</html>
```

### Méthode 2 : Créer des Mailable personnalisés

Pour un contrôle total, créez des classes Mailable personnalisées :

1. **Créer une classe Mailable** :

```bash
php artisan make:mail PasswordResetMail
```

2. **Personnaliser la classe** dans `app/Mail/PasswordResetMail.php` :

```php
<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public $token;
    public $email;

    public function __construct($token, $email)
    {
        $this->token = $token;
        $this->email = $email;
    }

    public function build()
    {
        $url = config('app.password_reset_url') . "/password-reset/{$this->token}?email={$this->email}";
        
        return $this->subject('Réinitialisation de votre mot de passe')
                    ->view('emails.password-reset', [
                        'url' => $url,
                        'email' => $this->email
                    ]);
    }
}
```

3. **Créer le template** `resources/views/emails/password-reset.blade.php` :

```blade
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Réinitialisation de mot de passe</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #4CAF50;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            background-color: #f9f9f9;
            padding: 30px;
            border: 1px solid #ddd;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            padding: 20px;
            color: #666;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Alarrache</h1>
    </div>
    <div class="content">
        <h2>Réinitialisation de mot de passe</h2>
        <p>Bonjour,</p>
        <p>Vous avez demandé à réinitialiser votre mot de passe. Cliquez sur le bouton ci-dessous pour continuer :</p>
        <a href="{{ $url }}" class="button">Réinitialiser mon mot de passe</a>
        <p>Si le bouton ne fonctionne pas, copiez et collez ce lien dans votre navigateur :</p>
        <p style="word-break: break-all; color: #666;">{{ $url }}</p>
        <p>Ce lien expirera dans 60 minutes.</p>
        <p>Si vous n'avez pas demandé cette réinitialisation, ignorez cet email.</p>
    </div>
    <div class="footer">
        <p>&copy; {{ date('Y') }} Alarrache. Tous droits réservés.</p>
    </div>
</body>
</html>
```

4. **Utiliser le Mailable personnalisé** :

Modifiez `app/Http/Controllers/Auth/PasswordResetLinkController.php` pour utiliser votre Mailable personnalisé (optionnel, car Laravel gère déjà cela automatiquement).

### Méthode 3 : Personnaliser via AppServiceProvider

Vous pouvez également personnaliser les notifications directement dans `AppServiceProvider` :

```php
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

public function boot(): void
{
    ResetPassword::toMailUsing(function ($notifiable, $token) {
        $url = config('app.password_reset_url') . "/password-reset/$token?email={$notifiable->getEmailForPasswordReset()}";
        
        return (new MailMessage)
            ->subject('Réinitialisation de votre mot de passe')
            ->greeting('Bonjour !')
            ->line('Vous recevez cet email car nous avons reçu une demande de réinitialisation de mot de passe pour votre compte.')
            ->action('Réinitialiser le mot de passe', $url)
            ->line('Ce lien expirera dans 60 minutes.')
            ->line('Si vous n\'avez pas demandé cette réinitialisation, ignorez cet email.');
    });
}
```

## Test de l'envoi d'emails

### Test en mode développement

1. Configurez `MAIL_MAILER=log` dans votre `.env`
2. Faites une demande de réinitialisation de mot de passe
3. Vérifiez les logs dans `storage/logs/laravel.log`

### Test avec Mailtrap

1. Créez un compte sur [Mailtrap.io](https://mailtrap.io)
2. Configurez les variables SMTP dans votre `.env`
3. Les emails apparaîtront dans votre boîte Mailtrap

### Test en production

Utilisez un service comme Mailgun, SendGrid, ou AWS SES pour l'environnement de production.

## Vérification de la configuration

Pour vérifier que votre configuration est correcte, vous pouvez créer une route de test :

```php
Route::get('/test-email', function () {
    try {
        Mail::raw('Test email', function ($message) {
            $message->to('test@example.com')
                    ->subject('Test Email');
        });
        return 'Email envoyé avec succès !';
    } catch (\Exception $e) {
        return 'Erreur : ' . $e->getMessage();
    }
});
```

## Résolution des problèmes

### Les emails ne sont pas envoyés

1. Vérifiez que `MAIL_MAILER` est correctement configuré
2. Vérifiez les logs dans `storage/logs/laravel.log`
3. Vérifiez que les variables d'environnement sont correctement définies
4. Exécutez `php artisan config:clear` après avoir modifié le `.env`

### Les liens dans les emails sont incorrects

1. Vérifiez que `PASSWORD_RESET_URL` est correctement défini dans votre `.env`
2. Vérifiez que `config('app.password_reset_url')` retourne la bonne valeur
3. Exécutez `php artisan config:clear`

### Erreurs SMTP

- Vérifiez que le port et l'encryption sont corrects
- Pour Gmail, utilisez un "mot de passe d'application" au lieu du mot de passe normal
- Vérifiez que votre serveur peut se connecter au serveur SMTP (firewall)

## Bonnes pratiques

1. **Environnement de développement** : Utilisez `log` ou Mailtrap
2. **Environnement de production** : Utilisez un service professionnel (Mailgun, SendGrid, AWS SES)
3. **Sécurité** : Ne commitez jamais vos credentials dans le dépôt Git
4. **Templates** : Gardez les templates simples et responsives
5. **Tests** : Testez toujours les emails avant de déployer en production

