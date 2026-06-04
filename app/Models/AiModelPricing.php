<?php

namespace App\Models;

use Database\Factories\AiModelPricingFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Prism\Prism\Enums\Provider;

class AiModelPricing extends Model
{
    /** @use HasFactory<AiModelPricingFactory> */
    use HasFactory;

    protected $fillable = [
        'provider',
        'model',
        'prompt_token_price',
        'completion_token_price',
        'thought_token_price',
        'cache_read_token_price',
        'cache_write_token_price',
    ];

    protected function casts(): array
    {
        return [
            'provider' => Provider::class,
            'prompt_token_price' => 'decimal:8',
            'completion_token_price' => 'decimal:8',
            'thought_token_price' => 'decimal:8',
            'cache_read_token_price' => 'decimal:8',
            'cache_write_token_price' => 'decimal:8',
        ];
    }

    #[Scope]
    protected function forModel(Builder $query, string $model, ?string $provider = null): void
    {
        $query->where('model', $model);

        if ($provider) {
            $query->where('provider', $provider);
        }
    }
}
