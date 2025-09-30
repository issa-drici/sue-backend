<?php

use App\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('should update sports preferences successfully', function () {
    $user = UserModel::factory()->create();

    $response = $this->actingAs($user)
        ->putJson('/api/users/sports-preferences', [
            'sports_preferences' => ['tennis', 'football']
        ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Sports préférés mis à jour avec succès',
            'data' => [
                'sports_preferences' => ['tennis', 'football']
            ]
        ]);

    // Vérifier que les données sont bien sauvegardées
    $user->refresh();
    expect($user->sports_preferences)->toBe(['tennis', 'football']);
});

test('should reject invalid sports', function () {
    $user = UserModel::factory()->create();

    $response = $this->actingAs($user)
        ->putJson('/api/users/sports-preferences', [
            'sports_preferences' => ['invalid-sport', 'tennis']
        ]);

    $response->assertStatus(400)
        ->assertJson([
            'success' => false,
            'error' => [
                'code' => 'VALIDATION_ERROR'
            ]
        ]);
});

test('should reject more than 5 sports', function () {
    $user = UserModel::factory()->create();

    $response = $this->actingAs($user)
        ->putJson('/api/users/sports-preferences', [
            'sports_preferences' => ['tennis', 'football', 'basketball', 'golf', 'musculation', 'extra-sport']
        ]);

    $response->assertStatus(400)
        ->assertJson([
            'success' => false,
            'error' => [
                'code' => 'VALIDATION_ERROR'
            ]
        ]);
});

test('should reject non-array sports preferences', function () {
    $user = UserModel::factory()->create();

    $response = $this->actingAs($user)
        ->putJson('/api/users/sports-preferences', [
            'sports_preferences' => 'not-an-array'
        ]);

    $response->assertStatus(400)
        ->assertJson([
            'success' => false,
            'error' => [
                'code' => 'INVALID_DATA'
            ]
        ]);
});

test('should require authentication', function () {
    $response = $this->putJson('/api/users/sports-preferences', [
        'sports_preferences' => ['tennis', 'football']
    ]);

    $response->assertStatus(401);
});

test('should return user profile with sports preferences', function () {
    $user = UserModel::factory()->create([
        'sports_preferences' => ['tennis', 'football']
    ]);

    $response = $this->actingAs($user)
        ->getJson('/api/users/profile');

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'sports_preferences' => ['tennis', 'football']
            ]
        ]);
});
