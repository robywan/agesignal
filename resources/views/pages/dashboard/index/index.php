<?php

use App\Models\AiUsage;
use App\Models\LabTestDocument;
use App\Models\LabTestResult;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Facades\Prism;
use Prism\Prism\ValueObjects\Messages\AssistantMessage;
use Prism\Prism\ValueObjects\Messages\UserMessage;

new #[Title('Dashboard')] class extends Component
{
    public ?int $selectedDocumentId = null;

    public int $comparePage = 0;

    public const COMPARE_PAGE_SIZE = 10;

    /** @var list<array{role: 'user'|'assistant', content: string}> */
    public array $aiMessages = [];

    public string $aiInput = '';

    public ?string $aiError = null;

    #[Computed]
    public function user(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }

    #[Computed]
    public function activeDocument(): ?LabTestDocument
    {
        $query = $this->user
            ->labTestDocuments()
            ->with(['media', 'tables.results.loincCoreEntry']);

        if ($this->selectedDocumentId !== null) {
            return $query->whereKey($this->selectedDocumentId)->first()
                ?? $query->latest('test_date')->first();
        }

        return $query->latest('test_date')->first();
    }

    public function selectDocument(int $documentId): void
    {
        $exists = $this->user->labTestDocuments()->whereKey($documentId)->exists();

        if (! $exists) {
            return;
        }

        $this->selectedDocumentId = $documentId;
        $this->comparePage = 0;
        $this->aiMessages = [];
        $this->aiError = null;
    }

    /**
     * @return BaseCollection<int, LabTestResult>
     */
    #[Computed]
    public function results(): BaseCollection
    {
        return $this->activeDocument
            ? $this->activeDocument->tables->flatMap->results->values()
            : collect();
    }

    /**
     * @return BaseCollection<int, object{result: LabTestResult, severity: string, deviation: float, label: string}>
     */
    #[Computed]
    public function processedResults(): BaseCollection
    {
        return $this->results->map(fn (LabTestResult $r) => (object) [
            'result' => $r,
            'severity' => $this->severityFor($r),
            'deviation' => $this->deviationFor($r),
            'label' => $this->labelFor($r),
        ]);
    }

    /**
     * @return array{ok: int, warn: int, critical: int}
     */
    #[Computed]
    public function statusCounts(): array
    {
        $counts = ['ok' => 0, 'warn' => 0, 'critical' => 0];

        foreach ($this->processedResults as $row) {
            $counts[$row->severity]++;
        }

        return $counts;
    }

    #[Computed]
    public function dominantStatus(): string
    {
        $counts = $this->statusCounts;

        if ($counts['critical'] > 0) {
            return 'critical';
        }

        if ($counts['warn'] > 0) {
            return 'warn';
        }

        return 'ok';
    }

    /**
     * @return BaseCollection<int, object>
     */
    #[Computed]
    public function outOfRange(): BaseCollection
    {
        return $this->processedResults
            ->filter(fn ($row) => $row->severity !== 'ok')
            ->sortByDesc('deviation')
            ->values();
    }

    /**
     * @return BaseCollection<string, BaseCollection<int, object>>
     */
    #[Computed]
    public function resultsByCategory(): BaseCollection
    {
        return $this->processedResults
            ->groupBy(fn ($row) => $row->result->loincCoreEntry?->class ?? __('Altri parametri'))
            ->sortKeys();
    }

    /**
     * @return Collection<int, LabTestDocument>
     */
    #[Computed]
    public function documents(): Collection
    {
        return $this->user
            ->labTestDocuments()
            ->latest('test_date')
            ->get();
    }

    #[Computed]
    public function previousDocument(): ?LabTestDocument
    {
        if (! $this->activeDocument) {
            return null;
        }

        return $this->user
            ->labTestDocuments()
            ->where('id', '!=', $this->activeDocument->id)
            ->where(function ($q) {
                $q->where('test_date', '<', $this->activeDocument->test_date)
                    ->orWhere(function ($q2) {
                        $q2->where('test_date', '=', $this->activeDocument->test_date)
                            ->where('id', '<', $this->activeDocument->id);
                    });
            })
            ->with('tables.results')
            ->latest('test_date')
            ->first();
    }

    /**
     * Pairs each numeric parameter of the active referto with its match in the previous one.
     * Match by loinc_num when both have it, else by normalized name.
     *
     * @return BaseCollection<int, object{label: string, unit: ?string, current: float, previous: float, deltaAbs: float, deltaPct: ?float, severity: string}>
     */
    #[Computed]
    public function comparisonRows(): BaseCollection
    {
        $previous = $this->previousDocument;

        if (! $previous) {
            return collect();
        }

        $previousResults = $previous->tables->flatMap->results;

        $byLoinc = $previousResults
            ->filter(fn (LabTestResult $r) => $r->loinc_num !== null && $r->numeric_value !== null)
            ->keyBy('loinc_num');

        // Key by name+unit so that the same parameter expressed in two units
        // (e.g. HbA1c in % and mmol/mol) doesn't collapse onto a single row.
        $byNameUnit = $previousResults
            ->filter(fn (LabTestResult $r) => $r->numeric_value !== null)
            ->keyBy(fn (LabTestResult $r) => $this->matchKey($r->name, $r->unit_measure));

        return $this->processedResults
            ->filter(fn ($row) => $row->result->numeric_value !== null)
            ->map(function ($row) use ($byLoinc, $byNameUnit) {
                $current = $row->result;
                $previous = $current->loinc_num !== null
                    ? ($byLoinc[$current->loinc_num] ?? null)
                    : null;

                if ($previous === null) {
                    $previous = $byNameUnit[$this->matchKey($current->name, $current->unit_measure)] ?? null;
                }

                if ($previous === null) {
                    return null;
                }

                $currentValue = (float) $current->numeric_value;
                $previousValue = (float) $previous->numeric_value;
                $deltaAbs = $currentValue - $previousValue;
                $deltaPct = abs($previousValue) > 1e-9
                    ? ($deltaAbs / $previousValue) * 100
                    : null;

                return (object) [
                    'label' => $row->label,
                    'unit' => $current->unit_measure,
                    'current' => $currentValue,
                    'previous' => $previousValue,
                    'deltaAbs' => $deltaAbs,
                    'deltaPct' => $deltaPct,
                    'severity' => $row->severity,
                ];
            })
            ->filter()
            ->sortByDesc(fn ($r) => $r->deltaPct !== null ? abs($r->deltaPct) : 0)
            ->values();
    }

    private function matchKey(?string $name, ?string $unit): string
    {
        return mb_strtolower(trim((string) $name)).'|'.mb_strtolower(trim((string) $unit));
    }

    #[Computed]
    public function comparePages(): int
    {
        return (int) max(1, ceil($this->comparisonRows->count() / self::COMPARE_PAGE_SIZE));
    }

    /**
     * @return BaseCollection<int, object>
     */
    #[Computed]
    public function paginatedComparison(): BaseCollection
    {
        return $this->comparisonRows
            ->slice($this->comparePage * self::COMPARE_PAGE_SIZE, self::COMPARE_PAGE_SIZE)
            ->values();
    }

    public function nextComparePage(): void
    {
        if ($this->comparePage < $this->comparePages - 1) {
            $this->comparePage++;
        }
    }

    public function previousComparePage(): void
    {
        if ($this->comparePage > 0) {
            $this->comparePage--;
        }
    }

    private function severityFor(LabTestResult $result): string
    {
        if ($result->numeric_value === null) {
            return $result->is_abnormal ? 'warn' : 'ok';
        }

        $value = (float) $result->numeric_value;
        $min = $result->reference_min !== null ? (float) $result->reference_min : null;
        $max = $result->reference_max !== null ? (float) $result->reference_max : null;

        if ($min === null && $max === null) {
            return $result->is_abnormal ? 'warn' : 'ok';
        }

        $belowMin = $min !== null && $value < $min;
        $aboveMax = $max !== null && $value > $max;

        if (! $belowMin && ! $aboveMax) {
            return 'ok';
        }

        $bound = $belowMin ? $min : $max;

        if ($bound === null || abs($bound) < 1e-9) {
            return 'warn';
        }

        return abs($value - $bound) / abs($bound) > 0.25 ? 'critical' : 'warn';
    }

    private function deviationFor(LabTestResult $result): float
    {
        if ($result->numeric_value === null) {
            return 0.0;
        }

        $value = (float) $result->numeric_value;
        $min = $result->reference_min !== null ? (float) $result->reference_min : null;
        $max = $result->reference_max !== null ? (float) $result->reference_max : null;

        if ($min !== null && $value < $min) {
            return $min - $value;
        }

        if ($max !== null && $value > $max) {
            return $value - $max;
        }

        return 0.0;
    }

    private function labelFor(LabTestResult $result): string
    {
        return $result->loincCoreEntry?->long_common_name
            ?? $result->loincCoreEntry?->short_name
            ?? $result->name;
    }

    public function sendAiMessage(): void
    {
        $this->aiError = null;

        $prompt = trim($this->aiInput);

        if ($prompt === '') {
            return;
        }

        if (! $this->activeDocument) {
            $this->aiError = __('Carica un referto per usare l\'assistente.');

            return;
        }

        $this->aiMessages[] = ['role' => 'user', 'content' => $prompt];
        $this->aiInput = '';

        try {
            $messages = collect($this->aiMessages)
                ->map(fn (array $m) => $m['role'] === 'user'
                    ? new UserMessage($m['content'])
                    : new AssistantMessage($m['content']))
                ->all();

            $response = Prism::text()
                ->using(Provider::Gemini, 'gemini-2.5-flash')
                ->withSystemPrompt($this->aiSystemPrompt())
                ->withMessages($messages)
                ->withMaxTokens(800)
                ->withClientOptions(['timeout' => 60])
                ->withClientRetry(1, 500)
                ->asText();

            $this->aiMessages[] = ['role' => 'assistant', 'content' => $response->text];

            AiUsage::create([
                'subject_type' => 'lab_test_document',
                'subject_id' => $this->activeDocument->id,
                'provider' => Provider::Gemini,
                'model' => $response->meta->model,
                'description' => 'Chat AI Care dashboard',
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
        } catch (Throwable $e) {
            array_pop($this->aiMessages);
            $this->aiError = __('Non sono riuscito a rispondere. Riprova tra poco.');
            report($e);
        }
    }

    private function aiSystemPrompt(): string
    {
        $rows = $this->processedResults
            ->map(function ($row) {
                $r = $row->result;
                $value = $r->numeric_value ?? $r->textual_value ?? $r->value;
                $unit = $r->unit_measure ? ' '.$r->unit_measure : '';
                $range = ($r->reference_min !== null && $r->reference_max !== null)
                    ? sprintf('range %s-%s', $r->reference_min, $r->reference_max)
                    : ($r->textual_range ?? 'range non disponibile');
                $status = match ($row->severity) {
                    'ok' => 'nella norma',
                    'warn' => 'fuori range (lieve)',
                    'critical' => 'fuori range (significativo)',
                    default => 'sconosciuto',
                };

                return sprintf('- %s: %s%s, %s, %s', $row->label, $value, $unit, $range, $status);
            })
            ->implode("\n");

        $date = $this->activeDocument?->test_date?->format('d/m/Y') ?? 'sconosciuta';

        return <<<PROMPT
            Sei un assistente sanitario per pazienti italiani. Spieghi i risultati delle analisi del sangue in linguaggio semplice e rassicurante.

            Regole:
            - Rispondi sempre in italiano.
            - Non fornire diagnosi: per dubbi clinici suggerisci il confronto col medico curante.
            - Evita gergo medico: niente "ipercolesterolemia", "iperglicemia", ecc.; usa termini comuni.
            - Tono caldo, pratico, conciso. Massimo 4-6 frasi a meno che l'utente non chieda dettagli.
            - Se la domanda non riguarda i parametri del referto, riportala gentilmente sull'argomento.
            - Niente disclaimer lunghi: una sola frase a fine risposta se utile.

            Contesto del referto del paziente (data {$date}):
            {$rows}
            PROMPT;
    }

    public function bmiSeverity(): ?string
    {
        $bmi = $this->user->bmi;

        if ($bmi === null) {
            return null;
        }

        if ($bmi >= 18.5 && $bmi <= 24.9) {
            return 'ok';
        }

        if (($bmi >= 17 && $bmi < 18.5) || ($bmi > 24.9 && $bmi < 30)) {
            return 'warn';
        }

        return 'critical';
    }

    public function bmiLabel(): ?string
    {
        return match ($this->bmiSeverity()) {
            'ok' => __('Normopeso'),
            'warn' => __('Da monitorare'),
            'critical' => __('Fuori range'),
            default => null,
        };
    }
};
