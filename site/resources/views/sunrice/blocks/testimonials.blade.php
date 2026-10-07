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
            <h2 class="mb-10 text-center text-2xl font-bold tracking-tight text-zinc-900 sm:text-3xl dark:text-zinc-100">
                {{ $block->heading }}
            </h2>
        @endif

        @if ($block->items)
            <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($block->items as $item)
                    <figure class="flex flex-col rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                        @if ($item['quote'])
                            <blockquote class="flex-1 text-zinc-700 dark:text-zinc-300">
                                <p class="text-base leading-relaxed">&ldquo;{{ $item['quote'] }}&rdquo;</p>
                            </blockquote>
                        @endif

                        <figcaption class="mt-6 flex items-center gap-3">
                            @if ($item['image'])
                                @foreach ($item['image'] as $photo)
                                    <img
                                        src="{{ $photo->url() }}"
                                        alt="{{ $item['author'] }}"
                                        class="size-10 rounded-full object-cover"
                                    >
                                @endforeach
                            @endif
                            <div>
                                @if ($item['author'])
                                    <p class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $item['author'] }}</p>
                                @endif
                                @if ($item['role'])
                                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $item['role'] }}</p>
                                @endif
                            </div>
                        </figcaption>
                    </figure>
                @endforeach
            </div>
        @endif
    </div>
</section>
