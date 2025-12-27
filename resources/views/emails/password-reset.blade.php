<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réinitialisation de mot de passe - SUE</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            color: #111111;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            overflow: hidden;
        }
        .header {
            background-color: #000000;
            padding: 40px 20px;
            text-align: center;
        }
        .header h1 {
            color: #D4FC79; /* Electric Volt */
            margin: 0;
            font-size: 32px;
            font-weight: 900;
            font-style: italic;
            text-transform: uppercase;
            letter-spacing: -1px;
            line-height: 1;
        }
        .content {
            padding: 40px 40px;
            text-align: left;
        }
        .content h2 {
            color: #000000;
            margin-top: 0;
            font-size: 24px;
            font-weight: 800;
            text-transform: uppercase;
            font-style: italic;
            letter-spacing: -0.5px;
            margin-bottom: 20px;
        }
        .content p {
            color: #333333;
            margin: 0 0 20px;
            font-size: 16px;
            line-height: 1.6;
        }
        .button-container {
            text-align: center;
            margin: 40px 0;
        }
        .button {
            display: inline-block;
            background-color: #000000;
            color: #D4FC79 !important;
            padding: 16px 40px;
            text-decoration: none;
            font-weight: 900;
            font-size: 16px;
            text-transform: uppercase;
            font-style: italic;
            letter-spacing: 0.5px;
            border-radius: 30px;
        }
        a.button, a.button:visited, a.button:hover, a.button:active {
            color: #D4FC79 !important;
            text-decoration: none !important;
        }
        .link-fallback {
            background-color: #f8f9fa;
            padding: 15px;
            border-left: 4px solid #000000;
            margin: 30px 0;
            word-break: break-all;
            font-family: monospace;
            font-size: 12px;
            color: #555555;
        }
        .footer {
            background-color: #f4f4f4;
            padding: 30px 20px;
            text-align: center;
            color: #888888;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>SUE</h1>
        </div>

        <div class="content">
            <h2>Récupération de compte</h2>

            <p>Bonjour,</p>

            <p>Nous avons reçu une demande de réinitialisation de mot de passe pour votre compte <strong>SUE</strong>. Si vous êtes à l'origine de cette demande, cliquez sur le bouton ci-dessous pour créer un nouveau mot de passe.</p>

            <div class="button-container">
                <!-- Inline style added for maximum compatibility -->
                <a href="{{ $url }}" class="button" style="color: #D4FC79 !important; text-decoration: none;">RÉINITIALISER LE MOT DE PASSE</a>
            </div>

            <p>Si le bouton ne fonctionne pas, copiez et collez le lien suivant dans votre navigateur :</p>

            <div class="link-fallback">
                {{ $url }}
            </div>

            <p style="font-size: 14px; color: #666; margin-top: 30px;">
                <em>Ce lien expirera dans 60 minutes.</em><br>
                Si vous n'avez pas demandé cette réinitialisation, aucune action n'est requise.
            </p>
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} SUE. Tous droits réservés.</p>
        </div>
    </div>
</body>
</html>
