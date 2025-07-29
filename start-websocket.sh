#!/bin/bash

echo "🚀 Démarrage du serveur WebSocket Socket.IO..."

# Aller dans le dossier du serveur WebSocket
cd websocket-server

# Vérifier si les dépendances sont installées
if [ ! -d "node_modules" ]; then
    echo "📦 Installation des dépendances..."
    npm install
fi

# Démarrer le serveur
echo "🎉 Démarrage du serveur sur le port 6001..."
npm start
