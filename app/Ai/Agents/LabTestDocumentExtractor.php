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
#[Provider(Lab::Gemini)]
#[Model('gemini-3.1-flash-lite-preview')]
#[Temperature(0)]
#[MaxTokens(32000)]
#[Timeout(300)]
class LabTestDocumentExtractor implements Agent, HasMiddleware, HasStructuredOutput
{
    use Promptable;

    public function __construct(
        protected ?string $usageKey = null
    ) {}

    public function instructions(): Stringable|string
    {
        return <<<'INSTRUCTIONS'
            Riceverai in allegato un referto di esame di laboratorio in formato PDF.

            Il tuo compito è estrarre ogni analisi presente nel documento e restituirla come elemento dell'array `results`.

            Per ogni risultato:
            - `name`: il nome dell'esame
            - `value`: il valore osservato
            - `unit_measure`: l'unità di misura (null se assente nel documento)
            - `reference_values`: i valori o l'intervallo di riferimento (null solo se non trovato in nessuna parte del documento)
            - `notes`: annotazioni, flag, commenti o note cliniche riferiti a quella specifica analisi (null se assente)

            Regole per il campo `reference_values` (campo critico — cerca con attenzione):
            - Cerca in tutte le posizioni del documento: colonne intitolate "Rif.", "V.N.", "Val. Norma", "Range", "Valori di Riferimento", "Valori Normali", "Limiti", "Norma", "Riferimento"; valori tra parentesi tonde o quadre sulla stessa riga; testo a destra del valore nella stessa riga; note a piè di pagina associate a quell'analisi.
            - Accetta qualsiasi formato: "12.0-16.0", "60 - 110", "< 50", "> 10", "Assente", "Negativo", range con operatori o testo libero.
            - Se il range compare in una riga di intestazione condivisa tra più analisi (es. un'unica riga "Rif: 60-110" per un gruppo di analisi), copialo per ciascuna analisi del gruppo.
            - Restituisci null solo se, dopo aver cercato in tutto il documento, non trovi alcun valore di riferimento per quell'analisi specifica.

            Istruzioni operative:
            - Analizza l'intero documento, incluse tutte le pagine.
            - Ogni analisi deve apparire una sola volta; se la stessa analisi compare più volte (es. intestazioni ripetute tra pagine), inseriscila una sola volta.
            - Non dedurre dati mancanti e non correggere valori sospetti.
            - Conserva punteggiatura, simboli, segni `<`, `>`, `<=`, `>=`, asterischi, flag `H/L` e unità esattamente come appaiono nel documento.
            - IMPORTANTE: restituisci SEMPRE tutte e cinque le chiavi (`name`, `value`, `unit_measure`, `reference_values`, `notes`) per ogni risultato. Usa null esplicitamente quando il dato non è presente. Non omettere mai una chiave.
            - Usa il campo `notes` per flag, annotazioni cliniche o qualunque informazione testuale relativa a quella riga che non rientra negli altri campi.
            - Ignora intestazioni, piè di pagina, dati anagrafici del paziente e informazioni amministrative del laboratorio.
            - Restituisci solo i dati strutturati richiesti, senza testo introduttivo o spiegazioni.

            Obiettivo: ottenere un'estrazione fedele, completa e deduplicata di tutti i risultati presenti nel referto PDF, con particolare attenzione a non perdere i valori di riferimento.
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
                            ->required()
                            ->max(255),
                        'value' => $schema->string()
                            ->description('Valore Riscontrato')
                            ->required()
                            ->max(255),
                        'unit_measure' => $schema->string()
                            ->description('Unità di Misura')
                            ->max(255)
                            ->nullable()
                            ->required(),
                        'reference_values' => $schema->string()
                            ->description('Valori di Riferimento')
                            ->max(255)
                            ->nullable()
                            ->required(),
                        'notes' => $schema->string()
                            ->description('Note dal referto')
                            ->nullable()
                            ->required(),
                    ])
                    ->description('Un risultato dettagliato delle analisi')
                    ->required()
                )
                ->required(),
        ];
    }
}
