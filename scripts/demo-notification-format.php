<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\DateFormatterService;

echo "=== Démonstration du nouveau formatage des notifications d'invitation ===\n\n";

// Exemples de sports
$sports = ['tennis', 'golf', 'musculation', 'football', 'basketball'];
$dates = [
    ['date' => '2024-08-05', 'time' => '10:30', 'jour' => 'lundi'],
    ['date' => '2024-08-06', 'time' => '14:00', 'jour' => 'mardi'],
    ['date' => '2024-08-07', 'time' => '18:30', 'jour' => 'mercredi'],
    ['date' => '2024-08-08', 'time' => '09:15', 'jour' => 'jeudi'],
    ['date' => '2024-08-09', 'time' => '16:45', 'jour' => 'vendredi']
];

echo "📅 Formatage des dates et heures :\n";
foreach ($dates as $dateInfo) {
    $formatted = DateFormatterService::formatDateAndTime($dateInfo['date'], $dateInfo['time']);
    echo "  {$dateInfo['date']} {$dateInfo['time']} → {$formatted}\n";
}

echo "\n🏃‍♂️ Titres des notifications d'invitation :\n";
foreach ($sports as $sport) {
    $title = DateFormatterService::generateInvitationTitle($sport);
    $pushTitle = DateFormatterService::generatePushInvitationTitle($sport);
    $reinvitationTitle = DateFormatterService::generatePushReinvitationTitle($sport);

    echo "  {$sport}: {$title}\n";
    echo "  {$sport} (push): {$pushTitle}\n";
    echo "  {$sport} (réinvitation): {$reinvitationTitle}\n";
    echo "\n";
}

echo "📝 Messages complets des notifications :\n";
foreach ($sports as $sport) {
    $message = DateFormatterService::generateInvitationMessage($sport, '2024-08-05', '10:30');
    echo "  {$sport}: {$message}\n";
}

echo "\n✅ Exemples de notifications générées :\n";
echo "  Titre: Invitation Tennis\n";
echo "  Message: Vous avez été invité à une session de Tennis lundi 5 août à 10h30\n";
echo "\n  Titre: 🏃‍♂️ Invitation Football\n";
echo "  Message: Vous avez été invité à une session de Football mardi 6 août à 14h00\n";
echo "\n  Titre: 🏃‍♂️ Nouvelle invitation Golf\n";
echo "  Message: Vous avez été invité à une session de Golf mercredi 7 août à 18h30\n";

echo "\n💬 Notifications de commentaires :\n";
foreach ($sports as $sport) {
    $commentTitle = DateFormatterService::generateCommentTitle($sport);
    $pushCommentTitle = DateFormatterService::generatePushCommentTitle($sport);
    $commentMessage = DateFormatterService::generateCommentMessage('Jean Dupont', $sport, '2024-08-05', '10:30');
    $commentMessageShort = DateFormatterService::generateCommentMessageShort('Marie Martin', $sport);
    
    echo "  {$sport}:\n";
    echo "    Titre: {$commentTitle}\n";
    echo "    Titre (push): {$pushCommentTitle}\n";
    echo "    Message: {$commentMessageShort}\n";
    echo "    Message (détaillé): {$commentMessage}\n";
    echo "\n";
}

echo "\n=== Fin de la démonstration ===\n";
