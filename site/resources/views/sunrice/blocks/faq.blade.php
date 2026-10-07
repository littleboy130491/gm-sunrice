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
    <div class="mx-auto max-w-3xl">
        @if ($block->heading)
            <h2 class="mb-8 text-2xl font-bold tracking-tight text-zinc-900 sm:text-3xl dark:text-zinc-100">
                {{ $block->heading }}
            </h2>
        @endif

        @if ($block->items)
            <div class="divide-y divide-zinc-200 rounded-xl border border-zinc-200 dark:divide-zinc-800 dark:border-zinc-800">
                @foreach ($block->items as $item)
                    <details class="group px-5 py-4">
                        <summary class="cursor-pointer list-none font-semibold text-zinc-900 marker:content-none dark:text-zinc-100 [&::-webkit-details-marker]:hidden">
                            <span class="flex items-center justify-between gap-4">
                                {{ $item['question'] }}
                                <span class="text-zinc-400 transition group-open:rotate-45">+</span>
                            </span>
                        </summary>
                        @if ($item['answer'])
                            <div class="prose prose-zinc mt-3 pb-1 dark:prose-invert max-w-none">
                                {!! $item['answer'] !!}
                            </div>
                        @endif
                    </details>
                @endforeach
            </div>
        @endif
    </div>
</section>
