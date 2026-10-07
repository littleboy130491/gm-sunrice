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
    <div class="mx-auto grid max-w-6xl items-start gap-10 lg:grid-cols-2 lg:gap-16">
        @if ($block->image)
            <div>
                @foreach ($block->image as $image)
                    <x-asset-figure
                        :asset="$image"
                        :alt="$block->name"
                        class="w-full rounded-xl object-cover"
                    />
                @endforeach
            </div>
        @endif
        <div>
            @if ($block->bio)
                <div class="prose prose-zinc mb-8 dark:prose-invert max-w-none">
                    {!! $block->bio !!}
                </div>
            @endif
            @if ($block->name)
                <p class="notranslate text-lg font-bold text-zinc-900 dark:text-zinc-100">{{ $block->name }}</p>
            @endif
            @if ($block->role)
                <p class="text-sm font-semibold uppercase tracking-wide text-emerald-600">{{ $block->role }}</p>
            @endif
        </div>
    </div>
</section>
