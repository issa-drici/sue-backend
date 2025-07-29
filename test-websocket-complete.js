const io = require('socket.io-client');

console.log('🧪 Test WebSocket Complet - Alarrache Backend');
console.log('=============================================');

// Configuration
const SOCKET_URL = 'http://localhost:6001';
const API_URL = 'http://localhost:8000/api';
const AUTH_TOKEN = '60|YLFpXFykmVYIAmyuFC2kcmwltiLlQr5FszrLgtCl48724458';
const SESSION_ID = 'a71a86f2-0137-499b-ab5f-c396bce6ac09';

// Variables de test
let testResults = {
    connection: false,
    authentication: false,
    joinSession: false,
    commentCreated: false,
    commentUpdated: false,
    commentDeleted: false
};

let createdCommentId = null;

// Connexion WebSocket
const socket = io(SOCKET_URL, {
    transports: ['websocket'],
    auth: {
        token: AUTH_TOKEN
    }
});

// Test 1: Connexion
socket.on('connect', () => {
    console.log('✅ Connexion WebSocket réussie');
    testResults.connection = true;
    socket.emit('authenticate', { token: AUTH_TOKEN });
});

// Test 2: Authentification
socket.on('authenticated', () => {
    console.log('✅ Authentification réussie');
    testResults.authentication = true;
    socket.emit('join-session', { sessionId: SESSION_ID });
});

// Test 3: Rejoindre session
socket.on('session-joined', (data) => {
    console.log('✅ Session rejointe:', data.sessionId);
    testResults.joinSession = true;

    // Démarrer les tests automatiques
    setTimeout(() => testCreateComment(), 1000);
});

// Test 4: Événement comment.created
socket.on('comment.created', (data) => {
    console.log('✅ Événement comment.created reçu !');
    console.log('📝 Commentaire:', data.comment.content);
    console.log('👤 Utilisateur:', data.comment.user.firstname, data.comment.user.lastname);
    testResults.commentCreated = true;

    // Test suivant après 2 secondes
    setTimeout(() => testUpdateComment(data.comment.id), 2000);
});

// Test 5: Événement comment.updated
socket.on('comment.updated', (data) => {
    console.log('✅ Événement comment.updated reçu !');
    console.log('📝 Commentaire modifié:', data.comment.content);
    testResults.commentUpdated = true;

    // Test suivant après 2 secondes
    setTimeout(() => testDeleteComment(data.comment.id), 2000);
});

// Test 6: Événement comment.deleted
socket.on('comment.deleted', (data) => {
    console.log('✅ Événement comment.deleted reçu !');
    console.log('🆔 Commentaire supprimé:', data.commentId);
    testResults.commentDeleted = true;

    // Fin des tests
    setTimeout(() => {
        printResults();
        process.exit(0);
    }, 1000);
});

// Gestion des erreurs
socket.on('connect_error', (error) => {
    console.error('❌ Erreur de connexion:', error.message);
    process.exit(1);
});

socket.on('disconnect', (reason) => {
    console.log('🔌 Déconnexion:', reason);
});

// Fonctions de test API
async function testCreateComment() {
    try {
        console.log('\n📝 Test 1: Création de commentaire...');

        const response = await fetch(`${API_URL}/sessions/${SESSION_ID}/comments`, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${AUTH_TOKEN}`
            },
            body: JSON.stringify({
                content: `Test WebSocket automatique ${new Date().toLocaleTimeString()}`
            })
        });

        const data = await response.json();

        if (data.success) {
            console.log('✅ Commentaire créé via API:', data.data.id);
            createdCommentId = data.data.id;
        } else {
            console.error('❌ Erreur création:', data);
        }
    } catch (error) {
        console.error('❌ Erreur API création:', error.message);
    }
}

async function testUpdateComment(commentId) {
    try {
        console.log('\n📝 Test 2: Modification de commentaire...');

        const response = await fetch(`${API_URL}/sessions/${SESSION_ID}/comments/${commentId}`, {
            method: 'PUT',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${AUTH_TOKEN}`
            },
            body: JSON.stringify({
                content: `Commentaire modifié automatiquement ${new Date().toLocaleTimeString()}`
            })
        });

        const data = await response.json();

        if (data.success) {
            console.log('✅ Commentaire modifié via API');
        } else {
            console.error('❌ Erreur modification:', data);
        }
    } catch (error) {
        console.error('❌ Erreur API modification:', error.message);
    }
}

async function testDeleteComment(commentId) {
    try {
        console.log('\n🗑️ Test 3: Suppression de commentaire...');

        const response = await fetch(`${API_URL}/sessions/${SESSION_ID}/comments/${commentId}`, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'Authorization': `Bearer ${AUTH_TOKEN}`
            }
        });

        const data = await response.json();

        if (data.success) {
            console.log('✅ Commentaire supprimé via API');
        } else {
            console.error('❌ Erreur suppression:', data);
        }
    } catch (error) {
        console.error('❌ Erreur API suppression:', error.message);
    }
}

// Affichage des résultats
function printResults() {
    console.log('\n📊 RÉSULTATS DES TESTS');
    console.log('======================');

    const tests = [
        { name: 'Connexion WebSocket', result: testResults.connection },
        { name: 'Authentification', result: testResults.authentication },
        { name: 'Rejoindre session', result: testResults.joinSession },
        { name: 'Événement comment.created', result: testResults.commentCreated },
        { name: 'Événement comment.updated', result: testResults.commentUpdated },
        { name: 'Événement comment.deleted', result: testResults.commentDeleted }
    ];

    tests.forEach(test => {
        const status = test.result ? '✅' : '❌';
        console.log(`${status} ${test.name}`);
    });

    const successCount = tests.filter(t => t.result).length;
    const totalCount = tests.length;

    console.log(`\n🎯 Score: ${successCount}/${totalCount} tests réussis`);

    if (successCount === totalCount) {
        console.log('🎉 TOUS LES TESTS SONT RÉUSSIS !');
        console.log('🚀 Les événements WebSocket fonctionnent parfaitement !');
    } else {
        console.log('⚠️ Certains tests ont échoué. Vérifiez la configuration.');
    }
}

// Timeout de sécurité
setTimeout(() => {
    console.log('\n⏰ Timeout atteint. Arrêt des tests.');
    printResults();
    process.exit(1);
}, 30000);

console.log('🚀 Démarrage des tests automatiques...');
