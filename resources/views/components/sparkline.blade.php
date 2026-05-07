@props([
    'points',
    'referenceMin' => null,
    'referenceMax' => null,
    'width' => 160,
    'height' => 44,
])

@php
    $padding = 4;
    $count = is_countable($points) ? count($points) : 0;

    if ($count < 2) {
        // Single point or empty: render a flat dash.
        $fallback = '<line x1="'.$padding.'" y1="'.($height / 2).'" x2="'.($width - $padding).'" y2="'.($height / 2).'" stroke="currentColor" stroke-width="1" class="text-gray-200"/>';
    } else {
        $values = collect($points)->pluck('value')->map(fn ($v) => (float) $v);
        $minY = (float) $values->min();
        $maxY = (float) $values->max();

        if (is_numeric($referenceMin)) {
            $minY = min($minY, (float) $referenceMin);
        }
        if (is_numeric($referenceMax)) {
            $maxY = max($maxY, (float) $referenceMax);
        }

        $padY = max(($maxY - $minY) * 0.12, 0.0001);
        $minY -= $padY;
        $maxY += $padY;
        $rangeY = max($maxY - $minY, 0.0001);

        $project = function ($i, $v) use ($count, $width, $height, $padding, $minY, $rangeY) {
            $x = $padding + ($i / ($count - 1)) * ($width - 2 * $padding);
            $y = $height - $padding - (($v - $minY) / $rangeY) * ($height - 2 * $padding);
            return [$x, $y];
        };

        // Reference range band (green tint) when both bounds are numeric
        $bandSvg = '';
        if (is_numeric($referenceMin) && is_numeric($referenceMax)) {
            [$_, $yMax] = $project(0, (float) $referenceMin);
            [$__, $yMin] = $project(0, (float) $referenceMax);
            $bandY = min($yMin, $yMax);
            $bandH = abs($yMax - $yMin);
            $bandSvg = '<rect x="'.$padding.'" y="'.number_format($bandY, 2).'" width="'.($width - 2 * $padding).'" height="'.number_format($bandH, 2).'" class="fill-status-ok-bg" opacity="0.55"/>';
        }

        $pathParts = [];
        $circleSvg = '';
        foreach ($points as $i => $p) {
            [$x, $y] = $project($i, (float) $p->value);
            $pathParts[] = ($i === 0 ? 'M' : 'L').number_format($x, 2).' '.number_format($y, 2);
            $fill = match ($p->severity ?? 'ok') {
                'critical' => 'fill-status-low',
                'warn' => 'fill-status-warn',
                default => 'fill-status-ok',
            };
            $circleSvg .= '<circle cx="'.number_format($x, 2).'" cy="'.number_format($y, 2).'" r="2.2" class="'.$fill.'"/>';
        }
        $path = implode(' ', $pathParts);
        $fallback = $bandSvg.'<path d="'.$path.'" fill="none" stroke="currentColor" stroke-width="1.4" class="text-gray-400"/>'.$circleSvg;
    }
@endphp

<svg viewBox="0 0 {{ $width }} {{ $height }}" {{ $attributes->merge(['class' => 'block']) }} preserveAspectRatio="none">
    {!! $fallback !!}
</svg>
