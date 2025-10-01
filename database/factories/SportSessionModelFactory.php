<?php

namespace Database\Factories;

use App\Models\SportSessionModel;
use App\Models\UserModel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SportSessionModel>
 */
class SportSessionModelFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = SportSessionModel::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => Str::uuid(),
            'sport' => $this->faker->randomElement(\App\Services\SportService::getSupportedSports()),
            'date' => $this->faker->dateTimeBetween('now', '+30 days')->format('Y-m-d'),
            'start_time' => $this->faker->time('H:i'),
            'end_time' => $this->faker->time('H:i'),
            'location' => $this->faker->city() . ' ' . $this->faker->randomElement(['Club', 'Centre Sportif', 'Gymnase', 'Stade']),
            'organizer_id' => UserModel::factory(),
            'max_participants' => $this->faker->optional()->numberBetween(2, 20),
            'price_per_person' => $this->faker->optional()->randomFloat(2, 0, 50),
            'status' => 'active',
        ];
    }

    /**
     * Indicate that the session is in the past.
     */
    public function past(): static
    {
        return $this->state(fn (array $attributes) => [
            'date' => $this->faker->dateTimeBetween('-30 days', 'now')->format('Y-m-d'),
        ]);
    }

    /**
     * Indicate that the session is today.
     */
    public function today(): static
    {
        return $this->state(fn (array $attributes) => [
            'date' => now()->format('Y-m-d'),
        ]);
    }

    /**
     * Indicate that the session is tomorrow.
     */
    public function tomorrow(): static
    {
        return $this->state(fn (array $attributes) => [
            'date' => now()->addDay()->format('Y-m-d'),
        ]);
    }

    /**
     * Indicate that the session is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
        ]);
    }
}
