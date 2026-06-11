<?php

namespace Database\Factories;

use App\Enums\LabTestDocumentStatus;
use App\Models\LabTestDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LabTestDocument>
 */
class LabTestDocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'owner_user_id' => User::factory(),
            'test_date' => $this->faker->dateTimeBetween('-2 years', 'now')->format('Y-m-d'),
            'status' => LabTestDocumentStatus::Pending,
        ];
    }
}
