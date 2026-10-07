@php
    $isBanner = ($block->style ?? 'banner') === 'banner';
    $isDark = ($block->background ?? 'default') === 'dark';
    $sectionBg = match ($block->background ?? 'default') {
        'muted' => 'bg-zinc-50 dark:bg-zinc-900',
        'dark' => 'bg-zinc-900',
        default => 'bg-white dark:bg-zinc-950',
    };
@endphp

<section
    @if ($block->anchor) id="{{ $block->anchor }}" @endif
    class="px-4 py-12 sm:px-6 {{ $sectionBg }}"
>
    <div class="mx-auto max-w-6xl">
        <div @class([
            'rounded-2xl px-6 py-10 text-center sm:px-10',
            'bg-emerald-600 text-white' => $isBanner,
            'text-left' => ! $isBanner,
            'bg-emerald-700' => $isBanner && $isDark,
        ])>
            @if ($block->heading)
                <h2 @class([
                    'text-2xl font-bold tracking-tight sm:text-3xl',
                    'text-white' => $isBanner,
                    'text-zinc-900 dark:text-zinc-100' => ! $isBanner,
                ])>
                    {{ $block->heading }}
                </h2>
            @endif

            @if ($block->text)
                <p @class([
                    'mx-auto mt-3 max-w-2xl',
                    'text-emerald-50' => $isBanner,
                    'text-zinc-600 dark:text-zinc-400' => ! $isBanner,
                ])>
                    {{ $block->text }}
                </p>
            @endif

            @if ($block->buttons)
                <div class="mt-6 flex flex-wrap justify-center gap-3">
                    @foreach ($block->buttons as $button)
                        @if ($button['label'] && $button['link'])
                            <x-block-button
                                :label="$button['label']"
                                :link="$button['link']"
                                :style="$isBanner ? ($button['style'] === 'outline' ? 'outline' : 'secondary') : ($button['style'] ?? 'primary')"
                            />
                        @endif
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</section>
