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
    return (int) $user->id === (int) $id;
});

// Channels pour les sessions sportives
Broadcast::channel('sport-session.{sessionId}', function ($user, $sessionId) {
    // Pour l'instant, autoriser tous les utilisateurs authentifiés
    // TODO: Vérifier que l'utilisateur est participant de la session
    return true;
});
