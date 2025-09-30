<?php

use App\UseCases\User\UpdateSportsPreferencesUseCase;
use App\Repositories\User\UserRepositoryInterface;
use App\Entities\User;
use App\Entities\SportSession;

beforeEach(function () {
    $this->userRepository = Mockery::mock(UserRepositoryInterface::class);
    $this->useCase = new UpdateSportsPreferencesUseCase($this->userRepository);
});

test('should update sports preferences with valid sports', function () {
    $userId = 'test-user-id';
    $sportsPreferences = ['tennis', 'football'];
    $user = new User($userId, 'John', 'Doe', 'john@example.com', null, 'player', null);

    $this->userRepository
        ->shouldReceive('findById')
        ->with($userId)
        ->once()
        ->andReturn($user);

    $this->userRepository
        ->shouldReceive('update')
        ->with($userId, ['sports_preferences' => $sportsPreferences])
        ->once()
        ->andReturn($user);

    $result = $this->useCase->execute($userId, $sportsPreferences);

    expect($result)->toBe($sportsPreferences);
});

test('should reject invalid sports', function () {
    $userId = 'test-user-id';
    $sportsPreferences = ['invalid-sport', 'tennis'];

    $result = $this->useCase->execute($userId, $sportsPreferences);

    expect($result)->toBeNull();
});

test('should reject more than 5 sports', function () {
    $userId = 'test-user-id';
    $sportsPreferences = ['tennis', 'football', 'basketball', 'golf', 'musculation', 'extra-sport'];

    $result = $this->useCase->execute($userId, $sportsPreferences);

    expect($result)->toBeNull();
});

test('should return null if user not found', function () {
    $userId = 'non-existent-user';
    $sportsPreferences = ['tennis', 'football'];

    $this->userRepository
        ->shouldReceive('findById')
        ->with($userId)
        ->once()
        ->andReturn(null);

    $result = $this->useCase->execute($userId, $sportsPreferences);

    expect($result)->toBeNull();
});

test('should accept all valid sports', function () {
    $userId = 'test-user-id';
    $sportsPreferences = SportSession::getSupportedSports();
    $user = new User($userId, 'John', 'Doe', 'john@example.com', null, 'player', null);

    $this->userRepository
        ->shouldReceive('findById')
        ->with($userId)
        ->once()
        ->andReturn($user);

    $this->userRepository
        ->shouldReceive('update')
        ->with($userId, ['sports_preferences' => $sportsPreferences])
        ->once()
        ->andReturn($user);

    $result = $this->useCase->execute($userId, $sportsPreferences);

    expect($result)->toBe($sportsPreferences);
});
