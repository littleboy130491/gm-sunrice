@php
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
    <div class="mx-auto max-w-6xl">
        @if ($block->heading)
            <h2 class="mb-8 text-center text-sm font-semibold uppercase tracking-widest text-zinc-500 dark:text-zinc-400">
                {{ $block->heading }}
            </h2>
        @endif

        @if ($block->logos)
            <div class="flex flex-wrap items-center justify-center gap-8 sm:gap-12">
                @foreach ($block->logos as $logo)
                    <img
                        src="{{ $logo->url() }}"
                        alt="{{ $logo->alt ?? $logo->title ?? '' }}"
                        class="h-10 w-auto max-w-[140px] object-contain opacity-70 grayscale transition hover:opacity-100 hover:grayscale-0 sm:h-12"
                    >
                @endforeach
            </div>
        @endif
    </div>
</section>
