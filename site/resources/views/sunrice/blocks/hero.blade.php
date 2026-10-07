@php
    $isCenter = ($block->alignment ?? 'center') === 'center';
    $isDark = ($block->background ?? 'default') === 'dark';
    $sectionBg = match ($block->background ?? 'default') {
        'muted' => 'bg-zinc-50 dark:bg-zinc-900',
        'dark' => 'bg-zinc-900 text-zinc-100',
        default => 'bg-white dark:bg-zinc-950',
    };
@endphp

<section
    @if ($block->anchor) id="{{ $block->anchor }}" @endif
    class="{{ $sectionBg }}"
>
    @if ($block->background_image)
        <div class="relative">
            @foreach ($block->background_image as $image)
                <img
                    src="{{ $image->url() }}"
                    alt=""
                    class="aspect-[21/9] w-full object-cover"
                >
            @endforeach
            <div class="absolute inset-0 flex items-center bg-zinc-900/50 px-4 py-16 sm:px-6 lg:py-24 {{ $isCenter ? 'justify-center text-center' : 'text-left' }}">
                @include('blocks.partials.hero-copy')
            </div>
        </div>
    @else
        <div class="px-4 py-16 sm:px-6 lg:py-24 {{ $isCenter ? 'text-center' : 'text-left' }}">
            <div class="mx-auto max-w-6xl">
                @include('blocks.partials.hero-copy')
            </div>
        </div>
    @endif
</section>
