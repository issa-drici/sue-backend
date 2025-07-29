# 🚀 Démarrage Rapide WebSocket - Alarrache

## ⚡ Test en 2 minutes

### 1. Démarrer les serveurs
```bash
# Terminal 1 - Serveur Laravel
php artisan serve --host=0.0.0.0 --port=8000

# Terminal 2 - Serveur WebSocket
./start-websocket.sh
```

### 2. Tester avec le navigateur
Ouvrez `test-websocket.html` dans votre navigateur et testez en temps réel !

### 3. Vérifier que tout fonctionne
```bash
# Test de santé WebSocket
curl http://localhost:6001/health

# Test API Laravel
curl http://localhost:8000/api/version
```

## 📱 Intégration dans votre app

### Installation
```bash
npm install socket.io-client
```

### Configuration minimale
```javascript
import { io } from 'socket.io-client';

const socket = io('http://localhost:6001');

// Rejoindre une session
socket.emit('join-session', {
  sessionId: 'votre-session-id',
  userId: 'votre-user-id',
  user: { firstname: 'John', lastname: 'Doe' }
});

// Écouter les nouveaux commentaires
socket.on('comment.created', (data) => {
  console.log('Nouveau commentaire:', data.comment);
});
```

## 📚 Documentation complète
Consultez `docs/api/WEBSOCKET_FRONTEND_GUIDE.md` pour tous les détails !

## 🎯 Prochaines étapes
1. Tester avec `test-websocket.html`
2. Intégrer dans votre app mobile/web
3. Configurer pour la production

**Bonne intégration ! 🚀** 
