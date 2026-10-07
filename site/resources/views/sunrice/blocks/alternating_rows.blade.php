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
            <h2 class="mb-4 text-center text-2xl font-bold tracking-tight text-zinc-900 sm:text-3xl dark:text-zinc-100">
                {{ $block->heading }}
            </h2>
        @endif
        @if ($block->intro)
            <p class="mx-auto mb-10 max-w-3xl text-center text-zinc-600 dark:text-zinc-400">{{ $block->intro }}</p>
        @endif
        @if ($block->rows)
            <div class="space-y-16">
                @foreach ($block->rows as $row)
                    @php $imageFirst = $loop->even; @endphp
                    <div class="grid items-center gap-8 lg:grid-cols-2 lg:gap-12">
                        <div class="{{ $imageFirst ? 'lg:order-1' : 'lg:order-2' }}">
                            @if ($row['icon'])
                                @foreach ($row['icon'] as $icon)
                                    <img src="{{ $icon->url() }}" alt="{{ $icon->alt ?? '' }}" class="mb-4 h-12 w-12 object-contain">
                                @endforeach
                            @endif
                            @if ($row['title'])
                                <h3 class="text-xl font-bold text-zinc-900 dark:text-zinc-100">{{ $row['title'] }}</h3>
                            @endif
                            @if ($row['text'])
                                <p class="mt-3 text-zinc-600 dark:text-zinc-400">{{ $row['text'] }}</p>
                            @endif
                        </div>
                        @if ($row['image'])
                            <div class="{{ $imageFirst ? 'lg:order-2' : 'lg:order-1' }}">
                                @foreach ($row['image'] as $image)
                                    <x-asset-figure
                                        :asset="$image"
                                        :alt="$row['title']"
                                        class="w-full rounded-xl object-cover"
                                    />
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>
