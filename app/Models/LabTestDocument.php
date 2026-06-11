<?php

namespace App\Models;

use App\Enums\LabTestDocumentStatus;
use App\Enums\LabTestResultRequestStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class LabTestDocument extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;

    protected $fillable = [
        'owner_user_id',
        'test_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'test_date' => 'immutable_date',
            'status' => LabTestDocumentStatus::class,
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('files');
    }

    public function name(): Attribute
    {
        return Attribute::get(
            fn () => $this->test_date ? 'Test di laboratorio del '.$this->test_date->format('Y-m-d') : 'Test di laboratorio'
        );
    }

    public function syncStatusFromTables(): void
    {
        if (! $this->tables()->exists()) {
            return;
        }

        $hasPendingTables = $this->tables()
            ->whereIn('request_status', [
                LabTestResultRequestStatus::Pending->value,
                LabTestResultRequestStatus::Processing->value,
            ])
            ->exists();

        $this->update([
            'status' => $hasPendingTables
                ? LabTestDocumentStatus::Extracted
                : LabTestDocumentStatus::Parsed,
        ]);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function tables(): HasMany
    {
        return $this->hasMany(LabTestTable::class, 'document_id');
    }

    public function results(): HasManyThrough
    {
        return $this->hasManyThrough(LabTestResult::class, LabTestTable::class, 'document_id', 'table_id');
    }
}
