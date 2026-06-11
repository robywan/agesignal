<?php

namespace Database\Factories;

use App\Enums\LabTestResultRequestStatus;
use App\Models\LabTestDocument;
use App\Models\LabTestTable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LabTestTable>
 */
class LabTestTableFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'document_id' => LabTestDocument::factory(),
            'media_id' => null,
            'page_number' => null,
            'markdown' => null,
            'request_status' => LabTestResultRequestStatus::Pending,
        ];
    }
}
