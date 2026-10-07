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
        @if ($block->stats)
            <dl class="mt-6 flex flex-wrap gap-8">
                @foreach ($block->stats as $stat)
                    <div>
                        @if ($stat['value'])
                            <dt class="text-2xl font-bold text-emerald-600">{{ $stat['value'] }}</dt>
                        @endif
                        @if ($stat['label'])
                            <dd class="text-sm text-zinc-600 dark:text-zinc-400">{{ $stat['label'] }}</dd>
                        @endif
                    </div>
                @endforeach
            </dl>
        @endif
        @if ($block->tab_branches_label || $block->tab_service_label || $block->tab_parts_label)
            <div class="mt-6 flex flex-wrap gap-2" role="tablist">
                @if ($block->tab_branches_label)
                    <span class="rounded-full bg-emerald-600 px-4 py-1.5 text-sm font-semibold text-white">{{ $block->tab_branches_label }}</span>
                @endif
                @if ($block->tab_service_label)
                    <span class="rounded-full border border-zinc-300 px-4 py-1.5 text-sm font-medium text-zinc-700 dark:border-zinc-600 dark:text-zinc-300">{{ $block->tab_service_label }}</span>
                @endif
                @if ($block->tab_parts_label)
                    <span class="rounded-full border border-zinc-300 px-4 py-1.5 text-sm font-medium text-zinc-700 dark:border-zinc-600 dark:text-zinc-300">{{ $block->tab_parts_label }}</span>
                @endif
            </div>
        @endif
        <div class="mt-8 grid gap-8 lg:grid-cols-2">
            @if ($block->map_embed_url)
                <div class="aspect-video overflow-hidden rounded-xl bg-zinc-200 dark:bg-zinc-800">
                    <iframe src="{{ $block->map_embed_url }}" title="Map" class="size-full" loading="lazy"></iframe>
                </div>
            @else
                <div class="aspect-video rounded-xl bg-zinc-100 dark:bg-zinc-800" data-dealer-map>
                    {{-- Map pins/layout: wire Leaflet or similar in site.js using dealer coordinates --}}
                </div>
            @endif
            @if ($block->dealers)
                <ul class="space-y-4">
                    @foreach ($block->dealers as $dealer)
                        <li class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-800">
                            <h3 class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $dealer->title }}</h3>
                            @if ($dealer->city)
                                <p class="text-sm font-medium text-emerald-600">{{ $dealer->city }}</p>
                            @endif
                            @if ($dealer->address)
                                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ $dealer->address }}</p>
                            @endif
                            @if ($dealer->phone_number)
                                <p class="mt-2 text-sm"><a href="tel:{{ preg_replace('/[^0-9]/', '', $dealer->phone_number) }}" class="notranslate text-emerald-600 hover:underline">{{ $dealer->phone_number }}</a></p>
                            @endif
                            @if ($dealer->google_maps_url)
                                <a href="{{ $dealer->google_maps_url }}" class="mt-2 inline-block text-sm font-semibold text-emerald-600 hover:underline" target="_blank" rel="noopener">
                                    Petunjuk arah
                                </a>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</section>
