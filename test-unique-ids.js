const io = require('socket.io-client');

console.log('🧪 Test IDs Uniques - Commentaires Alarrache Backend');
console.log('====================================================');

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
    comment1Created: false,
    comment2Created: false,
    comment3Created: false,
    uniqueIds: true
};

let createdCommentIds = [];

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

    // Démarrer les tests de création
    setTimeout(() => testCreateComments(), 1000);
});

// Événements commentaires
socket.on('comment.created', (data) => {
    const commentId = data.comment.id;
    console.log(`✅ Événement comment.created reçu ! ID: ${commentId}`);
    console.log(`📝 Contenu: ${data.comment.content}`);
    console.log(`👤 Utilisateur: ${data.comment.user.firstname} ${data.comment.user.lastname}`);

    // Vérifier l'unicité de l'ID
    if (createdCommentIds.includes(commentId)) {
        console.error(`❌ ID dupliqué détecté: ${commentId}`);
        testResults.uniqueIds = false;
    } else {
        createdCommentIds.push(commentId);
        console.log(`✅ ID unique confirmé: ${commentId}`);
    }

    // Marquer le test comme réussi
    if (data.comment.content.includes('Test 1')) {
        testResults.comment1Created = true;
    } else if (data.comment.content.includes('Test 2')) {
        testResults.comment2Created = true;
    } else if (data.comment.content.includes('Test 3')) {
        testResults.comment3Created = true;
        // Fin des tests
        setTimeout(() => {
            printResults();
            process.exit(0);
        }, 2000);
    }
});

// Gestion des erreurs
socket.on('connect_error', (error) => {
    console.error('❌ Erreur de connexion:', error.message);
    process.exit(1);
});

socket.on('disconnect', (reason) => {
    console.log('🔌 Déconnexion:', reason);
});

// Fonction de création de commentaires
async function testCreateComments() {
    console.log('\n📝 Test de création de commentaires avec IDs uniques...');

    try {
        // Créer 3 commentaires consécutifs
        for (let i = 1; i <= 3; i++) {
            console.log(`\n📝 Création du commentaire ${i}...`);

            const response = await fetch(`${API_URL}/sessions/${SESSION_ID}/comments`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${AUTH_TOKEN}`
                },
                body: JSON.stringify({
                    content: `Test ${i} - ID unique ${new Date().toLocaleTimeString()}`
                })
            });

            const data = await response.json();

            if (data.success) {
                console.log(`✅ Commentaire ${i} créé via API: ${data.data.id}`);

                // Vérifier l'unicité de l'ID
                if (createdCommentIds.includes(data.data.id)) {
                    console.error(`❌ ID dupliqué détecté dans la réponse API: ${data.data.id}`);
                    testResults.uniqueIds = false;
                } else {
                    createdCommentIds.push(data.data.id);
                    console.log(`✅ ID unique confirmé dans la réponse API: ${data.data.id}`);
                }
            } else {
                console.error(`❌ Erreur création commentaire ${i}:`, data);
            }

            // Attendre 1 seconde entre chaque création
            if (i < 3) {
                await new Promise(resolve => setTimeout(resolve, 1000));
            }
        }
    } catch (error) {
        console.error('❌ Erreur API création:', error.message);
    }
}

// Affichage des résultats
function printResults() {
    console.log('\n📊 RÉSULTATS DES TESTS - IDs UNIQUES');
    console.log('=====================================');

    const tests = [
        { name: 'Connexion WebSocket', result: testResults.connection },
        { name: 'Authentification', result: testResults.authentication },
        { name: 'Rejoindre session', result: testResults.joinSession },
        { name: 'Commentaire 1 créé', result: testResults.comment1Created },
        { name: 'Commentaire 2 créé', result: testResults.comment2Created },
        { name: 'Commentaire 3 créé', result: testResults.comment3Created },
        { name: 'IDs uniques', result: testResults.uniqueIds }
    ];

    tests.forEach(test => {
        const status = test.result ? '✅' : '❌';
        console.log(`${status} ${test.name}`);
    });

    console.log('\n🆔 IDs générés:');
    createdCommentIds.forEach((id, index) => {
        console.log(`   ${index + 1}. ${id}`);
    });

    const successCount = tests.filter(t => t.result).length;
    const totalCount = tests.length;

    console.log(`\n🎯 Score: ${successCount}/${totalCount} tests réussis`);

    if (successCount === totalCount) {
        console.log('🎉 TOUS LES TESTS SONT RÉUSSIS !');
        console.log('🚀 Les IDs uniques fonctionnent parfaitement !');
        console.log('✅ Plus de problèmes de doublons !');
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

console.log('🚀 Démarrage des tests d\'IDs uniques...');
