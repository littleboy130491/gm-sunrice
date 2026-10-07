@php
    $embedUrl = $block->video_url ?? '';
    if ($embedUrl && str_contains($embedUrl, 'youtube.com/watch')) {
        parse_str(parse_url($embedUrl, PHP_URL_QUERY) ?? '', $query);
        $embedUrl = 'https://www.youtube.com/embed/' . ($query['v'] ?? '');
    } elseif ($embedUrl && str_contains($embedUrl, 'youtu.be/')) {
        $id = trim(parse_url($embedUrl, PHP_URL_PATH) ?? '', '/');
        $embedUrl = 'https://www.youtube.com/embed/' . $id;
    } elseif ($embedUrl && str_contains($embedUrl, 'vimeo.com/') && ! str_contains($embedUrl, 'player.vimeo.com')) {
        $id = trim(parse_url($embedUrl, PHP_URL_PATH) ?? '', '/');
        $embedUrl = 'https://player.vimeo.com/video/' . $id;
    }

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
    <div class="mx-auto max-w-4xl">
        @if ($block->heading)
            <h2 class="mb-6 text-center text-2xl font-bold tracking-tight text-zinc-900 sm:text-3xl dark:text-zinc-100">
                {{ $block->heading }}
            </h2>
        @endif

        @if ($embedUrl)
            <div class="aspect-video overflow-hidden rounded-xl bg-zinc-900 shadow-lg">
                <iframe
                    src="{{ $embedUrl }}"
                    title="{{ $block->heading ?? 'Video' }}"
                    class="size-full"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                    allowfullscreen
                ></iframe>
            </div>
        @endif

        @if ($block->caption)
            <p class="mt-3 text-center text-sm text-zinc-500 dark:text-zinc-400">
                {{ $block->caption }}
            </p>
        @endif
    </div>
</section>
