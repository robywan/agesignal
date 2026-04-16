<?php

namespace App\Models;

use App\Enums\LabTestResultLoincStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class LabTestResult extends Model
{
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
        'numeric_value',
        'operator',
        'textual_value',
        'is_abnormal',
        'reference_min',
        'reference_max',
        'textual_range',
    ];

    protected function casts(): array
    {
        return [
            'loinc_status' => LabTestResultLoincStatus::class
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
