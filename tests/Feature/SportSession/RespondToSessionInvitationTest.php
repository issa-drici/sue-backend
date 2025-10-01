<?php

use App\Models\SportSessionModel;
use App\Models\SportSessionParticipantModel;
use App\Models\UserModel;
use App\UseCases\SportSession\RespondToSessionInvitationUseCase;
use App\UseCases\User\UpdateSportsPreferencesUseCase;
use App\Repositories\SportSession\SportSessionRepository;
use App\Repositories\User\UserRepository;
use App\Repositories\Notification\NotificationRepository;
use App\Repositories\SportSessionComment\SportSessionCommentRepository;
use App\Repositories\PushToken\PushTokenRepository;
use App\Services\ExpoPushNotificationService;

beforeEach(function () {
    $this->sportSessionRepository = mock(SportSessionRepository::class);
    $this->userRepository = mock(UserRepository::class);
    $this->notificationRepository = mock(NotificationRepository::class);
    $this->commentRepository = mock(SportSessionCommentRepository::class);
    $this->pushTokenRepository = mock(PushTokenRepository::class);
    $this->expoPushService = mock(ExpoPushNotificationService::class);
    $this->updateSportsPreferencesUseCase = mock(UpdateSportsPreferencesUseCase::class);

    $this->useCase = new RespondToSessionInvitationUseCase(
        $this->sportSessionRepository,
        $this->notificationRepository,
        $this->commentRepository,
        $this->userRepository,
        $this->pushTokenRepository,
        $this->expoPushService,
        $this->updateSportsPreferencesUseCase
    );
});

test('should accept invitation and add sport to user preferences', function () {
    // Arrange
    $sessionId = 'session-123';
    $userId = 'user-456';
    $sport = 'aïkido';
    $response = 'accept';

    // Mock de la session
    $organizer = mock('App\Entities\User');
    $organizer->shouldReceive('getId')->andReturn('organizer-789');
    $organizer->shouldReceive('getFirstname')->andReturn('John');
    $organizer->shouldReceive('getLastname')->andReturn('Doe');

    $session = mock('App\Entities\SportSession');
    $session->shouldReceive('isParticipant')->with($userId)->andReturn(true);
    $session->shouldReceive('getMaxParticipants')->andReturn(null);
    $session->shouldReceive('getParticipants')->andReturn([]);
    $session->shouldReceive('getOrganizer')->andReturn($organizer);
    $session->shouldReceive('getId')->andReturn($sessionId);
    $session->shouldReceive('getSport')->andReturn($sport);

    // Mock de l'utilisateur
    $user = mock('App\Entities\User');
    $user->shouldReceive('getId')->andReturn($userId);
    $user->shouldReceive('getFirstname')->andReturn('Jane');
    $user->shouldReceive('getLastname')->andReturn('Smith');

    // Mock des repositories
    $this->sportSessionRepository->shouldReceive('findById')
        ->with($sessionId)
        ->andReturn($session);

    $this->sportSessionRepository->shouldReceive('updateParticipantStatus')
        ->with($sessionId, $userId, 'accepted')
        ->andReturn(true);

    $this->sportSessionRepository->shouldReceive('findById')
        ->with($sessionId)
        ->andReturn($session);

    $this->userRepository->shouldReceive('findById')
        ->with($userId)
        ->andReturn($user);

    // Mock de l'ajout automatique du sport aux préférences
    $this->updateSportsPreferencesUseCase->shouldReceive('addSportToPreferences')
        ->with($userId, $sport)
        ->andReturn(['aïkido']);

    // Mock de la notification
    $notification = mock('App\Entities\Notification');
    $notification->shouldReceive('getId')->andReturn('notification-123');

    $this->notificationRepository->shouldReceive('create')
        ->andReturn($notification);

    $this->pushTokenRepository->shouldReceive('getTokensForUser')
        ->with('organizer-789')
        ->andReturn(['token1']);

    $this->expoPushService->shouldReceive('sendNotification')
        ->andReturn(['success' => true]);

    // Mock du commentaire
    $comment = mock('App\Entities\SportSessionComment');
    $comment->shouldReceive('getId')->andReturn('comment-123');

    $this->commentRepository->shouldReceive('createComment')
        ->andReturn($comment);

    // Act
    $result = $this->useCase->execute($sessionId, $userId, $response);

    // Assert
    expect($result)->toBe($session);
});

test('should decline invitation without adding sport to preferences', function () {
    // Arrange
    $sessionId = 'session-123';
    $userId = 'user-456';
    $sport = 'aïkido';
    $response = 'decline';

    // Mock de la session
    $organizer = mock('App\Entities\User');
    $organizer->shouldReceive('getId')->andReturn('organizer-789');
    $organizer->shouldReceive('getFirstname')->andReturn('John');
    $organizer->shouldReceive('getLastname')->andReturn('Doe');

    $session = mock('App\Entities\SportSession');
    $session->shouldReceive('isParticipant')->with($userId)->andReturn(true);
    $session->shouldReceive('getOrganizer')->andReturn($organizer);
    $session->shouldReceive('getId')->andReturn($sessionId);
    $session->shouldReceive('getSport')->andReturn($sport);

    // Mock de l'utilisateur
    $user = mock('App\Entities\User');
    $user->shouldReceive('getId')->andReturn($userId);
    $user->shouldReceive('getFirstname')->andReturn('Jane');
    $user->shouldReceive('getLastname')->andReturn('Smith');

    // Mock des repositories
    $this->sportSessionRepository->shouldReceive('findById')
        ->with($sessionId)
        ->andReturn($session);

    $this->sportSessionRepository->shouldReceive('updateParticipantStatus')
        ->with($sessionId, $userId, 'declined')
        ->andReturn(true);

    $this->sportSessionRepository->shouldReceive('findById')
        ->with($sessionId)
        ->andReturn($session);

    $this->userRepository->shouldReceive('findById')
        ->with($userId)
        ->andReturn($user);

    // Ne devrait pas appeler l'ajout aux préférences pour un refus
    $this->updateSportsPreferencesUseCase->shouldNotReceive('addSportToPreferences');

    // Mock de la notification
    $notification = mock('App\Entities\Notification');
    $notification->shouldReceive('getId')->andReturn('notification-123');

    $this->notificationRepository->shouldReceive('create')
        ->andReturn($notification);

    $this->pushTokenRepository->shouldReceive('getTokensForUser')
        ->with('organizer-789')
        ->andReturn(['token1']);

    $this->expoPushService->shouldReceive('sendNotification')
        ->andReturn(['success' => true]);

    // Mock du commentaire
    $comment = mock('App\Entities\SportSessionComment');
    $comment->shouldReceive('getId')->andReturn('comment-123');

    $this->commentRepository->shouldReceive('createComment')
        ->andReturn($comment);

    // Act
    $result = $this->useCase->execute($sessionId, $userId, $response);

    // Assert
    expect($result)->toBe($session);
});

test('should continue accepting invitation even if adding sport to preferences fails', function () {
    // Arrange
    $sessionId = 'session-123';
    $userId = 'user-456';
    $sport = 'aïkido';
    $response = 'accept';

    // Mock de la session
    $organizer = mock('App\Entities\User');
    $organizer->shouldReceive('getId')->andReturn('organizer-789');
    $organizer->shouldReceive('getFirstname')->andReturn('John');
    $organizer->shouldReceive('getLastname')->andReturn('Doe');

    $session = mock('App\Entities\SportSession');
    $session->shouldReceive('isParticipant')->with($userId)->andReturn(true);
    $session->shouldReceive('getMaxParticipants')->andReturn(null);
    $session->shouldReceive('getParticipants')->andReturn([]);
    $session->shouldReceive('getOrganizer')->andReturn($organizer);
    $session->shouldReceive('getId')->andReturn($sessionId);
    $session->shouldReceive('getSport')->andReturn($sport);

    // Mock de l'utilisateur
    $user = mock('App\Entities\User');
    $user->shouldReceive('getId')->andReturn($userId);
    $user->shouldReceive('getFirstname')->andReturn('Jane');
    $user->shouldReceive('getLastname')->andReturn('Smith');

    // Mock des repositories
    $this->sportSessionRepository->shouldReceive('findById')
        ->with($sessionId)
        ->andReturn($session);

    $this->sportSessionRepository->shouldReceive('updateParticipantStatus')
        ->with($sessionId, $userId, 'accepted')
        ->andReturn(true);

    $this->sportSessionRepository->shouldReceive('findById')
        ->with($sessionId)
        ->andReturn($session);

    $this->userRepository->shouldReceive('findById')
        ->with($userId)
        ->andReturn($user);

    // Mock de l'échec de l'ajout automatique du sport aux préférences
    $this->updateSportsPreferencesUseCase->shouldReceive('addSportToPreferences')
        ->with($userId, $sport)
        ->andThrow(new Exception('Database error'));

    // Mock de la notification
    $notification = mock('App\Entities\Notification');
    $notification->shouldReceive('getId')->andReturn('notification-123');

    $this->notificationRepository->shouldReceive('create')
        ->andReturn($notification);

    $this->pushTokenRepository->shouldReceive('getTokensForUser')
        ->with('organizer-789')
        ->andReturn(['token1']);

    $this->expoPushService->shouldReceive('sendNotification')
        ->andReturn(['success' => true]);

    // Mock du commentaire
    $comment = mock('App\Entities\SportSessionComment');
    $comment->shouldReceive('getId')->andReturn('comment-123');

    $this->commentRepository->shouldReceive('createComment')
        ->andReturn($comment);

    // Act
    $result = $this->useCase->execute($sessionId, $userId, $response);

    // Assert - L'acceptation doit quand même réussir malgré l'erreur
    expect($result)->toBe($session);
});

test('should add sport to preferences for existing sport in user preferences', function () {
    // Arrange
    $sessionId = 'session-123';
    $userId = 'user-456';
    $sport = 'tennis';
    $response = 'accept';

    // Mock de la session
    $organizer = mock('App\Entities\User');
    $organizer->shouldReceive('getId')->andReturn('organizer-789');
    $organizer->shouldReceive('getFirstname')->andReturn('John');
    $organizer->shouldReceive('getLastname')->andReturn('Doe');

    $session = mock('App\Entities\SportSession');
    $session->shouldReceive('isParticipant')->with($userId)->andReturn(true);
    $session->shouldReceive('getMaxParticipants')->andReturn(null);
    $session->shouldReceive('getParticipants')->andReturn([]);
    $session->shouldReceive('getOrganizer')->andReturn($organizer);
    $session->shouldReceive('getId')->andReturn($sessionId);
    $session->shouldReceive('getSport')->andReturn($sport);

    // Mock de l'utilisateur
    $user = mock('App\Entities\User');
    $user->shouldReceive('getId')->andReturn($userId);
    $user->shouldReceive('getFirstname')->andReturn('Jane');
    $user->shouldReceive('getLastname')->andReturn('Smith');

    // Mock des repositories
    $this->sportSessionRepository->shouldReceive('findById')
        ->with($sessionId)
        ->andReturn($session);

    $this->sportSessionRepository->shouldReceive('updateParticipantStatus')
        ->with($sessionId, $userId, 'accepted')
        ->andReturn(true);

    $this->sportSessionRepository->shouldReceive('findById')
        ->with($sessionId)
        ->andReturn($session);

    $this->userRepository->shouldReceive('findById')
        ->with($userId)
        ->andReturn($user);

    // Mock de l'ajout automatique du sport aux préférences (retourne les préférences existantes)
    $this->updateSportsPreferencesUseCase->shouldReceive('addSportToPreferences')
        ->with($userId, $sport)
        ->andReturn(['tennis', 'football']); // Pas de changement car déjà présent

    // Mock de la notification
    $notification = mock('App\Entities\Notification');
    $notification->shouldReceive('getId')->andReturn('notification-123');

    $this->notificationRepository->shouldReceive('create')
        ->andReturn($notification);

    $this->pushTokenRepository->shouldReceive('getTokensForUser')
        ->with('organizer-789')
        ->andReturn(['token1']);

    $this->expoPushService->shouldReceive('sendNotification')
        ->andReturn(['success' => true]);

    // Mock du commentaire
    $comment = mock('App\Entities\SportSessionComment');
    $comment->shouldReceive('getId')->andReturn('comment-123');

    $this->commentRepository->shouldReceive('createComment')
        ->andReturn($comment);

    // Act
    $result = $this->useCase->execute($sessionId, $userId, $response);

    // Assert
    expect($result)->toBe($session);
});
