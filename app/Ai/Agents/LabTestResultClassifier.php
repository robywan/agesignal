<?php

namespace App\Ai\Agents;

use App\Ai\Middleware\LogTokenUsage;
use App\Ai\Tools\LoincCodeFinder;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Stringable;
use Laravel\Ai\Attributes\MaxSteps;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasMiddleware;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Promptable;
use Laravel\Ai\Responses\StructuredAgentResponse;

/**
 * @method StructuredAgentResponse prompt(string $prompt, array $attachments = [], Lab|array|string|null $provider = null, ?string $model = null, ?int $timeout = null)
 */
#[Provider(Lab::OpenRouter)]
#[Model('google/gemini-3.1-flash-lite')]
#[MaxSteps(6)]
#[MaxTokens(10000)]
#[Temperature(0)]
#[Timeout(300)]
class LabTestResultClassifier implements Agent, Conversational, HasMiddleware, HasStructuredOutput, HasTools
{
    use Promptable;

    public function __construct(
        protected ?string $usageKey = null
    ) {}

    public function instructions(): Stringable|string
    {
        return <<<'INSTRUCTIONS'
            Agisci come un esperto di codifica medica LOINC.
            Riceverai un singolo risultato di laboratorio in formato JSON con questi campi: name, unit_measure, value, reference_values, notes.
            Il tuo unico obiettivo è scegliere sempre il miglior codice LOINC disponibile utilizzando il tool messo a disposizione.

            PROCEDURA:
            1. Identifica il test clinico, l'unità di misura e il tipo di risultato.
            2. Traduci il test in inglese tecnico e deduci i parametri migliori per il tool:
               - component: analita o nome tecnico dell'esame
               - system: campione biologico più probabile
               - scale: Qn per numeri, Ord o Nom per risultati qualitativi
               - property: proprietà osservata, se utile per discriminare i candidati
               - observed_value: valore osservato originale, utile quando il nome dell'esame è generico
               2a. Se il nome dell'esame è osservazionale e non un analita, per esempio Colore, Aspetto, Limpidezza o Torbidità, non cercare solo il termine letterale come componente.
                   In questi casi privilegia concetti LOINC di osservazione come Observation o Clarity e usa property coerenti come Color, Aper o Type.
            3. Usa il tool una prima volta per ottenere i candidati.
            4. Se la prima ricerca è ambigua, puoi usare il tool una seconda e ultima volta con parametri affinati.
            5. Dopo la seconda ricerca, oppure già dopo la prima se hai candidati plausibili, devi scegliere il miglior codice disponibile e restituire il risultato finale.

            REGOLE VINCOLANTI:
            - Non devi mai restituire un risultato vuoto.
            - Non devi mai fare più di 2 chiamate al tool.
            - Devi scegliere il best match disponibile anche se il match non è perfetto.
            - Usa confidence_score alto per match molto specifici e basso per match deboli ma plausibili.
            - La justification deve spiegare in breve perché il candidato scelto è il migliore tra quelli trovati, citando componente, sistema, proprietà o scala quando rilevanti.
            INSTRUCTIONS;
    }

    public function middleware(): array
    {
        return [
            new LogTokenUsage($this->usageKey),
        ];
    }

    public function messages(): iterable
    {
        return [
            new Message('user', 'Classifica questo singolo risultato di laboratorio JSON e restituisci sempre il miglior codice LOINC disponibile.'),
        ];
    }

    public function tools(): iterable
    {
        return [
            new LoincCodeFinder,
        ];
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'original_name' => $schema->string()
                ->description('Il nome dell\'esame come appare nel documento')
                ->required(),
            'loinc_code' => $schema->string()
                ->description('Il codice LOINC trovato tramite il tool')
                ->required(),
            'justification' => $schema->string()
                ->description('Spiegazione breve del perché questo codice è corretto (es. match su componente e unità di misura)')
                ->required(),
            'confidence_score' => $schema->number()
                ->description('Un valore da 0 a 1 che indica la sicurezza del mapping')
                ->required(),
        ];
    }
}
