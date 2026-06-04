<?php

namespace Database\Factories;

use App\Models\LoincCoreEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LoincCoreEntry>
 */
class LoincCoreEntryFactory extends Factory
{
    protected $model = LoincCoreEntry::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'loinc_num' => fake()->unique()->numerify('99999-#'),
            'component' => 'Test',
            'property' => 'Prid',
            'time_aspect' => 'Pt',
            'system' => 'Ser/Plas',
            'scale_type' => 'Qn',
            'method_type' => null,
            'class' => 'CHEM',
            'class_type' => 1,
            'long_common_name' => 'Test analyte',
            'short_name' => 'Test analyte',
            'external_copyright_notice' => null,
            'status' => 'ACTIVE',
            'version_first_released' => '2.80',
            'version_last_changed' => '2.80',
        ];
    }
}
