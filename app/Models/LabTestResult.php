<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabTestResult extends Model
{
    protected $fillable = [
        'name',
        'value',
        'unit_measure',
        'reference_values',
        'notes'
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(LabTestResultRequest::class, 'request_id');
    }
}
