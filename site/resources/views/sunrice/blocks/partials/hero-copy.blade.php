@php
    $onImage = filled($block->background_image ?? null);
@endphp

<div @class(['mx-auto max-w-6xl', 'text-white' => $onImage])>
    @if ($block->image && ! $block->background_image)
        <div class="mb-8 flex {{ ($block->alignment ?? 'center') === 'center' ? 'justify-center' : '' }}">
            @foreach ($block->image as $image)
                <x-asset-figure
                    :asset="$image"
                    :alt="$block->headline"
                    class="max-h-64 w-auto rounded-xl object-contain"
                />
            @endforeach
        </div>
    @endif

    @if ($block->headline)
        <h2 @class([
            'text-3xl font-bold tracking-tight sm:text-4xl lg:text-5xl',
            'text-zinc-900 dark:text-zinc-100' => ! $onImage,
        ])>
            {{ $block->headline }}
        </h2>
    @endif

    @if ($block->subheadline)
        <p @class(['mt-3 text-lg font-medium text-emerald-400', 'text-emerald-600' => ! $onImage])>
            {{ $block->subheadline }}
        </p>
    @endif

    @if ($block->text)
        <p @class([
            'mx-auto mt-4 max-w-2xl text-lg',
            'text-zinc-200' => $onImage,
            'text-zinc-600 dark:text-zinc-400' => ! $onImage,
            ($block->alignment ?? 'center') === 'center' ? '' : 'mx-0',
        ])>
            {{ $block->text }}
        </p>
    @endif

    @if ($block->buttons)
        <div class="mt-8 flex flex-wrap gap-3 {{ ($block->alignment ?? 'center') === 'center' ? 'justify-center' : '' }}">
            @foreach ($block->buttons as $button)
                @if ($button['label'] && $button['link'])
                    <x-block-button
                        :label="$button['label']"
                        :link="$button['link']"
                        :style="$button['style'] ?? 'primary'"
                    />
                @endif
            @endforeach
        </div>
    @endif
</div>
