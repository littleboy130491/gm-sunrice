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
            <h2 class="mb-10 text-center text-2xl font-bold tracking-tight text-zinc-900 sm:text-3xl dark:text-zinc-100">
                {{ $block->heading }}
            </h2>
        @endif

        @if ($block->items)
            <dl class="grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($block->items as $item)
                    <div class="text-center">
                        @if ($item['value'])
                            <dt class="text-3xl font-bold tracking-tight text-emerald-600 sm:text-4xl dark:text-emerald-400">
                                {{ $item['value'] }}
                            </dt>
                        @endif
                        @if ($item->get('label'))
                            <dd class="mt-2 text-sm font-medium text-zinc-600 dark:text-zinc-400">
                                {{ $item->get('label') }}
                            </dd>
                        @endif
                    </div>
                @endforeach
            </dl>
        @endif
    </div>
</section>
