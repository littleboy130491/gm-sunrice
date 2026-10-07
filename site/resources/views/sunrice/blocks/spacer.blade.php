@php
    $height = match ($block->size ?? 'medium') {
        'small' => 'h-8',
        'large' => 'h-24',
        'xlarge' => 'h-32',
        default => 'h-16',
    };
@endphp

<div
    @if ($block->anchor) id="{{ $block->anchor }}" @endif
    class="{{ $height }}"
    aria-hidden="true"
></div>
