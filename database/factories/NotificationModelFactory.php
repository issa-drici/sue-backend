<?php

namespace Database\Factories;

use App\Models\NotificationModel;
use App\Models\UserModel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\NotificationModel>
 */
class NotificationModelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = ['invitation', 'reminder', 'update', 'comment', 'session_update', 'session_cancelled'];
        
        return [
            'id' => Str::uuid(),
            'user_id' => UserModel::factory(),
            'type' => $this->faker->randomElement($types),
            'title' => $this->faker->sentence(3),
            'message' => $this->faker->paragraph(2),
            'session_id' => null, // Peut être null
            'read' => $this->faker->boolean(20), // 20% de chance d'être lue
            'push_sent' => $this->faker->boolean(80), // 80% de chance d'avoir été envoyée
            'push_sent_at' => $this->faker->optional()->dateTimeBetween('-1 week', 'now'),
            'push_data' => $this->faker->optional()->randomElement([
                [
                    'sessionTitle' => $this->faker->sentence(3),
                    'organizerName' => $this->faker->name(),
                    'sessionDate' => $this->faker->date(),
                    'sessionTime' => $this->faker->time(),
                ],
                null
            ]),
        ];
    }

    /**
     * Indicate that the notification is read.
     */
    public function read(): static
    {
        return $this->state(fn (array $attributes) => [
            'read' => true,
        ]);
    }

    /**
     * Indicate that the notification is unread.
     */
    public function unread(): static
    {
        return $this->state(fn (array $attributes) => [
            'read' => false,
        ]);
    }

    /**
     * Indicate that the notification is of invitation type.
     */
    public function invitation(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'invitation',
            'title' => 'Nouvelle invitation',
            'message' => $this->faker->name() . ' vous invite à une session de ' . $this->faker->randomElement(['tennis', 'football', 'basketball', 'golf']),
        ]);
    }

    /**
     * Indicate that the notification is of reminder type.
     */
    public function reminder(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'reminder',
            'title' => 'Rappel de session',
            'message' => 'Votre session commence dans ' . $this->faker->randomElement(['1 heure', '2 heures', '30 minutes']),
        ]);
    }

    /**
     * Indicate that the notification is of update type.
     */
    public function update(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'update',
            'title' => 'Mise à jour',
            'message' => 'Une session a été modifiée',
        ]);
    }

    /**
     * Indicate that the notification is of comment type.
     */
    public function comment(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'comment',
            'title' => 'Nouveau commentaire',
            'message' => $this->faker->name() . ' a commenté une session',
        ]);
    }
}
