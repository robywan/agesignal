<?php

namespace App\Actions;

use App\Models\AiModelPricing;
use App\Models\LabTestResult;
use App\Prisma\Tools\SearchLoincCodeTool;
use Illuminate\Support\Collection;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Schema\BooleanSchema;
use Prism\Prism\Schema\EnumSchema;
use Prism\Prism\Schema\NumberSchema;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;
use Prism\Prism\Structured\Response;

class NormalizeLabTestResultAction
{
    public function __invoke(LabTestResult $testResult): Response
    {
        $response = Prism::structured()
            ->using(Provider::Gemini, 'gemini-3.1-flash-lite-preview')
            ->withMaxTokens(10000)
            ->withClientOptions(['timeout' => 300]) // Adjust request timeout
            ->withClientRetry(2, 1000) // Add automatic retries
            ->withSystemPrompt(<<<'INSTRUCTIONS'
                Agisci come un parser di dati di laboratorio. Riceverai i dati di un singolo esame clinico. Il tuo unico compito è pulire e normalizzare le stringhe grezze in un formato strutturato.

                REGOLE DI PARSING:
                1. SCALA (loinc_scale_type): Se è "Qn", estrai a tutti i costi il numero da 'raw_value'. Se è "Ord" o "Nom", il risultato va in 'textual_value' e 'numeric_value' sarà null.
                2. NUMERI E VIRGOLE: Converti le virgole in punti (es. "12,5" -> 12.5). Ignora spazi e lettere attaccate ai numeri in 'raw_value'.
                3. OPERATORI: Se 'raw_value' o 'raw_range' contengono prefissi come "<" o ">" (es. "< 0.5"), isola il segno nel campo 'operator'.
                4. RANGE SCOMPOSTO: Dividi 'raw_range' estraendo min e max. 
                RESTITUISCI SEMPRE le chiavi 'reference_min' e 'reference_max', impostandole a null se mancano.
                Esempi di ragionamento:
                - Se 'raw_range' è "60 - 110" -> reference_min: 60, reference_max: 110
                - Se 'raw_range' è "< 50" -> reference_min: null, reference_max: 50
                - Se 'raw_range' è "> 10" -> reference_min: 10, reference_max: null
                - Se 'raw_range' è "Assente" -> reference_min: null, reference_max: null, textual_range: "Assente"
                5. ANOMALIE: Imposta 'is_abnormal' a true se 'raw_value' contiene asterischi (*), lettere come 'H' (High) o 'L' (Low), o se il valore numerico calcolato supera i limiti di reference_min/reference_max.
                INSTRUCTIONS)
                // CONTESTO: [Analisi del Sangue / Analisi delle Feci]
            ->withMaxSteps(30) // Necessario minimo 2 per poter utilizzare il tool
            ->withTools([
                SearchLoincCodeTool::make(),
            ])
            ->withSchema(new ObjectSchema(
                name: 'normalized',
                description: 'Un singolo risultato normalizzato',
                properties: [
                    new NumberSchema(
                        'numeric_value',
                        'Il valore numerico estratto, o null se non applicabile',
                        true
                    ),
                    new EnumSchema(
                        'operator', 
                        'L\'operatore associato al valore numerico, se presente',
                        ['<', '>', '<=', '>=', '=']
                    ),
                    new StringSchema(
                        'textual_value',
                        'Il valore testuale estratto, o null se non applicabile',
                        true
                    ),
                    new BooleanSchema(
                        'is_abnormal',
                        'Indica se il risultato è anomalo',
                        true
                    ),
                    new NumberSchema(
                        'reference_min',
                        'Il valore minimo di riferimento, o null se non applicabile',
                        true
                    ),
                    new NumberSchema(
                        'reference_max',
                        'Il valore massimo di riferimento, o null se non applicabile',
                        true
                    ),
                    new StringSchema(
                        'textual_range',
                        'Il range testuale, o null se non applicabile',
                        true
                    )
                ],
                requiredFields: [
                    'numeric_value', 
                    'operator', 
                    'textual_value', 
                    'is_abnormal', 
                    'reference_min', 
                    'reference_max', 
                    'textual_range'
                ]
            ))
            ->withPrompt(json_encode([
                'exam_name' => $testResult->name,
                'raw_value' => $testResult->value,
                'raw_range' => $testResult->reference_values,
                'loinc_scale_type' => $testResult->loincCoreEntry?->scale_type ?? 'Qn', // Default a Qn se non mappato
            ]))
            ->asStructured();

        $usageData = [
            'provider' => Provider::Gemini,
            'model' => $response->meta->model,
            'description' => 'Classificazione esame',
            'prompt_tokens' => $response->usage->promptTokens,
            'completion_tokens' => $response->usage->completionTokens,
            'thought_tokens' => $response->usage->thoughtTokens,
            'cache_read_input_tokens' => $response->usage->cacheReadInputTokens,
            'cache_write_input_tokens' => $response->usage->cacheWriteInputTokens,
        ];

        $apiModelPricing = AiModelPricing::query()
            ->forModel(Provider::Gemini, $response->meta->model)
            ->first();

        if ($apiModelPricing) {
            $usageData = [ 
                ...$usageData, 
                ...[
                    'prompt_token_cost' => $apiModelPricing->prompt_token_price,
                    'completion_token_cost' => $apiModelPricing->completion_token_price,
                    'thought_token_cost' => $apiModelPricing->thought_token_price,
                    'cache_read_token_cost' => $apiModelPricing->cache_read_token_price,
                    'cache_write_token_cost' => $apiModelPricing->cache_write_token_price,
                ]
            ];
        }

        $testResult->aiUsages()->create($usageData);

        if (!empty($response->structured)) {
            $testResult->update([
                'numeric_value' => $response->structured['numeric_value'] ?? null,
                'operator' => $response->structured['operator'] ?? null,
                'textual_value' => $response->structured['textual_value'] ?? null,
                'is_abnormal' => $response->structured['is_abnormal'] ?? false,
                'reference_min' => $response->structured['reference_min'] ?? null,
                'reference_max' => $response->structured['reference_max'] ?? null,
                'textual_range' => $response->structured['textual_range'] ?? null,
            ]);
        }

        return $response;
    }
}
