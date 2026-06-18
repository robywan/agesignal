<?php

namespace App\Models;

use App\Enums\LabTestResultLoincStatus;
use App\Enums\LabTestResultNormalizationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class LabTestResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'value',
        'unit_measure',
        'reference_values',
        'notes',
        'loinc_num',
        'loinc_status',
        'loinc_justification',
        'loinc_confidence_score',
        'loinc_debug_payload',
        'numeric_value',
        'operator',
        'textual_value',
        'is_abnormal',
        'reference_min',
        'reference_max',
        'textual_range',
        'normalization_status',
    ];

    protected function casts(): array
    {
        return [
            'loinc_status' => LabTestResultLoincStatus::class,
            'normalization_status' => LabTestResultNormalizationStatus::class,
            'loinc_debug_payload' => 'array',
        ];
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(LabTestTable::class, 'table_id');
    }

    public function loincCoreEntry(): BelongsTo
    {
        return $this->belongsTo(LoincCoreEntry::class, 'loinc_num', 'loinc_num');
    }

    /**
     * @return MorphMany<AiUsage>
     */
    public function aiUsages(): MorphMany
    {
        return $this->morphMany(AiUsage::class, 'subject');
    }
}
