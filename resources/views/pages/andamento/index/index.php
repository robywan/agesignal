<?php

use App\Models\LabTestDocument;
use App\Models\LabTestResult;
use App\Models\User;
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Andamento storico')] class extends Component
{
    public string $filter = 'all';

    #[Computed]
    public function user(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }

    /**
     * @return BaseCollection<int, LabTestDocument>
     */
    #[Computed]
    public function documents(): BaseCollection
    {
        return $this->user
            ->labTestDocuments()
            ->whereNotNull('test_date')
            ->with('tables.results.loincCoreEntry')
            ->orderBy('test_date')
            ->get();
    }

    /**
     * One trend object per parameter, computed across all the user's referti.
     *
     * @return BaseCollection<int, object>
     */
    #[Computed]
    public function trends(): BaseCollection
    {
        $documents = $this->documents;

        if ($documents->count() < 2) {
            return collect();
        }

        $allPoints = $documents->flatMap(fn (LabTestDocument $d) => $d->tables->flatMap->results
            ->filter(fn (LabTestResult $r) => $r->numeric_value !== null)
            ->map(fn (LabTestResult $r) => (object) [
                'result' => $r,
                'date' => $d->test_date,
            ])
        );

        $grouped = $allPoints->groupBy(function (object $p) {
            $r = $p->result;

            return $r->loinc_num ?? $this->matchKey($r->name, $r->unit_measure);
        });

        return $grouped
            ->filter(fn (BaseCollection $points) => $points->count() >= 2)
            ->map(fn (BaseCollection $points, $key) => $this->buildTrend((string) $key, $points->sortBy('date')->values()))
            ->values();
    }

    /**
     * @return BaseCollection<int, object>
     */
    #[Computed]
    public function filteredTrends(): BaseCollection
    {
        return $this->trends
            ->filter(fn ($t) => match ($this->filter) {
                'rising' => $t->monotonic && $t->direction === 'up',
                'falling' => $t->monotonic && $t->direction === 'down',
                'abnormal' => $t->abnormal,
                'stable' => ! $t->monotonic && abs($t->relativeSlope) < 0.005,
                default => true,
            })
            ->sortByDesc(fn ($t) => ($t->abnormal ? 1000 : 0) + abs($t->relativeSlope) * 100)
            ->values();
    }

    /**
     * @return array{all: int, rising: int, falling: int, abnormal: int, stable: int}
     */
    #[Computed]
    public function counts(): array
    {
        $trends = $this->trends;

        return [
            'all' => $trends->count(),
            'rising' => $trends->filter(fn ($t) => $t->monotonic && $t->direction === 'up')->count(),
            'falling' => $trends->filter(fn ($t) => $t->monotonic && $t->direction === 'down')->count(),
            'abnormal' => $trends->filter(fn ($t) => $t->abnormal)->count(),
            'stable' => $trends->filter(fn ($t) => ! $t->monotonic && abs($t->relativeSlope) < 0.005)->count(),
        ];
    }

    public function setFilter(string $filter): void
    {
        $this->filter = $filter;
    }

    private function buildTrend(string $key, BaseCollection $points): object
    {
        $first = $points->first();
        $last = $points->last();
        $sample = $first->result;

        $values = $points->map(fn ($p) => (float) $p->result->numeric_value);
        $n = $values->count();

        // Linear regression slope (x = ordinal index 0..n-1)
        $xMean = ($n - 1) / 2;
        $yMean = $values->avg();
        $num = 0;
        $den = 0;

        foreach ($values as $i => $v) {
            $num += ($i - $xMean) * ($v - $yMean);
            $den += ($i - $xMean) ** 2;
        }

        $slope = $den > 0 ? $num / $den : 0;
        $relativeSlope = abs($yMean) > 1e-9 ? $slope / $yMean : 0;

        // Direction consistency
        $upMoves = 0;
        $downMoves = 0;

        for ($i = 1; $i < $n; $i++) {
            $diff = $values[$i] - $values[$i - 1];

            if ($diff > 0) {
                $upMoves++;
            } elseif ($diff < 0) {
                $downMoves++;
            }
        }

        $monotonicityScore = $n > 1 ? max($upMoves, $downMoves) / ($n - 1) : 0;
        $direction = $upMoves > $downMoves ? 'up' : ($downMoves > $upMoves ? 'down' : 'flat');
        $isMonotonic = $monotonicityScore >= 0.8 && abs($relativeSlope) > 0.005;

        $firstValue = (float) $first->result->numeric_value;
        $lastValue = (float) $last->result->numeric_value;
        $deltaPctTotal = abs($firstValue) > 1e-9
            ? (($lastValue - $firstValue) / $firstValue) * 100
            : null;

        $latestSeverity = $this->severityFor($last->result);
        $isAbnormal = $latestSeverity !== 'ok';

        $pointsWithSeverity = $points->map(fn ($p) => (object) [
            'date' => $p->date,
            'value' => (float) $p->result->numeric_value,
            'severity' => $this->severityFor($p->result),
        ]);

        $trendLabel = match (true) {
            $isMonotonic && $direction === 'up' => __('Costante in rialzo'),
            $isMonotonic && $direction === 'down' => __('Costante in discesa'),
            abs($relativeSlope) < 0.005 => __('Stabile'),
            $relativeSlope > 0 => __('In rialzo'),
            $relativeSlope < 0 => __('In discesa'),
            default => __('Stabile'),
        };

        return (object) [
            'key' => $key,
            'label' => $sample->loincCoreEntry?->long_common_name ?? $sample->loincCoreEntry?->short_name ?? $sample->name,
            'unit' => $sample->unit_measure,
            'referenceMin' => $sample->reference_min !== null ? (float) $sample->reference_min : null,
            'referenceMax' => $sample->reference_max !== null ? (float) $sample->reference_max : null,
            'points' => $pointsWithSeverity,
            'firstValue' => $firstValue,
            'lastValue' => $lastValue,
            'minValue' => (float) $values->min(),
            'maxValue' => (float) $values->max(),
            'slope' => $slope,
            'relativeSlope' => $relativeSlope,
            'direction' => $direction,
            'monotonic' => $isMonotonic,
            'deltaPctTotal' => $deltaPctTotal,
            'abnormal' => $isAbnormal,
            'latestSeverity' => $latestSeverity,
            'trendLabel' => $trendLabel,
        ];
    }

    private function severityFor(LabTestResult $r): string
    {
        if ($r->numeric_value === null) {
            return $r->is_abnormal ? 'warn' : 'ok';
        }

        $value = (float) $r->numeric_value;
        $min = $r->reference_min !== null ? (float) $r->reference_min : null;
        $max = $r->reference_max !== null ? (float) $r->reference_max : null;

        if ($min === null && $max === null) {
            return $r->is_abnormal ? 'warn' : 'ok';
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

    private function matchKey(?string $name, ?string $unit): string
    {
        return mb_strtolower(trim((string) $name)).'|'.mb_strtolower(trim((string) $unit));
    }
};
