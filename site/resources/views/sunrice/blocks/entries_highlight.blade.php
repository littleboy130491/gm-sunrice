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
    <div class="mx-auto max-w-6xl">
        @if ($block->heading)
            <h2 class="text-2xl font-bold tracking-tight text-zinc-900 sm:text-3xl dark:text-zinc-100">
                {{ $block->heading }}
            </h2>
        @endif
        @if ($block->text)
            <p class="mt-3 max-w-2xl text-zinc-600 dark:text-zinc-400">{{ $block->text }}</p>
        @endif
        @if ($block->entries)
            <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($block->entries as $entry)
                    @include('blocks.partials.entry-card', ['entry' => $entry])
                @endforeach
            </div>
        @endif
        @if ($block->button?->label && $block->button?->link)
            <div class="mt-8">
                <x-block-button
                    :label="$block->button->label"
                    :link="$block->button->link"
                    :style="$block->button->style ?? 'primary'"
                />
            </div>
        @endif
    </div>
</section>
