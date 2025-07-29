<?php

use App\Models\ExampleModel;

test('can get all examples', function () {
    // Arrange
    $examples = ExampleModel::factory()->count(3)->create();

    // Act
    $response = $this->getJson('/api/examples');

    // Assert
    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'description',
                    'is_active',
                    'created_at',
                    'updated_at'
                ]
            ],
            'meta' => [
                'total',
                'timestamp'
            ]
        ]);

    expect($response->json('meta.total'))->toBe(3);
});

test('can filter examples by active status', function () {
    // Arrange
    $activeExamples = ExampleModel::factory()->count(2)->create(['is_active' => true]);
    $inactiveExamples = ExampleModel::factory()->count(1)->create(['is_active' => false]);

    // Act
    $response = $this->getJson('/api/examples?active=true');

    // Assert
    $response->assertStatus(200);
    expect($response->json('meta.total'))->toBe(2);
    expect($response->json('data'))->each->toHaveKey('is_active', true);
});

test('can filter examples by name', function () {
    // Arrange
    ExampleModel::factory()->create(['name' => 'Test Example']);
    ExampleModel::factory()->create(['name' => 'Another Example']);
    ExampleModel::factory()->create(['name' => 'Different Name']);

    // Act
    $response = $this->getJson('/api/examples?name=Example');

    // Assert
    $response->assertStatus(200);
    expect($response->json('meta.total'))->toBe(2);
    expect($response->json('data'))->each->toHaveKey('name');
});

test('returns empty array when no examples exist', function () {
    // Act
    $response = $this->getJson('/api/examples');

    // Assert
    $response->assertStatus(200)
        ->assertJson([
            'data' => [],
            'meta' => [
                'total' => 0
            ]
        ]);
});

test('validates filter parameters', function () {
    // Act
    $response = $this->getJson('/api/examples?active=invalid');

    // Assert
    $response->assertStatus(422)
        ->assertJsonValidationErrors(['active']);
});
