<?php

namespace App\Actions;

use App\Models\LabTestResult;
use App\Prisma\Tools\SearchLoincCodeTool;
use Illuminate\Support\Collection;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Schema\ArraySchema;
use Prism\Prism\Schema\NumberSchema;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;
use Prism\Prism\Structured\Response;

class ClassifyLabTestAction
{
    public function __invoke(LabTestResult $labTestResult): Response
    {
        $response = Prism::structured()
            ->using(Provider::Gemini, 'gemini-flash-latest')
            ->withMaxTokens(6000)
            ->withClientOptions(['timeout' => 300]) // Adjust request timeout
            ->withClientRetry(2, 1000) // Add automatic retries
            ->withSystemPrompt(<<<'INSTRUCTIONS'
                Agisci come un esperto di codifica medica LOINC. 
                Il tuo unico obiettivo è mappare i nomi degli esami presenti nella tabella Markdown ai codici LOINC ufficiali utilizzando il tool 'search_loinc_code'.

                PROCEDURA:
                1. Identifica l'esame e l'unità di misura (es. Glicemia, mg/dL).
                2. Traduci l'esame in inglese tecnico (es. Glucose).
                3. Usa il tool per cercare i codici candidati nel database.
                4. Seleziona il codice che corrisponde meglio a:
                    - Componente (Analita)
                    - Sistema (es. Blood, Urine, Stool)
                    - Proprietà (es. Massa/Volume vs Numero/Volume)
                5. Nel campo 'justification', spiega brevemente la logica (es: "Selezionato 2339-0 perché corrisponde a Glucosio nel sangue con unità di misura mg/dL").
                INSTRUCTIONS)
                // CONTESTO: [Analisi del Sangue / Analisi delle Feci]
            ->withMaxSteps(10) // Necessario minimo 2 per poter utilizzare il tool
            ->withTools([
                SearchLoincCodeTool::make(),
            ])
            ->withSchema(new ArraySchema(
                name: 'results',
                description: 'Risultati della classificazione degli esami, arricchiti con i codici LOINC',
                items: new ObjectSchema(
                    name: 'result',
                    description: 'Un singolo risultato classificato',
                    properties: [
                        new StringSchema('original_name', 'Il nome dell\'esame come appare nel documento'),
                        new StringSchema('loinc_code', 'Il codice LOINC trovato tramite il tool'),
                        new StringSchema('justification', 'Spiegazione breve del perché questo codice è corretto (es. match su componente e unità di misura)'),
                        new NumberSchema('confidence_score', 'Un valore da 0 a 1 che indica la sicurezza del mapping'),
                    ],
                    requiredFields: ['original_name', 'loinc_code', 'justification']
                )
            ))
            ->withPrompt(Collection::make($labTestResult)
                ->only(['name', 'unit_measure', 'value', 'reference_values', 'notes'])
                ->toJson())
            ->asStructured();

        $labTestResult->aiUsages()->create([
            'provider' => Provider::Gemini,
            'model' => $response->meta->model,
            'description' => 'Classificazione esame',
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
        ]);

        return $response;
    }
}
