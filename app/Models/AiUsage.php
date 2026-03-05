<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Prism\Prism\Enums\Provider;

class AiUsage extends Model
{
    /** @use HasFactory<\Database\Factories\AiUsageFactory> */
    use HasFactory;

    protected $fillable = [
        'provider',
        'model',
        'description',
        'prompt_tokens',
        'completion_tokens',
        'thought_tokens',
        'cache_read_input_tokens',
        'cache_write_input_tokens',
        'prompt_token_cost',
        'completion_token_cost',
        'thought_token_cost',
        'cache_read_token_cost',
        'cache_write_token_cost',
    ];

    protected function casts(): array
    {
        return [
            'provider' => Provider::class,
        ];
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
