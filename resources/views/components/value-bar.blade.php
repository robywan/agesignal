@props([
    'name',
    'value',
    'unit' => null,
    'min' => null,
    'max' => null,
    'textualRange' => null,
    'severity' => 'ok',
    'statusLabel' => null,
])

@php
    $fillColor = match ($severity) {
        'ok' => 'bg-status-ok',
        'warn' => 'bg-status-warn',
        'critical' => 'bg-status-low',
        default => 'bg-gray-400',
    };

    $widthPercent = 0;
    if (is_numeric($value) && is_numeric($min) && is_numeric($max) && (float) $max > (float) $min) {
        $span = (float) $max - (float) $min;
        $relative = ((float) $value - (float) $min) / $span;
        $widthPercent = max(0, min(100, ($relative + 0.1) * 80));
    } elseif (is_numeric($value)) {
        $widthPercent = $severity === 'ok' ? 60 : 90;
    }

    $rangeText = (is_numeric($min) && is_numeric($max))
        ? sprintf('%s–%s%s', rtrim(rtrim((string) $min, '0'), '.'), rtrim(rtrim((string) $max, '0'), '.'), $unit ? ' '.$unit : '')
        : $textualRange;
@endphp

<div class="flex flex-col gap-1 py-2">
    <div class="flex items-center gap-3">
        <div class="w-32 shrink-0 text-label text-text-primary truncate" title="{{ $name }}">{{ $name }}</div>

        <div class="relative h-1.5 flex-1 rounded-full bg-gray-100 overflow-hidden">
            <div class="absolute inset-y-0 left-0 {{ $fillColor }} rounded-full transition-[width]"
                 style="width: {{ $widthPercent }}%"></div>
        </div>

        <div class="w-20 shrink-0 text-right text-label text-text-primary tabular-nums">
            {{ $value }}@if ($unit)<span class="text-caption text-text-secondary"> {{ $unit }}</span>@endif
        </div>

        @if ($statusLabel)
            <x-status-badge :status="$severity" :label="$statusLabel" />
        @endif
    </div>

    @if ($rangeText)
        <div class="ml-32 pl-3 text-caption text-text-secondary">
            {{ __('Range') }}: {{ $rangeText }}
        </div>
    @endif
</div>
