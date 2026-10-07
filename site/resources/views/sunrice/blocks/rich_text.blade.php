@php
    $maxWidth = match ($block->width ?? 'default') {
        'narrow' => 'max-w-2xl',
        'wide' => 'max-w-5xl',
        'full' => 'max-w-none',
        default => 'max-w-3xl',
    };
    $sectionBg = match ($block->background ?? 'default') {
        'muted' => 'bg-zinc-50 dark:bg-zinc-900',
        'dark' => 'bg-zinc-900',
        default => 'bg-white dark:bg-zinc-950',
    };
@endphp

<section
    @if ($block->anchor) id="{{ $block->anchor }}" @endif
    class="px-4 py-12 sm:px-6 lg:py-16 {{ $sectionBg }}"
>
    <div class="mx-auto {{ $maxWidth }}">
        <div class="prose prose-zinc dark:prose-invert max-w-none">
            {!! $block->content !!}
        </div>
    </div>
</section>
