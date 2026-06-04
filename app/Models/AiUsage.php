<?php

namespace App\Models;

use Database\Factories\AiUsageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'provider',
    'model',
    'agent',
    'prompt_tokens',
    'completion_tokens',
    'reasoning_tokens',
    'cache_read_input_tokens',
    'cache_write_input_tokens',
    'steps_num',
    'prompt_token_cost',
    'completion_token_cost',
    'reasoning_token_cost',
    'cache_read_token_cost',
    'cache_write_token_cost',
])]
class AiUsage extends Model
{
    /** @use HasFactory<AiUsageFactory> */
    use HasFactory;
}
