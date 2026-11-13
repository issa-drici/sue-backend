<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    // Identifiants UUID : comparaison en chaîne (un cast (int) ramenait tous les UUID à 0)
    return (string) $user->id === (string) $id;
});

// Chat d'une session : organisateur et invités (même ceux qui ont refusé), cf. SessionChatService
Broadcast::channel('session-chat.{sessionId}', function ($user, $sessionId) {
    return app(\App\Services\SessionChatService::class)->isMember($sessionId, $user->id);
});

// Présence en temps réel (UserOnline / UserOffline / UserTyping) : canal privé,
// réservé aux membres de la session comme le chat
Broadcast::channel('sport-session.{sessionId}', function ($user, $sessionId) {
    return app(\App\Services\SessionChatService::class)->isMember($sessionId, $user->id);
});
