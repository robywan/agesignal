<?php

namespace App\Models;

use App\Enums\LabTestResultRequestStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class LabTestTable extends Model
{
    protected $fillable = [
        'media_id',
        'page_number',
        'markdown',
        'cells'
    ];

    protected function casts()
    {
        return [
            'cells' => 'array',
            'request_status' => LabTestResultRequestStatus::class,
        ];
    }

    protected function title(): Attribute
    {
        return Attribute::get(
            fn () => 'Tabella pagina ' . $this->page_number
        );
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(LabTestDocument::class, 'document_id');
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'media_id');
    }

    /**
     * @return HasMany<LabTestResultRequest>
     */
    public function requests(): HasMany
    {
        return $this->hasMany(LabTestResultRequest::class, 'table_id');
    }

    /**
     * @return HasManyThrough<LabTestResult>
     */
    public function results(): HasManyThrough
    {
        return $this->hasManyThrough(LabTestResult::class, LabTestResultRequest::class, 'table_id', 'request_id');
    }
}