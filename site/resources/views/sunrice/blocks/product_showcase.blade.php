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
        <div class="flex flex-wrap items-end justify-between gap-4">
            @if ($block->heading)
                <h2 class="text-2xl font-bold tracking-tight text-zinc-900 sm:text-3xl dark:text-zinc-100">
                    {{ $block->heading }}
                </h2>
            @endif
            @if ($block->button?->label && $block->button?->link)
                <x-block-button :label="$block->button->label" :link="$block->button->link" :style="$block->button->style ?? 'outline'" />
            @endif
        </div>
        @if ($block->products)
            <div class="mt-10 flex gap-6 overflow-x-auto pb-4">
                @foreach ($block->products as $product)
                    <a href="{{ $product->url }}" class="group min-w-50 shrink-0 text-center">
                        @if ($product->get('featured_image'))
                            @foreach ($product->get('featured_image') as $image)
                                <x-asset-figure :asset="$image" :alt="$product->title"
                                    class="mx-auto h-32 w-auto object-contain transition group-hover:scale-105" />
                            @endforeach
                        @endif
                        <p class="notranslate mt-3 text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                            {{ $product->title }}</p>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</section>
