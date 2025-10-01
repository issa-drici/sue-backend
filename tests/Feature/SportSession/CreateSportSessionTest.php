<?php

use App\Models\SportSessionModel;
use App\Models\UserModel;
use App\UseCases\SportSession\CreateSportSessionUseCase;
use App\UseCases\User\UpdateSportsPreferencesUseCase;
use App\Repositories\SportSession\SportSessionRepository;
use App\Repositories\User\UserRepository;
use App\Repositories\Notification\NotificationRepository;
use App\Repositories\PushToken\PushTokenRepository;
use App\Services\ExpoPushNotificationService;

beforeEach(function () {
    $this->sportSessionRepository = mock(SportSessionRepository::class);
    $this->userRepository = mock(UserRepository::class);
    $this->notificationRepository = mock(NotificationRepository::class);
    $this->pushTokenRepository = mock(PushTokenRepository::class);
    $this->expoPushService = mock(ExpoPushNotificationService::class);
    $this->updateSportsPreferencesUseCase = mock(UpdateSportsPreferencesUseCase::class);

    $this->useCase = new CreateSportSessionUseCase(
        $this->sportSessionRepository,
        $this->notificationRepository,
        $this->userRepository,
        $this->pushTokenRepository,
        $this->expoPushService,
        $this->updateSportsPreferencesUseCase
    );
});

test('should create session and add sport to organizer preferences when sport is new', function () {
    // Arrange
    $organizerId = 'organizer-123';
    $sport = 'aïkido';
    $sessionData = [
        'sport' => $sport,
        'date' => '2025-12-21',
        'startTime' => '14:00',
        'endTime' => '16:00',
        'location' => 'Dojo central',
        'maxParticipants' => 8,
        'pricePerPerson' => 15,
        'organizer_id' => $organizerId
    ];

    // Mock de l'organisateur avec des préférences existantes
    $organizer = mock('App\Entities\User');
    $organizer->shouldReceive('getId')->andReturn($organizerId);
    $organizer->shouldReceive('getFirstname')->andReturn('John');
    $organizer->shouldReceive('getLastname')->andReturn('Doe');
    $organizer->shouldReceive('getSportsPreferences')->andReturn(['tennis', 'football']);

    // Mock de la session créée
    $session = mock('App\Entities\SportSession');
    $session->shouldReceive('getId')->andReturn('session-123');
    $session->shouldReceive('getSport')->andReturn($sport);
    $session->shouldReceive('getDate')->andReturn('2025-12-21');
    $session->shouldReceive('getStartTime')->andReturn('14:00');
    $session->shouldReceive('getEndTime')->andReturn('16:00');
    $session->shouldReceive('getLocation')->andReturn('Dojo central');
    $session->shouldReceive('getOrganizer')->andReturn($organizer);

    // Mock des repositories
    $this->userRepository->shouldReceive('findById')
        ->with($organizerId)
        ->andReturn($organizer);

    $this->sportSessionRepository->shouldReceive('create')
        ->with($sessionData)
        ->andReturn($session);

    // Mock de l'ajout automatique du sport aux préférences
    $this->updateSportsPreferencesUseCase->shouldReceive('addSportToPreferences')
        ->with($organizerId, $sport)
        ->andReturn(['tennis', 'football', 'aïkido']);

    // Mock de la notification
    $notification = mock('App\Entities\Notification');
    $notification->shouldReceive('getId')->andReturn('notification-123');

    $this->notificationRepository->shouldReceive('create')
        ->andReturn($notification);

    // Act
    $result = $this->useCase->execute($sessionData);

    // Assert
    expect($result)->toBe($session);
});

test('should create session without adding sport if already in preferences', function () {
    // Arrange
    $organizerId = 'organizer-123';
    $sport = 'tennis';
    $sessionData = [
        'sport' => $sport,
        'date' => '2025-12-21',
        'startTime' => '14:00',
        'endTime' => '16:00',
        'location' => 'Court de tennis',
        'organizer_id' => $organizerId
    ];

    // Mock de l'organisateur avec le sport déjà dans ses préférences
    $organizer = mock('App\Entities\User');
    $organizer->shouldReceive('getId')->andReturn($organizerId);
    $organizer->shouldReceive('getFirstname')->andReturn('John');
    $organizer->shouldReceive('getLastname')->andReturn('Doe');
    $organizer->shouldReceive('getSportsPreferences')->andReturn(['tennis', 'football']);

    // Mock de la session créée
    $session = mock('App\Entities\SportSession');
    $session->shouldReceive('getId')->andReturn('session-123');
    $session->shouldReceive('getSport')->andReturn($sport);
    $session->shouldReceive('getDate')->andReturn('2025-12-21');
    $session->shouldReceive('getStartTime')->andReturn('14:00');
    $session->shouldReceive('getEndTime')->andReturn('16:00');
    $session->shouldReceive('getLocation')->andReturn('Court de tennis');
    $session->shouldReceive('getOrganizer')->andReturn($organizer);

    // Mock des repositories
    $this->userRepository->shouldReceive('findById')
        ->with($organizerId)
        ->andReturn($organizer);

    $this->sportSessionRepository->shouldReceive('create')
        ->with($sessionData)
        ->andReturn($session);

    // Mock de l'ajout automatique du sport aux préférences (retourne les préférences existantes)
    $this->updateSportsPreferencesUseCase->shouldReceive('addSportToPreferences')
        ->with($organizerId, $sport)
        ->andReturn(['tennis', 'football']); // Pas de changement

    // Mock de la notification
    $notification = mock('App\Entities\Notification');
    $notification->shouldReceive('getId')->andReturn('notification-123');

    $this->notificationRepository->shouldReceive('create')
        ->andReturn($notification);

    // Act
    $result = $this->useCase->execute($sessionData);

    // Assert
    expect($result)->toBe($session);
});

test('should create session even if adding sport to preferences fails', function () {
    // Arrange
    $organizerId = 'organizer-123';
    $sport = 'aïkido';
    $sessionData = [
        'sport' => $sport,
        'date' => '2025-12-21',
        'startTime' => '14:00',
        'endTime' => '16:00',
        'location' => 'Dojo central',
        'organizer_id' => $organizerId
    ];

    // Mock de l'organisateur
    $organizer = mock('App\Entities\User');
    $organizer->shouldReceive('getId')->andReturn($organizerId);
    $organizer->shouldReceive('getFirstname')->andReturn('John');
    $organizer->shouldReceive('getLastname')->andReturn('Doe');

    // Mock de la session créée
    $session = mock('App\Entities\SportSession');
    $session->shouldReceive('getId')->andReturn('session-123');
    $session->shouldReceive('getSport')->andReturn($sport);
    $session->shouldReceive('getDate')->andReturn('2025-12-21');
    $session->shouldReceive('getStartTime')->andReturn('14:00');
    $session->shouldReceive('getEndTime')->andReturn('16:00');
    $session->shouldReceive('getLocation')->andReturn('Dojo central');
    $session->shouldReceive('getOrganizer')->andReturn($organizer);

    // Mock des repositories
    $this->userRepository->shouldReceive('findById')
        ->with($organizerId)
        ->andReturn($organizer);

    $this->sportSessionRepository->shouldReceive('create')
        ->with($sessionData)
        ->andReturn($session);

    // Mock de l'échec de l'ajout automatique du sport aux préférences
    $this->updateSportsPreferencesUseCase->shouldReceive('addSportToPreferences')
        ->with($organizerId, $sport)
        ->andThrow(new Exception('Database error'));

    // Mock de la notification
    $notification = mock('App\Entities\Notification');
    $notification->shouldReceive('getId')->andReturn('notification-123');

    $this->notificationRepository->shouldReceive('create')
        ->andReturn($notification);

    // Act
    $result = $this->useCase->execute($sessionData);

    // Assert - La session doit quand même être créée malgré l'erreur
    expect($result)->toBe($session);
});

test('should create session with participants and add sport to organizer preferences', function () {
    // Arrange
    $organizerId = 'organizer-123';
    $participantId = 'participant-456';
    $sport = 'basketball';
    $sessionData = [
        'sport' => $sport,
        'date' => '2025-12-21',
        'startTime' => '18:00',
        'endTime' => '20:00',
        'location' => 'Gymnase municipal',
        'organizer_id' => $organizerId,
        'participantIds' => [$participantId]
    ];

    // Mock de l'organisateur
    $organizer = mock('App\Entities\User');
    $organizer->shouldReceive('getId')->andReturn($organizerId);
    $organizer->shouldReceive('getFirstname')->andReturn('John');
    $organizer->shouldReceive('getLastname')->andReturn('Doe');

    // Mock du participant
    $participant = mock('App\Entities\User');
    $participant->shouldReceive('getId')->andReturn($participantId);

    // Mock de la session créée
    $session = mock('App\Entities\SportSession');
    $session->shouldReceive('getId')->andReturn('session-123');
    $session->shouldReceive('getSport')->andReturn($sport);
    $session->shouldReceive('getDate')->andReturn('2025-12-21');
    $session->shouldReceive('getStartTime')->andReturn('18:00');
    $session->shouldReceive('getEndTime')->andReturn('20:00');
    $session->shouldReceive('getLocation')->andReturn('Gymnase municipal');
    $session->shouldReceive('getOrganizer')->andReturn($organizer);

    // Mock des repositories
    $this->userRepository->shouldReceive('findById')
        ->with($organizerId)
        ->andReturn($organizer);

    $this->userRepository->shouldReceive('findById')
        ->with($participantId)
        ->andReturn($participant);

    $this->sportSessionRepository->shouldReceive('create')
        ->with($sessionData)
        ->andReturn($session);

    $this->sportSessionRepository->shouldReceive('isUserParticipant')
        ->with('session-123', $participantId)
        ->andReturn(false);

    $this->sportSessionRepository->shouldReceive('addParticipant')
        ->with('session-123', $participantId, 'pending')
        ->andReturn(true);

    // Mock de l'ajout automatique du sport aux préférences
    $this->updateSportsPreferencesUseCase->shouldReceive('addSportToPreferences')
        ->with($organizerId, $sport)
        ->andReturn(['basketball']);

    // Mock de la notification
    $notification = mock('App\Entities\Notification');
    $notification->shouldReceive('getId')->andReturn('notification-123');

    $this->notificationRepository->shouldReceive('create')
        ->andReturn($notification);

    // Mock des tokens push
    $this->pushTokenRepository->shouldReceive('getTokensForUser')
        ->with($participantId)
        ->andReturn(['token1']);

    $this->expoPushService->shouldReceive('sendNotification')
        ->andReturn(['success' => true]);

    $this->notificationRepository->shouldReceive('markAsPushSent')
        ->andReturn(true);

    // Act
    $result = $this->useCase->execute($sessionData);

    // Assert
    expect($result)->toBe($session);
});
