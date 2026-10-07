@php
    $style = $block->style ?? 'line';
@endphp

<div
    @if ($block->anchor) id="{{ $block->anchor }}" @endif
    class="mx-auto max-w-6xl px-4 sm:px-6"
>
    @if ($style === 'dots')
        <hr class="border-0 border-t border-dotted border-zinc-300 dark:border-zinc-700">
    @elseif ($style === 'space')
        <div class="h-8" aria-hidden="true"></div>
    @else
        <hr class="border-zinc-200 dark:border-zinc-800">
    @endif
</div>
