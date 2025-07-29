<?php

// Script pour créer une relation d'amitié pour tester la suppression
echo "=== CRÉATION D'UNE RELATION D'AMITIÉ POUR TEST ===\n\n";

// Utiliser artisan tinker pour créer l'amitié
$createFriendshipCommand = "php artisan tinker --execute=\"echo 'Création d\\'une relation d\\'amitié...' . PHP_EOL; \$user1 = \\App\\Models\\UserModel::where('email', 'driciissa76@gmail.com')->first(); \$user2 = \\App\\Models\\UserModel::where('email', 'gued.as76@hotmail.com')->first(); if (\$user1 && \$user2) { echo 'Utilisateur 1: ' . \$user1->firstname . ' ' . \$user1->lastname . ' (' . \$user1->email . ')' . PHP_EOL; echo 'Utilisateur 2: ' . \$user2->firstname . ' ' . \$user2->lastname . ' (' . \$user2->email . ')' . PHP_EOL; \$friend1 = \\App\\Models\\FriendModel::create(['id' => \\Illuminate\\Support\\Str::uuid(), 'user_id' => \$user1->id, 'friend_id' => \$user2->id]); \$friend2 = \\App\\Models\\FriendModel::create(['id' => \\Illuminate\\Support\\Str::uuid(), 'user_id' => \$user2->id, 'friend_id' => \$user1->id]); if (\$friend1 && \$friend2) { echo 'Relation d\\'amitié créée avec succès!' . PHP_EOL; } else { echo 'Erreur lors de la création de l\\'amitié' . PHP_EOL; } } else { echo 'Utilisateurs non trouvés' . PHP_EOL; } echo 'Vérification:' . PHP_EOL; \$friends = \\App\\Models\\FriendModel::with('friend')->where('user_id', \$user1->id)->get(); foreach(\$friends as \$friend) { echo '  - ' . \$friend->friend->firstname . ' ' . \$friend->friend->lastname . ' (' . \$friend->friend->email . ')' . PHP_EOL; } echo 'Total amis: ' . \$friends->count() . PHP_EOL;\"";
system($createFriendshipCommand);

echo "\n=== FIN DU SCRIPT ===\n";
