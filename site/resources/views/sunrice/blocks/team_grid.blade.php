@php
    $sectionBg = match ($block->background ?? 'default') {
        'muted' => 'bg-zinc-50 dark:bg-zinc-900',
        'dark' => 'bg-zinc-900 text-zinc-100',
        default => 'bg-white dark:bg-zinc-950',
    };
@endphp

<section @if ($block->anchor) id="{{ $block->anchor }}" @endif
    class="px-4 py-12 sm:px-6 lg:py-16 {{ $sectionBg }}">
    <div class="mx-auto max-w-6xl">
        @if ($block->heading)
            <h2 class="mb-10 text-center text-2xl font-bold tracking-tight text-zinc-900 sm:text-3xl dark:text-zinc-100">
                {{ $block->heading }}
            </h2>
        @endif
        @if ($block->members)
            <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($block->members as $member)
                    <figure class="text-center">
                        @if ($member['image'])
                            @foreach ($member['image'] as $image)
                                <x-asset-figure :asset="$image" :alt="$member['name']" :figure="false"
                                    class="mx-auto aspect-3/4 w-full max-w-xs rounded-xl object-cover" />
                            @endforeach
                        @endif
                        @if ($member['name'])
                            <figcaption class="notranslate mt-4 text-lg font-bold text-zinc-900 dark:text-zinc-100">
                                {{ $member['name'] }}</figcaption>
                        @endif
                        @if ($member['role'])
                            <p class="text-sm font-semibold uppercase tracking-wide text-emerald-600">{{ $member['role'] }}
                            </p>
                        @endif
                    </figure>
                @endforeach
            </div>
        @endif
    </div>
</section>
