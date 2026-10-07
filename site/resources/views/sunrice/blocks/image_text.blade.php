@php
    $imageFirst = ($block->image_position ?? 'left') === 'left';
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
    <div class="mx-auto grid max-w-6xl items-center gap-10 lg:grid-cols-2 lg:gap-16">
        @if ($block->image)
            <div class="{{ $imageFirst ? 'lg:order-1' : 'lg:order-2' }}">
                @foreach ($block->image as $image)
                    <x-asset-figure
                        :asset="$image"
                        :alt="$block->heading ?? ''"
                        class="w-full rounded-xl object-cover shadow-lg"
                    />
                @endforeach
            </div>
        @endif

        <div class="{{ $imageFirst ? 'lg:order-2' : 'lg:order-1' }}">
            @if ($block->heading)
                <h2 class="text-2xl font-bold tracking-tight text-zinc-900 sm:text-3xl dark:text-zinc-100">
                    {{ $block->heading }}
                </h2>
            @endif

            @if ($block->text)
                <div class="prose prose-zinc mt-4 dark:prose-invert max-w-none">
                    {!! $block->text !!}
                </div>
            @endif

            @if ($block->button?->label && $block->button?->link)
                <div class="mt-6">
                    <x-block-button
                        :label="$block->button->label"
                        :link="$block->button->link"
                        :style="$block->button->style ?? 'primary'"
                    />
                </div>
            @endif
        </div>
    </div>
</section>
