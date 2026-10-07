@php
    $sectionBg = match ($block->background ?? 'default') {
        'muted' => 'bg-zinc-50 dark:bg-zinc-900',
        'dark' => 'bg-zinc-900 text-zinc-100',
        default => 'bg-white dark:bg-zinc-950',
    };
    $featured = collect($block->featured_entry ?? [])->first();
@endphp

<section
    @if ($block->anchor) id="{{ $block->anchor }}" @endif
    class="px-4 py-12 sm:px-6 lg:py-16 {{ $sectionBg }}"
>
    <div class="mx-auto max-w-6xl">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                @if ($block->heading)
                    <h2 class="text-2xl font-bold tracking-tight text-zinc-900 sm:text-3xl dark:text-zinc-100">
                        {{ $block->heading }}
                    </h2>
                @endif
                @if ($block->text)
                    <p class="mt-2 text-zinc-600 dark:text-zinc-400">{{ $block->text }}</p>
                @endif
            </div>
            @if ($block->button?->label && $block->button?->link)
                <x-block-button
                    :label="$block->button->label"
                    :link="$block->button->link"
                    :style="$block->button->style ?? 'outline'"
                />
            @endif
        </div>
        <div class="mt-10 grid gap-8 lg:grid-cols-2">
            @if ($featured)
                <a href="{{ $featured->url }}" class="group overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-800">
                    @if ($featured->get('featured_image') ?? null)
                        @foreach ($featured->get('featured_image') as $image)
                            <x-asset-figure
                                :asset="$image"
                                :alt="$featured->title"
                                class="aspect-video w-full object-cover transition group-hover:scale-[1.02]"
                            />
                        @endforeach
                    @endif
                    <div class="p-6">
                        <h3 class="text-xl font-bold text-zinc-900 dark:text-zinc-100">{{ $featured->title }}</h3>
                        @if ($featured->get('excerpt') ?? null)
                            <p class="mt-2 text-zinc-600 dark:text-zinc-400">{{ $featured->get('excerpt') }}</p>
                        @endif
                    </div>
                </a>
            @endif
            @if ($block->entries)
                <div class="space-y-4">
                    @foreach ($block->entries as $entry)
                        <a href="{{ $entry->url }}" class="flex gap-4 rounded-lg border border-zinc-200 p-3 transition hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-zinc-900">
                            @if ($entry->get('featured_image') ?? null)
                                @foreach ($entry->get('featured_image') as $image)
                                    <x-asset-figure
                                        :asset="$image"
                                        :alt="$entry->title"
                                        class="size-20 shrink-0 rounded-lg object-cover"
                                    />
                                @endforeach
                            @endif
                            <div class="min-w-0">
                                <h3 class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $entry->title }}</h3>
                                @if ($entry->published_at ?? null)
                                    <time class="text-xs text-zinc-500" datetime="{{ $entry->published_at }}">{{ $entry->published_at->format('d.m.Y') }}</time>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</section>
