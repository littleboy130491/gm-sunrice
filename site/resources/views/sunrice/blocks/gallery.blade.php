@php
    $cols = (int) ($block->columns ?? 3);
    $gridClass = match ($cols) {
        2 => 'sm:grid-cols-2',
        4 => 'sm:grid-cols-2 lg:grid-cols-4',
        default => 'sm:grid-cols-2 lg:grid-cols-3',
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
    <div class="mx-auto max-w-6xl">
        @if ($block->heading)
            <h2 class="mb-8 text-2xl font-bold tracking-tight text-zinc-900 sm:text-3xl dark:text-zinc-100">
                {{ $block->heading }}
            </h2>
        @endif

        @if ($block->images)
            <div class="grid gap-4 {{ $gridClass }}">
                @foreach ($block->images as $image)
                    <x-asset-figure
                        :asset="$image"
                        class="aspect-square w-full rounded-xl object-cover shadow-sm"
                    />
                @endforeach
            </div>
        @endif
    </div>
</section>
