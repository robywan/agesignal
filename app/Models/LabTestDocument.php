<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class LabTestDocument extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'owner_user_id',
        'test_date'
    ];

    protected function casts()
    {
        return [
            'test_date' => 'immutable_date'
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('files');
    }

    public function name(): Attribute
    {
        return Attribute::get(
            fn () => $this->test_date ? 'Test di laboratorio del ' . $this->test_date->format('Y-m-d') : 'Test di laboratorio'
        );
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function tables(): HasMany
    {
        return $this->hasMany(LabTestTable::class, 'document_id');
    }
}