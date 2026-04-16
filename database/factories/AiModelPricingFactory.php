<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Prism\Prism\Enums\Provider;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AiModelPricing>
 */
class AiModelPricingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'provider' => fake()->randomElement(Provider::cases())->value,
            'model' => fake()->word(),
            'prompt_token_price' => fake()->randomFloat(8, 0, 100),
            'completion_token_price' => fake()->randomFloat(8, 0, 100),
            'thought_token_price' => 0,
            'cache_read_token_price' => 0,
            'cache_write_token_price' => 0,
        ];
    }
}
