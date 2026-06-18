<?php

namespace App\Ai\Agents;

use App\Ai\Middleware\LogTokenUsage;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Stringable;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasMiddleware;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;
use Laravel\Ai\Responses\StructuredAgentResponse;
use Override;

/**
 * @method StructuredAgentResponse prompt(string $prompt, array $attachments = [], Lab|array|string|null $provider = null, ?string $model = null, ?int $timeout = null)
 */
#[Provider(Lab::OpenRouter)]
#[Model('google/gemini-3.1-flash-lite')]
#[Temperature(0)]
#[MaxTokens(8000)]
#[Timeout(120)]
class LabTestReferenceValuesExtractor implements Agent, HasMiddleware, HasStructuredOutput
{
    use Promptable;

    public function __construct(
        protected ?string $usageKey = null
    ) {}

    public function instructions(): Stringable|string
    {
        return <<<'INSTRUCTIONS'
            Riceverai un referto di laboratorio in formato PDF e un elenco di nomi di esami.

            Il tuo unico compito è trovare i valori di riferimento (range normali) per ciascuno degli esami indicati.

            Per ogni esame nell'elenco:
            - `name`: il nome dell'esame, riportalo esattamente come fornito
            - `reference_values`: il valore o l'intervallo di riferimento trovato nel documento (null se non presente)

            Dove cercare i valori di riferimento:
            - Colonne con intestazione "Rif.", "V.N.", "Val. Norma", "Range", "Valori di Riferimento", "Valori Normali", "Limiti", "Norma", "Riferimento"
            - Valori tra parentesi tonde o quadre sulla stessa riga del valore dell'esame
            - Testo a destra del valore nella stessa riga
            - Note a piè di pagina associate a quell'analisi

            Accetta qualsiasi formato: "12.0-16.0", "60 - 110", "< 50", "> 10", "Assente", "Negativo", oppure range con testo libero.

            IMPORTANTE:
            - Restituisci un elemento per ogni esame ricevuto, nell'ordine in cui li hai ricevuti.
            - Usa null per `reference_values` se, dopo aver cercato in tutto il documento, non trovi alcun valore di riferimento per quell'analisi.
            - Non dedurre, non inventare e non correggere valori.
            - Conserva esattamente i simboli e il formato originale del documento.
            - Restituisci SEMPRE entrambe le chiavi (`name`, `reference_values`) per ogni elemento, anche quando `reference_values` è null.
            INSTRUCTIONS;
    }

    public function middleware(): array
    {
        return [
            new LogTokenUsage($this->usageKey),
        ];
    }

    #[Override]
    public function schema(JsonSchema $schema): array
    {
        return [
            'results' => $schema->array()
                ->description('Valori di riferimento recuperati')
                ->items($schema
                    ->object(fn ($schema) => [
                        'name' => $schema->string()
                            ->description('Nome dell\'esame')
                            ->required(),
                        'reference_values' => $schema->string()
                            ->description('Valori di riferimento')
                            ->max(255)
                            ->nullable()
                            ->required(),
                    ])
                    ->description('Valore di riferimento per un singolo esame')
                    ->required()
                )
                ->required(),
        ];
    }
}
