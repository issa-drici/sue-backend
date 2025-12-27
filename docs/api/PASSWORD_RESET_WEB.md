# Réinitialisation de Mot de Passe - Guide pour Site Web

## Vue d'ensemble

Ce guide explique comment utiliser l'endpoint de réinitialisation de mot de passe pour un site web. Cet endpoint permet à l'utilisateur de définir un nouveau mot de passe après avoir reçu un lien de réinitialisation par email.


## Endpoint

### POST /reset-password

**Description :** Réinitialiser le mot de passe avec un token reçu par email

L'utilisateur a cliqué sur le lien dans l'email et arrive sur la page de réinitialisation du site web.

**Endpoint :** `POST /reset-password`

**Headers :**
```
Content-Type: application/json
```

**Body :**
```json
{
  "token": "abc123def456...",
  "email": "user@example.com",
  "password": "nouveauMotDePasse123",
  "password_confirmation": "nouveauMotDePasse123"
}
```

**Paramètres requis :**
- `token` : Le token reçu dans le lien de l'email (extrait de l'URL)
- `email` : L'adresse email de l'utilisateur
- `password` : Le nouveau mot de passe (minimum 8 caractères par défaut Laravel)
- `password_confirmation` : La confirmation du nouveau mot de passe (doit correspondre exactement)

**Réponse Succès (200) :**
```json
{
  "status": "passwords.reset"
}
```

**Réponse Erreur (422) - Token invalide ou expiré :**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["passwords.token"]
  }
}
```

**Réponse Erreur (422) - Mots de passe ne correspondent pas :**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "password": ["The password confirmation does not match."]
  }
}
```

**Réponse Erreur (422) - Mot de passe trop court :**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "password": ["The password must be at least 8 characters."]
  }
}
```

**Ce qui se passe :**
- Le serveur vérifie que le token est valide et non expiré
- Le serveur vérifie que l'email correspond au token
- Le nouveau mot de passe est hashé et sauvegardé
- Le token de réinitialisation est invalidé (ne peut plus être réutilisé)

**À faire côté site web :**
- Extraire le token et l'email depuis l'URL du lien reçu par email
- Afficher un formulaire pour saisir le nouveau mot de passe (2 champs : password et password_confirmation)
- Valider que les deux champs de mot de passe correspondent avant l'envoi
- Envoyer la requête avec tous les paramètres requis (token, email, password, password_confirmation)
- Afficher un message de succès et rediriger vers la page de connexion

---

## Format du lien de réinitialisation

Le lien envoyé par email suit ce format (configuré côté serveur) :
```
{FRONTEND_URL}/password-reset/{token}?email={email}
```

**Exemple :**
```
https://alarrache.com/password-reset/abc123def456ghi789?email=user@example.com
```

**Extraction des paramètres depuis l'URL :**
- Le `token` se trouve dans le chemin de l'URL : `abc123def456ghi789`
- L'`email` se trouve dans les paramètres de requête (query string) : `user@example.com`

**Configuration côté serveur :**
Le format de l'URL est défini dans `AppServiceProvider` et utilise la variable d'environnement `FRONTEND_URL` du serveur.

---

## Cas d'usage

### Scénario 1 : Réinitialisation réussie

1. L'utilisateur arrive sur la page `/password-reset/{token}?email={email}` après avoir cliqué sur le lien dans l'email
2. Le site extrait le token et l'email de l'URL
3. Le site affiche un formulaire pour saisir le nouveau mot de passe (2 champs)
4. L'utilisateur saisit son nouveau mot de passe (2 fois)
5. Le site appelle `POST /reset-password` avec tous les paramètres
6. Le serveur retourne un succès : `{"status": "passwords.reset"}`
7. Le site affiche "Mot de passe réinitialisé avec succès"
8. L'utilisateur est redirigé vers la page de connexion

### Scénario 2 : Token expiré

1. L'utilisateur clique sur un ancien lien de réinitialisation
2. Le site extrait le token et l'email de l'URL
3. L'utilisateur saisit son nouveau mot de passe
4. Le site appelle `POST /reset-password`
5. Le serveur retourne une erreur : `{"errors": {"email": ["passwords.token"]}}`
6. Le site affiche : "Ce lien a expiré. Veuillez demander un nouveau lien"
7. Le site propose un bouton pour rediriger vers la page "Mot de passe oublié"

### Scénario 3 : Page de réinitialisation accessible directement sans token

1. L'utilisateur accède directement à `/password-reset` sans token dans l'URL
2. Le site détecte l'absence de token dans l'URL
3. Le site affiche un message : "Lien de réinitialisation invalide"
4. Le site propose de rediriger vers la page "Mot de passe oublié"

---

## Validation des données

### Token
- Doit être présent dans la requête
- Doit être valide et non expiré
- Doit correspondre à l'email fourni
- Ne peut être utilisé qu'une seule fois

### Email
- Format email valide requis
- Doit correspondre au token fourni

### Mot de passe
- Minimum 8 caractères (par défaut Laravel)
- Doit correspondre exactement à `password_confirmation`
- Les deux champs (`password` et `password_confirmation`) sont requis

---

## Gestion des erreurs

### Erreurs courantes

| Code | Message | Signification |
|------|---------|---------------|
| `passwords.reset` | Succès | Mot de passe réinitialisé avec succès |
| `passwords.token` | Erreur | Token invalide ou expiré |
| `passwords.user` | Erreur | Email non trouvé ou ne correspond pas au token |

### Bonnes pratiques

- Vérifier la présence du token et de l'email dans l'URL avant d'afficher le formulaire
- Valider les mots de passe côté client avant l'envoi (longueur minimale, correspondance)
- Gérer les cas d'expiration de token avec un message clair et proposer de demander un nouveau lien
- Gérer les cas où l'utilisateur accède directement à la page sans token ou email
- Afficher un message de succès clair et rediriger vers la page de connexion

---

## Structure de la page recommandée

### Page de réinitialisation du mot de passe
- **Route :** `/password-reset/:token?email=:email` ou `/password-reset?token=:token&email=:email`
- **Contenu :** Formulaire avec 2 champs mot de passe (password et password_confirmation)
- **Action :** Appel à `POST /reset-password` avec token, email, password et password_confirmation
- **Message après succès :** "Mot de passe réinitialisé avec succès" + redirection vers la page de connexion
- **Gestion d'erreur :** Afficher les messages d'erreur appropriés si le token est invalide ou expiré

---

## Sécurité

- Les tokens de réinitialisation expirent après un certain temps (configuré côté serveur)
- Chaque token ne peut être utilisé qu'une seule fois (invalidé après utilisation)
- Les mots de passe sont hashés avant stockage en base de données
- Aucune information sensible n'est retournée dans les réponses d'erreur
- Le format de l'URL de réinitialisation est configuré côté serveur

---

## Configuration requise

### Variable d'environnement serveur

Le serveur doit avoir la variable `FRONTEND_URL` configurée pour générer les liens corrects dans les emails.

**Exemple :**
```
FRONTEND_URL=https://alarrache.com
```

Cette URL sera utilisée pour construire le lien de réinitialisation envoyé par email.

---

## Notes importantes

1. **Expiration** : Les tokens ont une durée de vie limitée (configurée côté serveur)
2. **Réutilisation** : Un token ne peut être utilisé qu'une seule fois - après utilisation, il est invalidé
3. **Format URL** : Le format du lien est configuré côté serveur dans `AppServiceProvider` et utilise la variable d'environnement `FRONTEND_URL`
4. **Routing** : Le site web doit avoir une route qui correspond au format `/password-reset/{token}` ou `/password-reset?token={token}&email={email}` pour capturer le token et l'email depuis l'URL
5. **Extraction des paramètres** : Le token et l'email doivent être extraits de l'URL et inclus dans la requête POST
6. **Validation** : Toujours valider que le token et l'email sont présents dans l'URL avant d'afficher le formulaire

