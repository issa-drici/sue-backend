const io = require('socket.io-client');

console.log('🧪 Test WebSocket Simple - Alarrache Backend');
console.log('============================================');

// Configuration
const SOCKET_URL = 'http://localhost:6001';
const AUTH_TOKEN = '60|YLFpXFykmVYIAmyuFC2kcmwltiLlQr5FszrLgtCl48724458';
const SESSION_ID = 'a71a86f2-0137-499b-ab5f-c396bce6ac09';

// Connexion WebSocket
const socket = io(SOCKET_URL, {
    transports: ['websocket'],
    auth: {
        token: AUTH_TOKEN
    }
});

// Écouter les événements
socket.on('connect', () => {
    console.log('✅ Connecté au WebSocket');
    socket.emit('authenticate', { token: AUTH_TOKEN });
});

socket.on('authenticated', () => {
    console.log('✅ Authentifié');
    socket.emit('join-session', { sessionId: SESSION_ID });
});

socket.on('session-joined', (data) => {
    console.log('✅ Session rejointe:', data.sessionId);
    console.log('🎧 En attente d\'événements commentaires...');
    console.log('📝 Créez un commentaire via l\'API pour tester');
});

// Événements commentaires
socket.on('comment.created', (data) => {
    console.log('🎉 Événement comment.created reçu !');
    console.log('📝 Commentaire:', data.comment.content);
    console.log('👤 Utilisateur:', data.comment.user.firstname, data.comment.user.lastname);
});

socket.on('comment.updated', (data) => {
    console.log('🔄 Événement comment.updated reçu !');
    console.log('📝 Commentaire modifié:', data.comment.content);
});

socket.on('comment.deleted', (data) => {
    console.log('🗑️ Événement comment.deleted reçu !');
    console.log('🆔 Commentaire supprimé:', data.commentId);
});

// Gestion des erreurs
socket.on('connect_error', (error) => {
    console.error('❌ Erreur de connexion:', error.message);
});

socket.on('disconnect', (reason) => {
    console.log('🔌 Déconnecté:', reason);
});

console.log('🚀 Script de test démarré');
console.log('💡 Gardez ce script ouvert et créez des commentaires via l\'API');
console.log('📡 Les événements WebSocket apparaîtront ici en temps réel');
