<?php

use App\Models\SportSessionModel;
use App\Models\SportSessionParticipantModel;
use App\Models\UserModel;
use App\UseCases\SportSession\InviteUsersToSessionUseCase;
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

    $this->useCase = new InviteUsersToSessionUseCase(
        $this->sportSessionRepository,
        $this->userRepository,
        $this->notificationRepository,
        $this->pushTokenRepository,
        $this->expoPushService
    );
});

test('peut réinviter un utilisateur qui a décliné une invitation', function () {
    // Arrange
    $sessionId = 'session-123';
    $userId = 'user-456';
    $inviterId = 'organizer-789';

    // Mock de la session
    $session = mock('App\Entities\SportSession');
    $session->shouldReceive('getId')->andReturn($sessionId);
    $session->shouldReceive('getSport')->andReturn('tennis');
    $session->shouldReceive('getDate')->andReturn('2024-01-15');
    $session->shouldReceive('getTime')->andReturn('14:00');

    // Mock de l'utilisateur
    $user = mock('App\Entities\User');
    $user->shouldReceive('getId')->andReturn($userId);
    $user->shouldReceive('getFirstname')->andReturn('John');
    $user->shouldReceive('getLastname')->andReturn('Doe');
    $user->shouldReceive('getEmail')->andReturn('john@example.com');

    // Mock des repositories
    $this->sportSessionRepository->shouldReceive('findById')
        ->with($sessionId)
        ->andReturn($session);

    $this->userRepository->shouldReceive('findById')
        ->with($userId)
        ->andReturn($user);

    // L'utilisateur n'est pas déjà participant
    $this->sportSessionRepository->shouldReceive('isUserParticipant')
        ->with($sessionId, $userId)
        ->andReturn(false);

    // L'utilisateur n'est pas déjà invité (pending)
    $this->sportSessionRepository->shouldReceive('isUserInvited')
        ->with($sessionId, $userId)
        ->andReturn(false);

    // L'utilisateur a déjà été invité mais a décliné
    $existingParticipant = [
        'id' => 'participant-123',
        'session_id' => $sessionId,
        'user_id' => $userId,
        'status' => 'declined',
        'user' => [
            'id' => $userId,
            'firstname' => 'John',
            'lastname' => 'Doe',
            'email' => 'john@example.com'
        ]
    ];

    $this->sportSessionRepository->shouldReceive('findParticipant')
        ->with($sessionId, $userId)
        ->andReturn($existingParticipant);

    // La mise à jour du statut réussit
    $this->sportSessionRepository->shouldReceive('updateParticipantStatus')
        ->with($sessionId, $userId, 'pending')
        ->andReturn(true);

    // Mock de la notification
    $notification = mock('App\Entities\Notification');
    $notification->shouldReceive('getId')->andReturn('notification-123');

    $this->notificationRepository->shouldReceive('create')
        ->andReturn($notification);

    $this->notificationRepository->shouldReceive('markAsPushSent')
        ->andReturn(true);

    // Mock des tokens push
    $this->pushTokenRepository->shouldReceive('getTokensForUser')
        ->with($userId)
        ->andReturn(['token1', 'token2']);

    $this->expoPushService->shouldReceive('sendNotification')
        ->andReturn(['success' => true]);

    // Act
    $result = $this->useCase->execute($sessionId, $inviterId, [$userId]);

    // Assert
    expect($result['success'])->toBe(true);
    expect($result['data']['reinvitations'])->toBe(1);
    expect($result['data']['newInvitations'])->toBe(0);
    expect($result['message'])->toContain('réinvité');
    expect($result['data']['invitedUsers'])->toHaveCount(1);
    expect($result['data']['invitedUsers'][0]['id'])->toBe($userId);
});

test('peut inviter un nouvel utilisateur', function () {
    // Arrange
    $sessionId = 'session-123';
    $userId = 'user-456';
    $inviterId = 'organizer-789';

    // Mock de la session
    $session = mock('App\Entities\SportSession');
    $session->shouldReceive('getId')->andReturn($sessionId);
    $session->shouldReceive('getSport')->andReturn('tennis');
    $session->shouldReceive('getDate')->andReturn('2024-01-15');
    $session->shouldReceive('getTime')->andReturn('14:00');

    // Mock de l'utilisateur
    $user = mock('App\Entities\User');
    $user->shouldReceive('getId')->andReturn($userId);
    $user->shouldReceive('getFirstname')->andReturn('Jane');
    $user->shouldReceive('getLastname')->andReturn('Smith');
    $user->shouldReceive('getEmail')->andReturn('jane@example.com');

    // Mock des repositories
    $this->sportSessionRepository->shouldReceive('findById')
        ->with($sessionId)
        ->andReturn($session);

    $this->userRepository->shouldReceive('findById')
        ->with($userId)
        ->andReturn($user);

    // L'utilisateur n'est pas déjà participant
    $this->sportSessionRepository->shouldReceive('isUserParticipant')
        ->with($sessionId, $userId)
        ->andReturn(false);

    // L'utilisateur n'est pas déjà invité
    $this->sportSessionRepository->shouldReceive('isUserInvited')
        ->with($sessionId, $userId)
        ->andReturn(false);

    // L'utilisateur n'a jamais été invité
    $this->sportSessionRepository->shouldReceive('findParticipant')
        ->with($sessionId, $userId)
        ->andReturn(null);

    // La création de l'invitation réussit
    $this->sportSessionRepository->shouldReceive('inviteUser')
        ->with($sessionId, $userId)
        ->andReturn(true);

    // Mock de la notification
    $notification = mock('App\Entities\Notification');
    $notification->shouldReceive('getId')->andReturn('notification-123');

    $this->notificationRepository->shouldReceive('create')
        ->andReturn($notification);

    $this->notificationRepository->shouldReceive('markAsPushSent')
        ->andReturn(true);

    // Mock des tokens push
    $this->pushTokenRepository->shouldReceive('getTokensForUser')
        ->with($userId)
        ->andReturn(['token1']);

    $this->expoPushService->shouldReceive('sendNotification')
        ->andReturn(['success' => true]);

    // Act
    $result = $this->useCase->execute($sessionId, $inviterId, [$userId]);

    // Assert
    expect($result['success'])->toBe(true);
    expect($result['data']['newInvitations'])->toBe(1);
    expect($result['data']['reinvitations'])->toBe(0);
    expect($result['message'])->toContain('invité');
    expect($result['data']['invitedUsers'])->toHaveCount(1);
    expect($result['data']['invitedUsers'][0]['id'])->toBe($userId);
});

test('ne peut pas inviter un utilisateur qui participe déjà', function () {
    // Arrange
    $sessionId = 'session-123';
    $userId = 'user-456';
    $inviterId = 'organizer-789';

    // Mock de la session
    $session = mock('App\Entities\SportSession');
    $session->shouldReceive('getId')->andReturn($sessionId);

    // Mock de l'utilisateur
    $user = mock('App\Entities\User');
    $user->shouldReceive('getFirstname')->andReturn('John');
    $user->shouldReceive('getLastname')->andReturn('Doe');

    // Mock des repositories
    $this->sportSessionRepository->shouldReceive('findById')
        ->with($sessionId)
        ->andReturn($session);

    $this->userRepository->shouldReceive('findById')
        ->with($userId)
        ->andReturn($user);

    // L'utilisateur participe déjà
    $this->sportSessionRepository->shouldReceive('isUserParticipant')
        ->with($sessionId, $userId)
        ->andReturn(true);

    // Act
    $result = $this->useCase->execute($sessionId, $inviterId, [$userId]);

    // Assert
    expect($result['success'])->toBe(false);
    expect($result['error']['code'])->toBe('VALIDATION_ERROR');
    expect($result['error']['message'])->toBe('Aucun utilisateur n\'a pu être invité');
    expect($result['error']['details'])->toContain('participe déjà');
});
