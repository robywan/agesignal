<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class LabTestResultRequest extends Model
{
    protected $fillable = [
        'prompt_tokens',
        'completion_tokens',
        'thought_tokens',
        'cache_read_input_tokens',
        'cache_write_input_tokens',
    ];

    public function table(): BelongsTo
    {
        return $this->belongsTo(LabTestTable::class, 'table_id');
    }

    public function results(): HasMany
    {
        return $this->hasMany(LabTestResult::class, 'request_id');
    }

    /**
     * @return MorphToMany<AiUsage>
     */
    public function aiUsages(): MorphToMany
    {
        return $this->morphToMany(AiUsage::class, 'subject', 'ai_usage_subjects');
    }
}
