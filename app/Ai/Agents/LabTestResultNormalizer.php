<?php

namespace App\Ai\Agents;

use App\Ai\Middleware\LogTokenUsage;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Stringable;
use Laravel\Ai\Attributes\MaxSteps;
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

/**
 * @method StructuredAgentResponse prompt(string $prompt, array $attachments = [], Lab|array|string|null $provider = null, ?string $model = null, ?int $timeout = null)
 */
#[Provider(Lab::OpenRouter)]
#[Model('google/gemini-3.1-flash-lite')]
#[MaxSteps(10)]
#[MaxTokens(4000)]
#[Timeout(300)]
class LabTestResultNormalizer implements Agent, HasMiddleware, HasStructuredOutput
{
    use Promptable;

    public function __construct(
        protected ?string $usageKey = null
    ) {}

    public function instructions(): Stringable|string
    {
        return <<<'INSTRUCTIONS'
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
            INSTRUCTIONS;
    }

    public function middleware(): array
    {
        return [
            new LogTokenUsage($this->usageKey),
        ];
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'numeric_value' => $schema->number()
                ->description('Il valore numerico estratto, o null se non applicabile')
                ->nullable()
                ->required(),
            'operator' => $schema->string()
                ->enum(['<', '>', '<=', '>=', '='])
                ->description('L\'operatore associato al valore numerico, se presente')
                ->required(),
            'textual_value' => $schema->string()
                ->description('Il valore testuale estratto, o null se non applicabile')
                ->nullable()
                ->required(),
            'is_abnormal' => $schema->boolean()
                ->description('Indica se il risultato è anomalo')
                ->nullable()
                ->required(),
            'reference_min' => $schema->number()
                ->description('Il valore minimo di riferimento, o null se non applicabile')
                ->nullable()
                ->required(),
            'reference_max' => $schema->number()
                ->description('Il valore massimo di riferimento, o null se non applicabile')
                ->nullable()
                ->required(),
            'textual_range' => $schema->string()
                ->description('Il range testuale, o null se non applicabile')
                ->nullable()
                ->required(),
        ];
    }
}
