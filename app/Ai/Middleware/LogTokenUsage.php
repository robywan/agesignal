<?php

namespace App\Ai\Middleware;

use App\Models\AiModelPricing;
use App\Models\AiUsage;
use Closure;
use Laravel\Ai\Prompts\AgentPrompt;
use Laravel\Ai\Responses\AgentResponse;

class LogTokenUsage
{
    public function __construct(
        public readonly ?string $key = null
    ) {}

    /**
     * Handle the incoming prompt.
     */
    public function handle(AgentPrompt $prompt, Closure $next)
    {
        return $next($prompt)->then(function (AgentResponse $response) use ($prompt) {
            $providerName = $response->meta->provider;
            $modelName = $response->meta->model;

            $usageData = [
                'provider' => $providerName,
                'model' => $modelName,
                'agent' => get_class($prompt->agent),
                'steps_num' => $response->steps->count(),
                ...$response->usage->toArray(),
            ];

            if ($modelName) {
                $apiModelPricing = AiModelPricing::query()
                    ->forModel($modelName, $providerName)
                    ->first();

                if ($apiModelPricing) {
                    $usageData = [
                        ...$usageData,
                        ...[
                            'prompt_token_cost' => $apiModelPricing->prompt_token_price,
                            'completion_token_cost' => $apiModelPricing->completion_token_price,
                            'reasoning_token_cost' => $apiModelPricing->thought_token_price,
                            'cache_read_token_cost' => $apiModelPricing->cache_read_token_price,
                            'cache_write_token_cost' => $apiModelPricing->cache_write_token_price,
                        ],
                    ];
                }

                $aiUsage = new AiUsage($usageData);

                if ($this->key) {
                    $aiUsage->key = $this->key;
                }

                $aiUsage->save();
            }
        });
    }
}
