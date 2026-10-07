@php
    $cols = (int) ($block->columns ?? 3);
    $gridClass = match ($cols) {
        2 => 'sm:grid-cols-2',
        4 => 'sm:grid-cols-2 lg:grid-cols-4',
        default => 'sm:grid-cols-2 lg:grid-cols-3',
    };
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
            <h2 class="text-center text-2xl font-bold tracking-tight text-zinc-900 sm:text-3xl dark:text-zinc-100">
                {{ $block->heading }}
            </h2>
        @endif

        @if ($block->subheading)
            <p class="mx-auto mt-3 max-w-2xl text-center text-zinc-600 dark:text-zinc-400">
                {{ $block->subheading }}
            </p>
        @endif

        @if ($block->features)
            <div class="mt-10 grid gap-8 {{ $gridClass }}">
                @foreach ($block->features as $feature)
                    <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                        @if ($feature['icon'])
                            @foreach ($feature['icon'] as $icon)
                                <img
                                    src="{{ $icon->url() }}"
                                    alt=""
                                    class="mb-4 h-12 w-12 object-contain"
                                >
                            @endforeach
                        @endif

                        @if ($feature['title'])
                            <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">
                                @if (gm_link_url($feature['link']))
                                    <a href="{{ gm_link_url($feature['link']) }}" class="hover:text-emerald-600 dark:hover:text-emerald-400">
                                        {{ $feature['title'] }}
                                    </a>
                                @else
                                    {{ $feature['title'] }}
                                @endif
                            </h3>
                        @endif

                        @if ($feature['text'])
                            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                                {{ $feature['text'] }}
                            </p>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>
