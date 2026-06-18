<?php

namespace App\Ai\Agents;

use App\Ai\Middleware\LogTokenUsage;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Stringable;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Attributes\Provider;
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
#[MaxTokens(15000)]
#[Timeout(300)]
class LabTestResultExtractor implements Agent, HasMiddleware, HasStructuredOutput
{
    use Promptable;

    public function __construct(
        protected ?string $usageKey = null
    ) {}

    public function instructions(): Stringable|string
    {
        return <<<'INSTRUCTIONS'
            Riceverai una tabella Markdown contenente risultati di esami di laboratorio.

            Il tuo compito è estrarre ogni analisi presente nella tabella e restituirla come elemento dell'array `results`.

            Per ogni risultato:
            - `name`: il nome dell'esame
            - `value`: il valore osservato
            - `unit_measure`: l'unità di misura
            - `reference_values`: i valori o l'intervallo di riferimento
            - `notes`: annotazioni, flag, commenti o note cliniche presenti nella stessa riga

            Istruzioni operative:
            - Considera solo le righe dati della tabella Markdown.
            - Ignora la riga di intestazione e la riga dei separatori `|---|`.
            - Non dedurre colonne mancanti e non correggere automaticamente valori sospetti.
            - Conserva punteggiatura, simboli, segni `<`, `>`, `<=`, `>=`, asterischi, flag `H/L`, e unità esattamente come appaiono.
            - Se una cella contiene più informazioni utili, mettile nel campo più adatto; usa `notes` per ciò che non rientra chiaramente negli altri campi.
            - Restituisci solo i dati strutturati richiesti, senza testo introduttivo o spiegazioni.

            Obiettivo: ottenere un'estrazione fedele e completa dei risultati dalla tabella Markdown.
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
                ->description('Risultati delle analisi')
                ->items($schema
                    ->object(fn ($schema) => [
                        'name' => $schema->string()
                            ->description('Esame')
                            ->required(),
                        'value' => $schema->string()
                            ->description('Valore Riscontrato')
                            ->required(),
                        'unit_measure' => $schema->string()
                            ->description('Unità di Misura'),
                        'reference_values' => $schema->string()
                            ->description('Valori di Riferimento'),
                        'notes' => $schema->string()
                            ->description('Note dal referto'),
                    ])
                    ->description('Un risultato dettagliato delle analisi')
                    ->required()
                )
                ->required(),
        ];
    }
}
