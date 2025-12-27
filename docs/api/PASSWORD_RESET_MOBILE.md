# Mot de Passe Oublié - Guide pour Applications Mobile

## Vue d'ensemble

Ce guide explique comment utiliser l'endpoint de demande de réinitialisation de mot de passe dans une application mobile. Cet endpoint permet à l'utilisateur de demander un lien de réinitialisation par email.

## Endpoint

### POST /forgot-password

**Description :** Demander un lien de réinitialisation de mot de passe par email

L'utilisateur oublie son mot de passe et saisit son email dans l'application.

**Headers :**
```
Content-Type: application/json
```

**Body :**
```json
{
  "email": "user@example.com"
}
```

**Réponse Succès (200) :**
```json
{
  "status": "passwords.sent"
}
```

**Réponse Erreur (422) - Email invalide :**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email must be a valid email address."]
  }
}
```

**Réponse Erreur (422) - Email non trouvé :**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["passwords.user"]
  }
}
```

**Ce qui se passe :**
- Le serveur vérifie si l'email existe dans la base de données
- Si l'email existe, un token de réinitialisation est généré
- Un email est envoyé à l'utilisateur avec un lien contenant le token
- **Note importante :** Pour des raisons de sécurité, la réponse est identique que l'email existe ou non

**À faire côté application mobile :**
- Afficher un message de confirmation à l'utilisateur : "Si cet email existe, un lien de réinitialisation vous a été envoyé"
- Demander à l'utilisateur de vérifier sa boîte email
- L'utilisateur doit ouvrir l'email et cliquer sur le lien
- Le lien redirige vers le site web pour la réinitialisation du mot de passe

---

## Cas d'usage

### Scénario : Utilisateur oublie son mot de passe

1. L'utilisateur clique sur "Mot de passe oublié ?" dans l'application mobile
2. L'application affiche un formulaire avec un champ email
3. L'utilisateur saisit son email
4. L'application appelle `POST /forgot-password` avec l'email
5. L'application affiche : "Si cet email existe, un lien de réinitialisation vous a été envoyé. Vérifiez votre boîte email."
6. L'utilisateur ouvre l'email reçu
7. L'utilisateur clique sur le lien dans l'email
8. Le lien redirige vers le site web pour compléter la réinitialisation

---

## Validation des données

### Email
- Format email valide requis
- Le champ est obligatoire

---

## Gestion des erreurs

### Erreurs courantes

| Code | Message | Signification |
|------|---------|---------------|
| `passwords.sent` | Succès | Email envoyé (même si l'email n'existe pas) |
| `passwords.user` | Erreur | Email non trouvé dans la base |
| `passwords.throttled` | Erreur | Trop de tentatives, réessayez plus tard |

### Bonnes pratiques

- Toujours afficher un message générique après l'envoi (sécurité) : "Si cet email existe, un lien vous a été envoyé"
- Ne jamais révéler si un email existe ou non dans la base de données
- Gérer le rate limiting si l'utilisateur fait trop de tentatives
- Informer l'utilisateur qu'il doit vérifier sa boîte email (y compris les spams)

---

## Sécurité

- Le serveur limite le nombre de demandes par email (rate limiting)
- Aucune information sensible n'est retournée dans les réponses d'erreur
- Pour des raisons de sécurité, la réponse est identique que l'email existe ou non

---

## Notes importantes

1. **Confidentialité** : Ne jamais révéler si un email existe ou non dans la base de données - toujours afficher le même message de succès
2. **Rate limiting** : Trop de demandes peuvent être bloquées temporairement
3. **Lien de réinitialisation** : Le lien envoyé par email redirige vers le site web, pas vers l'application mobile
4. **Processus complet** : Après avoir utilisé cet endpoint, l'utilisateur doit compléter la réinitialisation sur le site web

