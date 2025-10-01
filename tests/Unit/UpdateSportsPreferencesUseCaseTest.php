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

test('should accept all 5 original valid sports', function () {
    $userId = 'test-user-id';
    $sportsPreferences = ['tennis', 'golf', 'musculation', 'football', 'basketball'];
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

// Tests pour la nouvelle méthode addSportToPreferences

test('should add new sport to empty preferences', function () {
    $userId = 'test-user-id';
    $sport = 'aïkido';
    $user = new User($userId, 'John', 'Doe', 'john@example.com', null, 'player', null);

    $this->userRepository
        ->shouldReceive('findById')
        ->with($userId)
        ->once()
        ->andReturn($user);

    $this->userRepository
        ->shouldReceive('update')
        ->with($userId, ['sports_preferences' => [$sport]])
        ->once()
        ->andReturn($user);

    $result = $this->useCase->addSportToPreferences($userId, $sport);

    expect($result)->toBe([$sport]);
});

test('should add new sport to existing preferences', function () {
    $userId = 'test-user-id';
    $sport = 'aïkido';
    $existingPreferences = ['tennis', 'football'];
    $user = new User($userId, 'John', 'Doe', 'john@example.com', null, 'player', $existingPreferences);

    $this->userRepository
        ->shouldReceive('findById')
        ->with($userId)
        ->once()
        ->andReturn($user);

    $this->userRepository
        ->shouldReceive('update')
        ->with($userId, ['sports_preferences' => ['tennis', 'football', 'aïkido']])
        ->once()
        ->andReturn($user);

    $result = $this->useCase->addSportToPreferences($userId, $sport);

    expect($result)->toBe(['tennis', 'football', 'aïkido']);
});

test('should not add sport if already in preferences', function () {
    $userId = 'test-user-id';
    $sport = 'tennis';
    $existingPreferences = ['tennis', 'football'];
    $user = new User($userId, 'John', 'Doe', 'john@example.com', null, 'player', $existingPreferences);

    $this->userRepository
        ->shouldReceive('findById')
        ->with($userId)
        ->once()
        ->andReturn($user);

    // Ne devrait pas appeler update car le sport est déjà présent
    $this->userRepository
        ->shouldNotReceive('update');

    $result = $this->useCase->addSportToPreferences($userId, $sport);

    expect($result)->toBe($existingPreferences);
});

test('should return null for invalid sport', function () {
    $userId = 'test-user-id';
    $sport = 'invalid-sport';

    $result = $this->useCase->addSportToPreferences($userId, $sport);

    expect($result)->toBeNull();
});

test('should return null if user not found when adding sport', function () {
    $userId = 'non-existent-user';
    $sport = 'tennis';

    $this->userRepository
        ->shouldReceive('findById')
        ->with($userId)
        ->once()
        ->andReturn(null);

    $result = $this->useCase->addSportToPreferences($userId, $sport);

    expect($result)->toBeNull();
});
