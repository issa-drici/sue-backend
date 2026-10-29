<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'clicksend' => [
        'username' => env('CLICKSEND_USERNAME'),
        'key' => env('CLICKSEND_API_KEY'),
        // Optionnel : sender ID alphanumérique (ex: "SUE") ou numéro expéditeur dédié.
        'from' => env('CLICKSEND_FROM'),
    ],

    // Fournisseur SMS actif (cf. App\Services\PhoneOtpService).
    // ClickSend ci-dessus reste configuré/inchangé pour une réactivation rapide.
    // Compte de démo pour les équipes de review (App Store, Google Play) : pour ce numéro seul,
    // aucun SMS n'est envoyé et le code attendu est REVIEW_OTP_CODE. Vide = désactivé.
    'review_account' => [
        'phone' => env('REVIEW_PHONE'),
        'code' => env('REVIEW_OTP_CODE'),
    ],

    'infinireach' => [
        'api_key' => env('INFINIREACH_API_KEY'),
        'from' => env('INFINIREACH_FROM'),
    ],

];
