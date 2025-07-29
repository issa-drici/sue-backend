# Implémentation des Commentaires Système

## Vue d'ensemble

Cette fonctionnalité ajoute automatiquement des commentaires système dans la liste des commentaires d'une session lorsqu'un utilisateur accepte ou refuse une invitation.

## Fonctionnalités

### ✅ Commentaires automatiques
- **Acceptation** : `"a accepté l'invitation à cette session ✅"`
- **Refus** : `"a décliné l'invitation à cette session ❌"`

### ✅ Temps réel via WebSocket
- Événement : `comment.created`
- Canal : `sport-session.{sessionId}`
- Données : `{comment: {...}}`

## Implémentation technique

### 1. Modification du UseCase
**Fichier :** `app/UseCases/SportSession/RespondToSessionInvitationUseCase.php`

**Changements :**
- Ajout de la dépendance `SportSessionCommentRepositoryInterface`
- Ajout de la dépendance `UserRepositoryInterface`
- Ajout de la dépendance `SocketIOService`
- Nouvelle méthode `createSystemComment()`

**Code ajouté :**
```php
private function createSystemComment(SportSession $session, string $userId, string $response): void
{
    // Récupérer les informations de l'utilisateur
    $user = $this->userRepository->findById($userId);
    
    if (!$user) {
        return; // Ne pas créer de commentaire si l'utilisateur n'existe pas
    }

    $userName = $user->getFirstname() . ' ' . $user->getLastname();
    
    // Créer le message du commentaire système
    $message = $response === 'accept'
        ? "a accepté l'invitation à cette session ✅"
        : "a décliné l'invitation à cette session ❌";

    // Créer le commentaire système
    $comment = $this->commentRepository->createComment(
        sessionId: $session->getId(),
        userId: $userId,
        content: $message
    );

    // Émettre l'événement WebSocket pour le temps réel
    try {
        $this->socketService->emitLaravelEvent(
            'comment.created',
            'sport-session.' . $session->getId(),
            ['comment' => $comment->toArray()]
        );
    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error("Failed to emit WebSocket event for system comment", [
            'sessionId' => $session->getId(),
            'userId' => $userId,
            'response' => $response,
            'error' => $e->getMessage()
        ]);
    }
}
```

### 2. Intégration dans le flux
Le commentaire système est créé après :
1. ✅ Validation de la réponse
2. ✅ Vérification de la limite de participants (si acceptation)
3. ✅ Mise à jour du statut du participant
4. ✅ Création de la notification pour l'organisateur
5. **🆕 Création du commentaire système**
6. **🆕 Émission de l'événement WebSocket**
7. ✅ Retour de la session mise à jour

## Tests

### Test de base
**Fichier :** `test_system_comments.php`
- Création d'utilisateurs (organisateur + participant)
- Création de session avec invitation
- Test d'acceptation et vérification du commentaire
- Test de refus et vérification du commentaire

### Test WebSocket
**Fichier :** `test_system_comments_websocket.php`
- Vérification que les événements WebSocket sont émis
- Confirmation du canal et des données
- Test complet du flux temps réel

## Documentation

### Mise à jour de l'API
**Fichier :** `docs/api/sessions.md`
- Ajout de la section "Commentaires système"
- Documentation des messages automatiques
- Explication du déclenchement WebSocket

## Avantages

### ✅ Pour les utilisateurs
- **Visibilité** : Voir immédiatement qui a accepté/refusé
- **Temps réel** : Affichage instantané dans l'application
- **Historique** : Traçabilité des réponses dans les commentaires

### ✅ Pour les développeurs
- **Cohérence** : Utilise le même système que les commentaires normaux
- **Simplicité** : Pas de nouvelle logique d'affichage côté frontend
- **Fiabilité** : Gestion d'erreurs et logging

## Flux complet

1. **Utilisateur répond à l'invitation** (`PATCH /sessions/{id}/respond`)
2. **Backend traite la réponse**
3. **Commentaire système créé** avec le message approprié
4. **Événement WebSocket émis** sur `sport-session.{sessionId}`
5. **Frontend reçoit l'événement** et affiche le commentaire en temps réel
6. **Tous les participants voient** la réponse instantanément

## Messages système

| Action | Message |
|--------|---------|
| Acceptation | `"a accepté l'invitation à cette session ✅"` |
| Refus | `"a décliné l'invitation à cette session ❌"` |

## Événements WebSocket

```javascript
// Événement émis
{
  event: 'comment.created',
  channel: 'sport-session.{sessionId}',
  data: {
    comment: {
      id: 'uuid',
      session_id: 'session-uuid',
      user_id: 'user-uuid',
      content: 'a accepté l\'invitation à cette session ✅',
      user: {
        id: 'user-uuid',
        firstname: 'Jean',
        lastname: 'Dupont'
      },
      created_at: '2024-01-15 10:30:00'
    }
  }
}
```

## Gestion d'erreurs

- **Utilisateur inexistant** : Pas de commentaire créé
- **Erreur WebSocket** : Log de l'erreur, commentaire créé quand même
- **Erreur de création** : Exception levée, réponse d'erreur

## Compatibilité

- ✅ Compatible avec l'existant
- ✅ Pas de breaking changes
- ✅ Même format que les commentaires normaux
- ✅ Même système WebSocket 
