@props(['status' => 'ok', 'label' => 'Normale'])

@php
    $classes = match ($status) {
        'ok' => 'bg-status-ok-bg text-status-ok-text',
        'warn' => 'bg-status-warn-bg text-status-warn-text',
        'low', 'critical' => 'bg-status-low-bg text-status-low-text',
        default => 'bg-gray-100 text-gray-600',
    };
@endphp

<span {{ $attributes->class(['inline-flex items-center rounded-full px-2 font-medium leading-none', $classes]) }}
    style="font-size: 9px; padding-top: 3px; padding-bottom: 3px;">
    {{ $label }}
</span>
