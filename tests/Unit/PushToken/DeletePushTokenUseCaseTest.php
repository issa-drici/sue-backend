<?php

namespace Tests\Unit\PushToken;

use App\Entities\PushToken;
use App\Repositories\PushToken\PushTokenRepositoryInterface;
use App\UseCases\PushToken\DeletePushTokenUseCase;
use DateTime;
use Tests\TestCase;
use Mockery;

class DeletePushTokenUseCaseTest extends TestCase
{
    private DeletePushTokenUseCase $useCase;
    private $mockRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockRepository = Mockery::mock(PushTokenRepositoryInterface::class);
        $this->useCase = new DeletePushTokenUseCase($this->mockRepository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_successfully_deletes_user_own_token()
    {
        $userId = 'user-123';
        $token = 'ExponentPushToken[test-token]';

        $pushToken = new PushToken(
            id: 'token-123',
            userId: $userId,
            token: $token,
            platform: 'ios',
            isActive: true,
            createdAt: new DateTime(),
            updatedAt: new DateTime()
        );

        $this->mockRepository
            ->shouldReceive('findByToken')
            ->with($token)
            ->once()
            ->andReturn($pushToken);

        $this->mockRepository
            ->shouldReceive('deleteToken')
            ->with($token)
            ->once()
            ->andReturn(true);

        $result = $this->useCase->execute($userId, $token);

        $this->assertTrue($result['success']);
        $this->assertEquals('Token supprimé avec succès', $result['message']);
    }

    /** @test */
    public function it_fails_when_token_does_not_belong_to_user()
    {
        $userId = 'user-123';
        $otherUserId = 'user-456';
        $token = 'ExponentPushToken[test-token]';

        $pushToken = new PushToken(
            id: 'token-123',
            userId: $otherUserId, // Token appartient à un autre utilisateur
            token: $token,
            platform: 'ios',
            isActive: true,
            createdAt: new DateTime(),
            updatedAt: new DateTime()
        );

        $this->mockRepository
            ->shouldReceive('findByToken')
            ->with($token)
            ->once()
            ->andReturn($pushToken);

        $this->mockRepository
            ->shouldNotReceive('deleteToken');

        $result = $this->useCase->execute($userId, $token);

        $this->assertFalse($result['success']);
        $this->assertEquals('TOKEN_NOT_FOUND_OR_ALREADY_DELETED', $result['error']);
    }

    /** @test */
    public function it_fails_when_token_does_not_exist()
    {
        $userId = 'user-123';
        $token = 'ExponentPushToken[nonexistent-token]';

        $this->mockRepository
            ->shouldReceive('findByToken')
            ->with($token)
            ->once()
            ->andReturn(null);

        $this->mockRepository
            ->shouldNotReceive('deleteToken');

        $result = $this->useCase->execute($userId, $token);

        $this->assertFalse($result['success']);
        $this->assertEquals('TOKEN_NOT_FOUND_OR_ALREADY_DELETED', $result['error']);
    }

    /** @test */
    public function it_fails_when_deletion_fails()
    {
        $userId = 'user-123';
        $token = 'ExponentPushToken[test-token]';

        $pushToken = new PushToken(
            id: 'token-123',
            userId: $userId,
            token: $token,
            platform: 'ios',
            isActive: true,
            createdAt: new DateTime(),
            updatedAt: new DateTime()
        );

        $this->mockRepository
            ->shouldReceive('findByToken')
            ->with($token)
            ->once()
            ->andReturn($pushToken);

        $this->mockRepository
            ->shouldReceive('deleteToken')
            ->with($token)
            ->once()
            ->andReturn(false); // Échec de la suppression

        $result = $this->useCase->execute($userId, $token);

        $this->assertFalse($result['success']);
        $this->assertEquals('TOKEN_NOT_FOUND_OR_ALREADY_DELETED', $result['error']);
    }

    /** @test */
    public function it_handles_exceptions_gracefully()
    {
        $userId = 'user-123';
        $token = 'ExponentPushToken[test-token]';

        $this->mockRepository
            ->shouldReceive('findByToken')
            ->with($token)
            ->once()
            ->andThrow(new \Exception('Database error'));

        $this->mockRepository
            ->shouldNotReceive('deleteToken');

        $result = $this->useCase->execute($userId, $token);

        $this->assertFalse($result['success']);
        $this->assertEquals('INTERNAL_ERROR', $result['error']);
    }
}
