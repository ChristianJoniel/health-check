<?php

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\HealthCheckFailure>
 */
class HealthCheckFailureFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'error_message' => fake()->sentence(),
            'response_code' => fake()->randomElement([500, 502, 503, 504, 0]),
            'response_time_ms' => fake()->numberBetween(100, 30000),
            'checked_at' => fake()->dateTimeBetween('-7 days', 'now'),
        ];
    }

    /**
     * Indicate that the failure was a timeout.
     */
    public function timeout(): static
    {
        return $this->state(fn (array $attributes) => [
            'error_message' => 'Connection timed out',
            'response_code' => 0,
            'response_time_ms' => 10000,
        ]);
    }

    /**
     * Indicate that the failure was a server error.
     */
    public function serverError(): static
    {
        return $this->state(fn (array $attributes) => [
            'error_message' => 'Internal Server Error',
            'response_code' => 500,
        ]);
    }
}
