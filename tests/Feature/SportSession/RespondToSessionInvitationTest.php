<?php

use App\UseCases\SportSession\RespondToSessionInvitationUseCase;
use App\Repositories\SportSession\SportSessionRepository;
use App\Repositories\Notification\NotificationRepository;
use App\Repositories\SportSessionComment\SportSessionCommentRepository;
use App\Repositories\User\UserRepository;
use App\Repositories\PushToken\PushTokenRepository;
use App\Services\ExpoPushNotificationService;

beforeEach(function () {
    $this->sportSessionRepository = mock(SportSessionRepository::class);
    $this->notificationRepository = mock(NotificationRepository::class);
    $this->commentRepository = mock(SportSessionCommentRepository::class);
    $this->userRepository = mock(UserRepository::class);
    $this->pushTokenRepository = mock(PushTokenRepository::class);
    $this->expoService = mock(ExpoPushNotificationService::class);

    $this->useCase = new RespondToSessionInvitationUseCase(
        $this->sportSessionRepository,
        $this->notificationRepository,
        $this->commentRepository,
        $this->userRepository,
        $this->pushTokenRepository,
        $this->expoService
    );
});

test('envoie des notifications à tous les participants actifs quand quelqu\'un accepte une invitation', function () {
    // Arrange
    $sessionId = 'session-123';
    $respondingUserId = 'user-456';
    $organizerId = 'organizer-789';
    $otherParticipantId = 'participant-999';

    // Mock de la session avec plusieurs participants
    $session = mock('App\Entities\SportSession');
    $session->shouldReceive('getId')->andReturn($sessionId);
    $session->shouldReceive('getSport')->andReturn('tennis');
    $session->shouldReceive('isParticipant')->with($respondingUserId)->andReturn(true);
    $session->shouldReceive('getMaxParticipants')->andReturn(null);

    // Mock de l'organisateur
    $organizer = mock('App\Entities\User');
    $organizer->shouldReceive('getId')->andReturn($organizerId);

    $session->shouldReceive('getOrganizer')->andReturn($organizer);

    // Mock des participants
    $participants = [
        [
            'id' => $organizerId,
            'fullName' => 'Organizer Name',
            'status' => 'accepted'
        ],
        [
            'id' => $respondingUserId,
            'fullName' => 'John Doe',
            'status' => 'pending'
        ],
        [
            'id' => $otherParticipantId,
            'fullName' => 'Jane Smith',
            'status' => 'accepted'
        ],
        [
            'id' => 'declined-user',
            'fullName' => 'Declined User',
            'status' => 'declined'
        ]
    ];

    $session->shouldReceive('getParticipants')->andReturn($participants);

    // Mock de l'utilisateur qui répond
    $respondingUser = mock('App\Entities\User');
    $respondingUser->shouldReceive('getFirstname')->andReturn('John');
    $respondingUser->shouldReceive('getLastname')->andReturn('Doe');

    // Mock des repositories
    $this->sportSessionRepository->shouldReceive('findById')
        ->with($sessionId)
        ->andReturn($session);

    $this->userRepository->shouldReceive('findById')
        ->with($respondingUserId)
        ->andReturn($respondingUser);

    $this->sportSessionRepository->shouldReceive('updateParticipantStatus')
        ->with($sessionId, $respondingUserId, 'accepted')
        ->andReturn(true);

    // Mock des notifications
    $notification1 = mock('App\Entities\Notification');
    $notification1->shouldReceive('getId')->andReturn('notification-1');

    $notification2 = mock('App\Entities\Notification');
    $notification2->shouldReceive('getId')->andReturn('notification-2');

    $this->notificationRepository->shouldReceive('create')
        ->with([
            'user_id' => $organizerId,
            'type' => 'update',
            'title' => 'Invitation acceptée',
            'message' => 'John Doe a accepté l\'invitation à la session de tennis',
            'session_id' => $sessionId,
        ])
        ->andReturn($notification1);

    $this->notificationRepository->shouldReceive('create')
        ->with([
            'user_id' => $otherParticipantId,
            'type' => 'update',
            'title' => 'Invitation acceptée',
            'message' => 'John Doe a accepté l\'invitation à la session de tennis',
            'session_id' => $sessionId,
        ])
        ->andReturn($notification2);

    // Mock des tokens push
    $this->pushTokenRepository->shouldReceive('getTokensForUser')
        ->with($organizerId)
        ->andReturn(['token1']);

    $this->pushTokenRepository->shouldReceive('getTokensForUser')
        ->with($otherParticipantId)
        ->andReturn(['token2']);

    $this->expoService->shouldReceive('sendNotification')
        ->twice()
        ->andReturn(['success' => true]);

    // Mock du commentaire système
    $comment = mock('App\Entities\SportSessionComment');
    $this->commentRepository->shouldReceive('createComment')
        ->andReturn($comment);

    // Act
    $result = $this->useCase->execute($sessionId, $respondingUserId, 'accept');

    // Assert
    expect($result)->toBe($session);
});

test('envoie des notifications à tous les participants actifs quand quelqu\'un décline une invitation', function () {
    // Arrange
    $sessionId = 'session-123';
    $respondingUserId = 'user-456';
    $organizerId = 'organizer-789';
    $otherParticipantId = 'participant-999';

    // Mock de la session avec plusieurs participants
    $session = mock('App\Entities\SportSession');
    $session->shouldReceive('getId')->andReturn($sessionId);
    $session->shouldReceive('getSport')->andReturn('tennis');
    $session->shouldReceive('isParticipant')->with($respondingUserId)->andReturn(true);

    // Mock de l'organisateur
    $organizer = mock('App\Entities\User');
    $organizer->shouldReceive('getId')->andReturn($organizerId);

    $session->shouldReceive('getOrganizer')->andReturn($organizer);

    // Mock des participants
    $participants = [
        [
            'id' => $organizerId,
            'fullName' => 'Organizer Name',
            'status' => 'accepted'
        ],
        [
            'id' => $respondingUserId,
            'fullName' => 'John Doe',
            'status' => 'pending'
        ],
        [
            'id' => $otherParticipantId,
            'fullName' => 'Jane Smith',
            'status' => 'accepted'
        ]
    ];

    $session->shouldReceive('getParticipants')->andReturn($participants);

    // Mock de l'utilisateur qui répond
    $respondingUser = mock('App\Entities\User');
    $respondingUser->shouldReceive('getFirstname')->andReturn('John');
    $respondingUser->shouldReceive('getLastname')->andReturn('Doe');

    // Mock des repositories
    $this->sportSessionRepository->shouldReceive('findById')
        ->with($sessionId)
        ->andReturn($session);

    $this->userRepository->shouldReceive('findById')
        ->with($respondingUserId)
        ->andReturn($respondingUser);

    $this->sportSessionRepository->shouldReceive('updateParticipantStatus')
        ->with($sessionId, $respondingUserId, 'declined')
        ->andReturn(true);

    // Mock des notifications
    $notification1 = mock('App\Entities\Notification');
    $notification1->shouldReceive('getId')->andReturn('notification-1');

    $notification2 = mock('App\Entities\Notification');
    $notification2->shouldReceive('getId')->andReturn('notification-2');

    $this->notificationRepository->shouldReceive('create')
        ->with([
            'user_id' => $organizerId,
            'type' => 'update',
            'title' => 'Invitation déclinée',
            'message' => 'John Doe a décliné l\'invitation à la session de tennis',
            'session_id' => $sessionId,
        ])
        ->andReturn($notification1);

    $this->notificationRepository->shouldReceive('create')
        ->with([
            'user_id' => $otherParticipantId,
            'type' => 'update',
            'title' => 'Invitation déclinée',
            'message' => 'John Doe a décliné l\'invitation à la session de tennis',
            'session_id' => $sessionId,
        ])
        ->andReturn($notification2);

    // Mock des tokens push
    $this->pushTokenRepository->shouldReceive('getTokensForUser')
        ->with($organizerId)
        ->andReturn(['token1']);

    $this->pushTokenRepository->shouldReceive('getTokensForUser')
        ->with($otherParticipantId)
        ->andReturn(['token2']);

    $this->expoService->shouldReceive('sendNotification')
        ->twice()
        ->andReturn(['success' => true]);

    // Mock du commentaire système
    $comment = mock('App\Entities\SportSessionComment');
    $this->commentRepository->shouldReceive('createComment')
        ->andReturn($comment);

    // Act
    $result = $this->useCase->execute($sessionId, $respondingUserId, 'decline');

    // Assert
    expect($result)->toBe($session);
});

test('n\'envoie pas de notification à l\'utilisateur qui répond', function () {
    // Arrange
    $sessionId = 'session-123';
    $respondingUserId = 'user-456';
    $organizerId = 'organizer-789';

    // Mock de la session avec seulement l'organisateur et l'utilisateur qui répond
    $session = mock('App\Entities\SportSession');
    $session->shouldReceive('getId')->andReturn($sessionId);
    $session->shouldReceive('getSport')->andReturn('tennis');
    $session->shouldReceive('isParticipant')->with($respondingUserId)->andReturn(true);
    $session->shouldReceive('getMaxParticipants')->andReturn(null);

    // Mock de l'organisateur
    $organizer = mock('App\Entities\User');
    $organizer->shouldReceive('getId')->andReturn($organizerId);

    $session->shouldReceive('getOrganizer')->andReturn($organizer);

    // Mock des participants
    $participants = [
        [
            'id' => $organizerId,
            'fullName' => 'Organizer Name',
            'status' => 'accepted'
        ],
        [
            'id' => $respondingUserId,
            'fullName' => 'John Doe',
            'status' => 'pending'
        ]
    ];

    $session->shouldReceive('getParticipants')->andReturn($participants);

    // Mock de l'utilisateur qui répond
    $respondingUser = mock('App\Entities\User');
    $respondingUser->shouldReceive('getFirstname')->andReturn('John');
    $respondingUser->shouldReceive('getLastname')->andReturn('Doe');

    // Mock des repositories
    $this->sportSessionRepository->shouldReceive('findById')
        ->with($sessionId)
        ->andReturn($session);

    $this->userRepository->shouldReceive('findById')
        ->with($respondingUserId)
        ->andReturn($respondingUser);

    $this->sportSessionRepository->shouldReceive('updateParticipantStatus')
        ->with($sessionId, $respondingUserId, 'accepted')
        ->andReturn(true);

    // Mock d'une seule notification (pour l'organisateur seulement)
    $notification = mock('App\Entities\Notification');
    $notification->shouldReceive('getId')->andReturn('notification-1');

    $this->notificationRepository->shouldReceive('create')
        ->once() // Une seule fois pour l'organisateur
        ->with([
            'user_id' => $organizerId,
            'type' => 'update',
            'title' => 'Invitation acceptée',
            'message' => 'John Doe a accepté l\'invitation à la session de tennis',
            'session_id' => $sessionId,
        ])
        ->andReturn($notification);

    // Mock des tokens push (seulement pour l'organisateur)
    $this->pushTokenRepository->shouldReceive('getTokensForUser')
        ->with($organizerId)
        ->andReturn(['token1']);

    $this->expoService->shouldReceive('sendNotification')
        ->once() // Une seule fois pour l'organisateur
        ->andReturn(['success' => true]);

    // Mock du commentaire système
    $comment = mock('App\Entities\SportSessionComment');
    $this->commentRepository->shouldReceive('createComment')
        ->andReturn($comment);

    // Act
    $result = $this->useCase->execute($sessionId, $respondingUserId, 'accept');

    // Assert
    expect($result)->toBe($session);
});
