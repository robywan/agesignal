<?php

namespace App\Actions;

use App\Enums\LabTestResultRequestStatus;
use App\Models\LabTestTable;
use Illuminate\Database\Eloquent\Collection;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Schema\ArraySchema;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;
use Prism\Prism\ValueObjects\Messages\UserMessage;

class ExtractLabTestResults
{
    public function __invoke(LabTestTable $table): Collection
    {
        $schema = $this->schema();

        $response = Prism::structured()
            ->using(Provider::Gemini, 'gemini-3-flash-preview')
            ->withMaxTokens(15000)
            ->withClientOptions(['timeout' => 300]) // Adjust request timeout
            ->withClientRetry(2, 1000) // Add automatic retries
            ->withSchema($schema)
            ->withMessages([
                new UserMessage('Mi tiri fuori i valori dalle seguenti analisi?'),
                new UserMessage($table->markdown),
            ])
            ->asStructured();

        $results = new Collection();
        $reponseResults = new Collection($response->structured['results'] ?? []);

        if ($reponseResults->isNotEmpty()) {
            $reponseResults
                ->each(function ($item) use ($table, $results) {
                    $results->add($table->results()->create([
                        'name' => $item['name'] ?? null,
                        'value' => $item['value'] ?? null,
                        'unit_measure' => $item['unit_measure'] ?? null,
                        'reference_values' => $item['reference_values'] ?? null,
                        'notes' => $item['notes'] ?? null,
                    ]));
                });

            $table->update([
                'request_status' => LabTestResultRequestStatus::Completed
            ]);

        } else {           
            $table->update([
                'request_status' => LabTestResultRequestStatus::Failed
            ]);
        }

        $table->aiUsages()->create([
            'prompt_tokens' => $response->usage->promptTokens,
            'completion_tokens' => $response->usage->completionTokens,
            'thought_tokens' => $response->usage->thoughtTokens,
            'cache_read_input_tokens' => $response->usage->cacheReadInputTokens,
            'cache_write_input_tokens' => $response->usage->cacheWriteInputTokens,
            'prompt_token_cost' => 0,
            'completion_token_cost' => 0,
            'thought_token_cost' => 0,
            'cache_read_token_cost' => 0,
            'cache_write_token_cost' => 0,
            'provider' => Provider::Gemini,
            'model' => $response->meta->model,
            'description' => 'Processamento tabella analisi del sangue',
        ]);

        return $results;
    }

    protected function schema(): ObjectSchema
    {
        return new ObjectSchema(
            name: 'list',
            description: 'Lista dei risultati delle analisi',
            properties: [
                new ArraySchema(
                    name: 'results',
                    description: 'Risultati delle analisi',
                    items: new ObjectSchema(
                        name: 'result',
                        description: 'Un risultato dettagliato delle analisi',
                        properties: [
                            new StringSchema('name', 'Esame'),
                            new StringSchema('value', 'Valore Riscontrato'),
                            new StringSchema('unit_measure', 'Unità di Misura'),
                            new StringSchema('reference_values', 'Valori di Riferimento'),
                            new StringSchema('notes', 'Note dal referto'),
                        ],
                        requiredFields: ['name', 'value']
                    )
                ),
            ],
            requiredFields: ['results']
        );
    }
}