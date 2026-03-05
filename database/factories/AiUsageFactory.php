<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AiUsage>
 */
class AiUsageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'model' => fake()->randomElement(['gemini-flash-latest', 'gemini-pro', 'claude-3-5-sonnet']),
            'prompt_tokens' => fake()->numberBetween(100, 5000),
            'completion_tokens' => fake()->numberBetween(50, 2000),
            'thought_tokens' => null,
            'cache_read_input_tokens' => null,
            'cache_write_input_tokens' => null,
        ];
    }
}
