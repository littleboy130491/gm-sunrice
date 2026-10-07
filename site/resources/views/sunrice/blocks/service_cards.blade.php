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
        @if ($block->cards)
            <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($block->cards as $card)
                    <article class="flex flex-col overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
                        @if ($card['image'])
                            @foreach ($card['image'] as $image)
                                <x-asset-figure
                                    :asset="$image"
                                    :alt="$card['title']"
                                    class="aspect-video w-full object-cover"
                                />
                            @endforeach
                        @endif
                        <div class="flex flex-1 flex-col p-6">
                            @if ($card['title'])
                                <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ $card['title'] }}</h3>
                            @endif
                            @if ($card['text'])
                                <p class="mt-2 flex-1 text-sm text-zinc-600 dark:text-zinc-400">{{ $card['text'] }}</p>
                            @endif
                            @if (gm_link_url($card['link']) && $card['link_label'])
                                <a href="{{ gm_link_url($card['link']) }}" class="mt-4 text-sm font-semibold uppercase tracking-wide text-emerald-600 hover:text-emerald-700 dark:text-emerald-400">
                                    {{ $card['link_label'] }}
                                </a>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </div>
</section>
