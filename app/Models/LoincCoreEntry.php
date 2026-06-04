<?php

namespace App\Models;

use Database\Factories\LoincCoreEntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[UseFactory(LoincCoreEntryFactory::class)]
#[Fillable([
    'loinc_num',
    'component',
    'property',
    'time_aspect',
    'system',
    'scale_type',
    'method_type',
    'class',
    'class_type',
    'long_common_name',
    'short_name',
    'external_copyright_notice',
    'status',
    'version_first_released',
    'version_last_changed',
])]
class LoincCoreEntry extends Model
{
    use HasFactory;

    protected $primaryKey = 'loinc_num';

    public $incrementing = false;

    protected $keyType = 'string';
}
