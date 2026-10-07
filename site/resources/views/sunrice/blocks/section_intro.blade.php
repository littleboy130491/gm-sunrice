@php
    $sectionBg = match ($block->background ?? 'default') {
        'muted' => 'bg-zinc-50 dark:bg-zinc-900',
        'dark' => 'bg-zinc-900 text-zinc-100',
        default => 'bg-white dark:bg-zinc-950',
    };
@endphp

<section
    @if ($block->anchor) id="{{ $block->anchor }}" @endif
    class="px-4 py-12 sm:px-6 lg:py-16 {{ $sectionBg }}"
>
    <div class="mx-auto max-w-3xl text-center">
        @if ($block->heading)
            <h2 class="text-2xl font-bold tracking-tight text-zinc-900 sm:text-3xl dark:text-zinc-100">
                {{ $block->heading }}
            </h2>
        @endif
        @if ($block->text)
            <p class="mt-4 text-zinc-600 dark:text-zinc-400">
                {{ $block->text }}
            </p>
        @endif
    </div>
</section>
