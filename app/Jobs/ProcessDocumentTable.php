<?php

namespace App\Jobs;

use App\Enums\LabTestResultRequestStatus;
use App\Models\LabTestTable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Collection;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Schema\ArraySchema;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;
use Prism\Prism\ValueObjects\Messages\UserMessage;

class ProcessDocumentTable implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected LabTestTable $labTestTable
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $schema = $this->schema();

        $response = Prism::structured()
            ->using(Provider::Gemini, 'gemini-3-flash-preview')
            ->withMaxTokens(6000)
            ->withClientOptions(['timeout' => 300]) // Adjust request timeout
            ->withClientRetry(2, 1000) // Add automatic retries
            ->withSchema($schema)
            ->withMessages([
                new UserMessage('Mi tiri fuori i valori dalle seguenti analisi?'),
                new UserMessage($this->labTestTable->markdown),
            ])
            ->asStructured();

        Collection::make($response->structured['results'])
            ->each(function ($item) {
                $this->labTestTable->results()->create([
                    'name' => $item['name'] ?? null,
                    'value' => $item['value'] ?? null,
                    'unit_measure' => $item['unit_measure'] ?? null,
                    'reference_values' => $item['reference_values'] ?? null,
                    'notes' => $item['notes'] ?? null,
                ]);
            });

        $this->labTestTable
            ->forceFill(['request_status' => LabTestResultRequestStatus::Completed])
            ->save();

        $this->labTestTable->aiUsages()->create([
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
