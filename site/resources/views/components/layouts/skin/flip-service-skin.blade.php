@props(['flip'])

@php
    $resolveUrl = function ($value) {
        if (is_array($value)) {
            return $value['url'] ?? null;
        }
        return $value ?: null;
    };

    $flipBtn = $resolveUrl($flip['url_button'] ?? null);
@endphp

<div class="flip-card group/flip relative h-70 lg:h-90 rounded-2xl overflow-hidden bg-(--color-surface) lg:bg-white ">

    {{-- Front --}}
    <div class="absolute inset-0 flex flex-col">
        @if (!empty($flip['image']))
            <div class="h-80 overflow-hidden">
                <img src="{{ $flip['image']?->url() }}" alt="{{ $flip['image']?->alt ?? ($flip['heading_flip'] ?? '') }}"
                    class="w-full h-full object-cover">
            </div>
        @endif
        <p class="p-4 text-(--color-heading) title-display text-xl tracking-tight lg:text-2xl text-center">
            {{ $flip['heading_flip'] ?? '' }}</p>
    </div>

    {{-- Back --}}
    <div
        class="absolute inset-0 bg-white rounded-2xl p-4 flex flex-col justify-between text-center gap-2 translate-y-full group-hover/flip:translate-y-0 transition-transform duration-500 ease-out">
        <div id="head-flip" class="flex flex-col gap-2 md:mt-4 lg:mt-8 lg:p-4">
            <p
                class="text-(--color-heading) title-display text-xl tracking-tight lg:text-2xl text-start md:text-center lg:text-center">
                {{ $flip['heading_flip'] ?? '' }}</p>

            @if (!empty($flip['short_description']))
                <p class="richtext text-(--color-body) text-start md:text-center lg:text-center">{!! $flip['short_description'] !!}
                </p>
            @endif
        </div>

        @if ($flipBtn)
            <a href="{{ $flipBtn }}"
                class="group rounded-full py-2 pr-2 pl-6 flex items-center justify-between bg-(--color-surface) hover:bg-(--color-primary)">
                <p class="uppercase title-display text-(--color-primary) tracking-widest group-hover:text-white -mb-1">
                    {{ $flip['label_button'] ?? 'Selengkapnya' }}
                </p>
                <span
                    class="w-9 h-9 rounded-full bg-(--color-primary) group-hover:bg-white flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4 text-white group-hover:text-(--color-primary)" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                    </svg>
                </span>
            </a>
        @endif
    </div>

</div>
