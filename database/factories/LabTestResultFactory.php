<?php

namespace Database\Factories;

use App\Models\LabTestResult;
use App\Models\LabTestTable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LabTestResult>
 */
class LabTestResultFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'table_id' => LabTestTable::factory(),
            'name' => fake()->randomElement(['Emoglobina', 'Glucosio', 'Colesterolo totale', 'Transaminasi GPT', 'Creatinina']),
            'value' => fake()->randomElement([
                '12.5 g/dL',
                '95 mg/dL',
                '180 mg/dL',
                '45 U/L',
                '< 0.5 mg/dL',
                '> 5.2 mmol/L',
                'Positivo',
                'Negativo',
            ]),
            'unit_measure' => fake()->optional()->randomElement(['g/dL', 'mg/dL', 'mg/L', 'U/L', 'mmol/L']),
            'reference_values' => fake()->optional()->randomElement([
                '12 - 16 g/dL',
                '70 - 110 mg/dL',
                '< 200 mg/dL',
                '8 - 50 U/L',
                '0.5 - 1.2 mg/dL',
                '* Assente',
            ]),
            'notes' => fake()->optional()->sentence(),
            'loinc_num' => null,
            'loinc_status' => null,
            'loinc_justification' => null,
            'loinc_confidence_score' => null,
            'loinc_debug_payload' => null,
            'numeric_value' => null,
            'operator' => null,
            'textual_value' => null,
            'is_abnormal' => false,
            'reference_min' => null,
            'reference_max' => null,
            'textual_range' => null,
            'normalization_status' => null,
        ];
    }
}
