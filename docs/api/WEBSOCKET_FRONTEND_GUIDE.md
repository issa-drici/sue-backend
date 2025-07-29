# 🌐 Guide WebSocket Frontend - Alarrache

## 📋 Table des matières
- [Configuration](#configuration)
- [Connexion WebSocket](#connexion-websocket)
- [Événements disponibles](#événements-disponibles)
- [API REST pour les commentaires](#api-rest-pour-les-commentaires)
- [Exemples d'utilisation](#exemples-dutilisation)
- [Gestion des erreurs](#gestion-des-erreurs)
- [Déploiement](#déploiement)

---

## ⚙️ Configuration

### Variables d'environnement
```javascript
// .env
SOCKET_IO_URL=http://localhost:6001  // Développement
SOCKET_IO_URL=https://votre-domaine.com:6001  // Production
API_URL=http://localhost:8000/api  // Développement
API_URL=https://votre-domaine.com/api  // Production
```

### Installation des dépendances
```bash
# Expo/React Native
npm install socket.io-client

# React Web
npm install socket.io-client
```

---

## 🔌 Connexion WebSocket

### Initialisation de la connexion
```javascript
import { io } from 'socket.io-client';

class WebSocketService {
  constructor() {
    this.socket = null;
    this.isConnected = false;
  }

  connect(userToken) {
    this.socket = io(process.env.SOCKET_IO_URL, {
      transports: ['websocket', 'polling'],
      auth: {
        token: userToken
      }
    });

    // Événements de connexion
    this.socket.on('connect', () => {
      console.log('✅ Connecté au serveur WebSocket');
      this.isConnected = true;
    });

    this.socket.on('disconnect', () => {
      console.log('❌ Déconnecté du serveur WebSocket');
      this.isConnected = false;
    });

    this.socket.on('error', (error) => {
      console.error('❌ Erreur WebSocket:', error);
    });
  }

  disconnect() {
    if (this.socket) {
      this.socket.disconnect();
      this.socket = null;
      this.isConnected = false;
    }
  }
}

export default new WebSocketService();
```

---

## 🎯 Événements disponibles

### 1. Rejoindre une session sportive
```javascript
// Émettre
socket.emit('join-session', {
  sessionId: 'uuid-de-la-session',
  userId: 'uuid-utilisateur',
  user: {
    id: 'uuid-utilisateur',
    firstname: 'John',
    lastname: 'Doe',
    avatar: 'url-avatar'
  }
});

// Écouter
socket.on('online-users', (users) => {
  console.log('👥 Utilisateurs en ligne:', users);
  // users = [{ socketId, user, joinedAt }]
});
```

### 2. Indicateur de frappe
```javascript
// Émettre - Commencer à taper
socket.emit('typing', {
  sessionId: 'uuid-de-la-session',
  userId: 'uuid-utilisateur',
  isTyping: true,
  user: { /* user data */ }
});

// Émettre - Arrêter de taper
socket.emit('typing', {
  sessionId: 'uuid-de-la-session',
  userId: 'uuid-utilisateur',
  isTyping: false,
  user: { /* user data */ }
});

// Écouter
socket.on('user.typing', (data) => {
  console.log('⌨️ Utilisateur en train de taper:', data);
  // data = { userId, user, isTyping, timestamp }
});
```

### 3. Événements de présence
```javascript
// Écouter - Utilisateur en ligne
socket.on('user.online', (data) => {
  console.log('🟢 Utilisateur connecté:', data);
  // data = { userId, user, joinedAt }
});

// Écouter - Utilisateur hors ligne
socket.on('user.offline', (data) => {
  console.log('🔴 Utilisateur déconnecté:', data);
  // data = { userId, user, leftAt }
});
```

### 4. Événements de commentaires
```javascript
// Écouter - Nouveau commentaire
socket.on('comment.created', (data) => {
  console.log('💬 Nouveau commentaire:', data);
  // data = { comment }
});

// Écouter - Commentaire modifié
socket.on('comment.updated', (data) => {
  console.log('✏️ Commentaire modifié:', data);
  // data = { comment }
});

// Écouter - Commentaire supprimé
socket.on('comment.deleted', (data) => {
  console.log('🗑️ Commentaire supprimé:', data);
  // data = { commentId, deletedAt }
});
```

---

## 📡 API REST pour les commentaires

### 1. Récupérer les commentaires d'une session
```javascript
// GET /api/sport-sessions/{sessionId}/comments
const getComments = async (sessionId) => {
  const response = await fetch(`${API_URL}/sport-sessions/${sessionId}/comments`, {
    headers: {
      'Authorization': `Bearer ${userToken}`,
      'Content-Type': 'application/json'
    }
  });
  
  return response.json();
};
```

### 2. Créer un commentaire
```javascript
// POST /api/sport-sessions/{sessionId}/comments
const createComment = async (sessionId, content, mentions = []) => {
  const response = await fetch(`${API_URL}/sport-sessions/${sessionId}/comments`, {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${userToken}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      content,
      mentions
    })
  });
  
  return response.json();
};
```

### 3. Modifier un commentaire
```javascript
// PUT /api/sport-sessions/{sessionId}/comments/{commentId}
const updateComment = async (sessionId, commentId, content, mentions = []) => {
  const response = await fetch(`${API_URL}/sport-sessions/${sessionId}/comments/${commentId}`, {
    method: 'PUT',
    headers: {
      'Authorization': `Bearer ${userToken}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      content,
      mentions
    })
  });
  
  return response.json();
};
```

### 4. Supprimer un commentaire
```javascript
// DELETE /api/sport-sessions/{sessionId}/comments/{commentId}
const deleteComment = async (sessionId, commentId) => {
  const response = await fetch(`${API_URL}/sport-sessions/${sessionId}/comments/${commentId}`, {
    method: 'DELETE',
    headers: {
      'Authorization': `Bearer ${userToken}`,
      'Content-Type': 'application/json'
    }
  });
  
  return response.json();
};
```

### 5. Récupérer la présence des utilisateurs
```javascript
// GET /api/sport-sessions/{sessionId}/presence
const getPresence = async (sessionId) => {
  const response = await fetch(`${API_URL}/sport-sessions/${sessionId}/presence`, {
    headers: {
      'Authorization': `Bearer ${userToken}`,
      'Content-Type': 'application/json'
    }
  });
  
  return response.json();
};
```

---

## 💡 Exemples d'utilisation

### Hook React pour les commentaires
```javascript
import { useState, useEffect } from 'react';
import WebSocketService from './WebSocketService';

export const useComments = (sessionId, userToken) => {
  const [comments, setComments] = useState([]);
  const [onlineUsers, setOnlineUsers] = useState([]);
  const [typingUsers, setTypingUsers] = useState([]);
  const [isConnected, setIsConnected] = useState(false);

  useEffect(() => {
    // Connexion WebSocket
    WebSocketService.connect(userToken);
    const socket = WebSocketService.socket;

    // Rejoindre la session
    socket.emit('join-session', {
      sessionId,
      userId: currentUser.id,
      user: currentUser
    });

    // Écouter les événements
    socket.on('comment.created', (data) => {
      setComments(prev => [...prev, data.comment]);
    });

    socket.on('comment.updated', (data) => {
      setComments(prev => 
        prev.map(comment => 
          comment.id === data.comment.id ? data.comment : comment
        )
      );
    });

    socket.on('comment.deleted', (data) => {
      setComments(prev => 
        prev.filter(comment => comment.id !== data.commentId)
      );
    });

    socket.on('online-users', (users) => {
      setOnlineUsers(users);
    });

    socket.on('user.typing', (data) => {
      if (data.isTyping) {
        setTypingUsers(prev => [...prev, data.user]);
      } else {
        setTypingUsers(prev => 
          prev.filter(user => user.id !== data.userId)
        );
      }
    });

    socket.on('connect', () => setIsConnected(true));
    socket.on('disconnect', () => setIsConnected(false));

    // Charger les commentaires existants
    loadComments();

    return () => {
      socket.off('comment.created');
      socket.off('comment.updated');
      socket.off('comment.deleted');
      socket.off('online-users');
      socket.off('user.typing');
    };
  }, [sessionId]);

  const loadComments = async () => {
    try {
      const response = await getComments(sessionId);
      setComments(response.data);
    } catch (error) {
      console.error('Erreur chargement commentaires:', error);
    }
  };

  const sendComment = async (content, mentions = []) => {
    try {
      const response = await createComment(sessionId, content, mentions);
      // Le commentaire sera ajouté via l'événement WebSocket
      return response;
    } catch (error) {
      console.error('Erreur envoi commentaire:', error);
      throw error;
    }
  };

  const updateComment = async (commentId, content, mentions = []) => {
    try {
      const response = await updateComment(sessionId, commentId, content, mentions);
      // Le commentaire sera mis à jour via l'événement WebSocket
      return response;
    } catch (error) {
      console.error('Erreur modification commentaire:', error);
      throw error;
    }
  };

  const deleteComment = async (commentId) => {
    try {
      const response = await deleteComment(sessionId, commentId);
      // Le commentaire sera supprimé via l'événement WebSocket
      return response;
    } catch (error) {
      console.error('Erreur suppression commentaire:', error);
      throw error;
    }
  };

  const startTyping = () => {
    socket.emit('typing', {
      sessionId,
      userId: currentUser.id,
      isTyping: true,
      user: currentUser
    });
  };

  const stopTyping = () => {
    socket.emit('typing', {
      sessionId,
      userId: currentUser.id,
      isTyping: false,
      user: currentUser
    });
  };

  return {
    comments,
    onlineUsers,
    typingUsers,
    isConnected,
    sendComment,
    updateComment,
    deleteComment,
    startTyping,
    stopTyping
  };
};
```

### Composant React pour les commentaires
```javascript
import React, { useState, useRef, useEffect } from 'react';
import { useComments } from './useComments';

export const CommentsSection = ({ sessionId, currentUser }) => {
  const {
    comments,
    onlineUsers,
    typingUsers,
    isConnected,
    sendComment,
    startTyping,
    stopTyping
  } = useComments(sessionId, currentUser.token);

  const [newComment, setNewComment] = useState('');
  const [isTyping, setIsTyping] = useState(false);
  const typingTimeoutRef = useRef(null);

  const handleTyping = (e) => {
    setNewComment(e.target.value);
    
    if (!isTyping) {
      setIsTyping(true);
      startTyping();
    }

    // Reset le timeout
    if (typingTimeoutRef.current) {
      clearTimeout(typingTimeoutRef.current);
    }

    typingTimeoutRef.current = setTimeout(() => {
      setIsTyping(false);
      stopTyping();
    }, 2000);
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!newComment.trim()) return;

    try {
      await sendComment(newComment.trim());
      setNewComment('');
      stopTyping();
    } catch (error) {
      console.error('Erreur envoi:', error);
    }
  };

  return (
    <div className="comments-section">
      {/* Statut de connexion */}
      <div className="connection-status">
        {isConnected ? '🟢 Connecté' : '🔴 Déconnecté'}
        <span>👥 {onlineUsers.length} en ligne</span>
      </div>

      {/* Liste des commentaires */}
      <div className="comments-list">
        {comments.map(comment => (
          <div key={comment.id} className="comment">
            <div className="comment-header">
              <img src={comment.user.avatar} alt={comment.user.firstname} />
              <span>{comment.user.firstname} {comment.user.lastname}</span>
              <span className="timestamp">
                {new Date(comment.created_at).toLocaleTimeString()}
              </span>
            </div>
            <div className="comment-content">{comment.content}</div>
          </div>
        ))}
      </div>

      {/* Indicateurs de frappe */}
      {typingUsers.length > 0 && (
        <div className="typing-indicator">
          {typingUsers.map(user => user.firstname).join(', ')} 
          {typingUsers.length === 1 ? ' tape...' : ' tapent...'}
        </div>
      )}

      {/* Formulaire de commentaire */}
      <form onSubmit={handleSubmit} className="comment-form">
        <input
          type="text"
          value={newComment}
          onChange={handleTyping}
          placeholder="Écrire un commentaire..."
          className="comment-input"
        />
        <button type="submit" disabled={!newComment.trim()}>
          Envoyer
        </button>
      </form>
    </div>
  );
};
```

---

## ⚠️ Gestion des erreurs

### Reconnexion automatique
```javascript
class WebSocketService {
  constructor() {
    this.reconnectAttempts = 0;
    this.maxReconnectAttempts = 5;
    this.reconnectDelay = 1000;
  }

  connect(userToken) {
    this.socket = io(process.env.SOCKET_IO_URL, {
      transports: ['websocket', 'polling'],
      auth: { token: userToken },
      reconnection: true,
      reconnectionAttempts: this.maxReconnectAttempts,
      reconnectionDelay: this.reconnectDelay,
      reconnectionDelayMax: 5000
    });

    this.socket.on('connect_error', (error) => {
      console.error('❌ Erreur de connexion:', error);
      this.handleReconnection();
    });

    this.socket.on('reconnect', (attemptNumber) => {
      console.log(`✅ Reconnexion réussie (tentative ${attemptNumber})`);
      this.reconnectAttempts = 0;
    });

    this.socket.on('reconnect_failed', () => {
      console.error('❌ Échec de la reconnexion');
      // Notifier l'utilisateur
    });
  }

  handleReconnection() {
    this.reconnectAttempts++;
    if (this.reconnectAttempts < this.maxReconnectAttempts) {
      setTimeout(() => {
        console.log(`🔄 Tentative de reconnexion ${this.reconnectAttempts}/${this.maxReconnectAttempts}`);
      }, this.reconnectDelay * this.reconnectAttempts);
    }
  }
}
```

### Gestion des erreurs API
```javascript
const handleApiError = (error) => {
  if (error.status === 401) {
    // Token expiré - rediriger vers login
    redirectToLogin();
  } else if (error.status === 403) {
    // Pas autorisé
    showError('Vous n\'êtes pas autorisé à effectuer cette action');
  } else if (error.status === 404) {
    // Ressource non trouvée
    showError('Commentaire ou session non trouvé');
  } else if (error.status === 409) {
    // Conflit
    showError('Action impossible - conflit détecté');
  } else {
    // Erreur serveur
    showError('Erreur serveur - réessayez plus tard');
  }
};
```

---

## 🚀 Déploiement

### Variables d'environnement de production
```javascript
// Production
SOCKET_IO_URL=https://votre-domaine.com:6001
API_URL=https://votre-domaine.com/api
```

### Configuration SSL (si nécessaire)
```javascript
const socket = io(process.env.SOCKET_IO_URL, {
  transports: ['websocket', 'polling'],
  secure: true, // Pour HTTPS
  rejectUnauthorized: false // Si certificat auto-signé
});
```

### Monitoring
```javascript
// Vérifier la santé du serveur WebSocket
const checkWebSocketHealth = async () => {
  try {
    const response = await fetch(`${process.env.SOCKET_IO_URL}/health`);
    const health = await response.json();
    console.log('🏥 WebSocket Health:', health);
    return health.status === 'ok';
  } catch (error) {
    console.error('❌ WebSocket non accessible:', error);
    return false;
  }
};
```

---

## 📱 Intégration Expo/React Native

### Installation
```bash
expo install socket.io-client
```

### Configuration pour mobile
```javascript
import { io } from 'socket.io-client';
import { Platform } from 'react-native';

const socketConfig = {
  transports: ['websocket', 'polling'],
  // Configuration spécifique mobile
  forceNew: true,
  timeout: 20000,
  // Désactiver la compression sur mobile pour de meilleures performances
  forceBase64: true
};

if (Platform.OS === 'android') {
  // Configuration spécifique Android
  socketConfig.extraHeaders = {
    'User-Agent': 'Alarrache-Mobile-Android'
  };
}
```

---

## 🎯 Checklist d'intégration

- [ ] Installer `socket.io-client`
- [ ] Configurer les variables d'environnement
- [ ] Implémenter la connexion WebSocket
- [ ] Gérer les événements de commentaires
- [ ] Gérer les événements de présence
- [ ] Implémenter les indicateurs de frappe
- [ ] Gérer les erreurs et reconnexions
- [ ] Tester en développement
- [ ] Configurer pour la production
- [ ] Tester en production

---

## 📞 Support

Pour toute question ou problème :
1. Vérifiez les logs du serveur WebSocket
2. Testez la connexion avec `curl http://localhost:6001/health`
3. Vérifiez les variables d'environnement
4. Consultez la documentation Socket.IO officielle

**Bonne intégration ! 🚀** 
