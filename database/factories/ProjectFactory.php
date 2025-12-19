<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'health_check_url' => fake()->unique()->url().'/up',
            'is_active' => true,
            'health_status' => 'unknown',
            'consecutive_failures' => 0,
            'first_failed_at' => null,
            'last_failed_at' => null,
            'last_notification_sent_at' => null,
            'last_recovered_at' => null,
        ];
    }

    /**
     * Indicate that the project is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the project is currently failing.
     */
    public function failing(int $consecutiveFailures = 1): static
    {
        return $this->state(fn (array $attributes) => [
            'health_status' => 'failing',
            'consecutive_failures' => $consecutiveFailures,
            'first_failed_at' => now()->subMinutes($consecutiveFailures),
            'last_failed_at' => now(),
        ]);
    }

    /**
     * Indicate that the project is currently healthy.
     */
    public function healthy(): static
    {
        return $this->state(fn (array $attributes) => [
            'health_status' => 'healthy',
            'consecutive_failures' => 0,
            'first_failed_at' => null,
            'last_failed_at' => null,
        ]);
    }
}
