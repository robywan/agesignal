<?php

namespace App\Filament\Resources\LabTestDocuments\Widgets;

use App\Models\LabTestDocument;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Collection;
use Illuminate\Support\Number;

class TokenUsageOverview extends StatsOverviewWidget
{
    public ?LabTestDocument $record = null;

    protected function getStats(): array
    {
        $inputTokens = new Collection;
        $outputTokens = new Collection;
        $costs = new Collection;
        /*
        $this->record->tables->each(function ($table) use ($costs, $inputTokens, $outputTokens) {
            $table->aiUsages->each(function ($usage) use ($costs, $inputTokens, $outputTokens) {
                $inputTokens->push($usage->prompt_tokens + $usage->thought_tokens + $usage->cache_write_input_tokens + $usage->cache_read_input_tokens);
                $outputTokens->push($usage->completion_tokens);

                $costs->push(($usage->prompt_tokens / 1_000_000) * $usage->prompt_token_cost);
                $costs->push(($usage->completion_tokens / 1_000_000) * $usage->completion_token_cost);
                $costs->push(($usage->thought_tokens / 1_000_000) * $usage->thought_token_cost);
                $costs->push(($usage->cache_read_input_tokens / 1_000_000) * $usage->cache_read_token_cost);
                $costs->push(($usage->cache_write_input_tokens / 1_000_000) * $usage->cache_write_token_cost);
            });
        });
        */
        /*
                $this->record->results->each(function ($result) use ($costs, $inputTokens, $outputTokens) {
                    $result->aiUsages->each(function ($usage) use ($costs, $inputTokens, $outputTokens) {
                        $inputTokens->push($usage->prompt_tokens + $usage->thought_tokens + $usage->cache_write_input_tokens + $usage->cache_read_input_tokens);
                        $outputTokens->push($usage->completion_tokens);

                        $costs->push(($usage->prompt_tokens / 1_000_000) * $usage->prompt_token_cost);
                        $costs->push(($usage->completion_tokens / 1_000_000) * $usage->completion_token_cost);
                        $costs->push(($usage->thought_tokens / 1_000_000) * $usage->thought_token_cost);
                        $costs->push(($usage->cache_read_input_tokens / 1_000_000) * $usage->cache_read_token_cost);
                        $costs->push(($usage->cache_write_input_tokens / 1_000_000) * $usage->cache_write_token_cost);
                    });
                });
                */

        return [
            Stat::make('Token di Input', Number::format($inputTokens->sum(), locale: 'it_IT')),
            Stat::make('Token di Output', Number::format($outputTokens->sum(), locale: 'it_IT')),
            Stat::make('Costo Totale', Number::currency($costs->sum(), 'EUR', locale: 'it_IT')),
        ];
    }
}
