@props(['term', 'product' => null])

@php
    $product =
        $product ??
        sunrice_global('product_label_information');
@endphp

<a href="{{ $term->url }}"
    class="group/card flex flex-col gap-2 lg:gap-10 rounded-2xl lg:rounded-3xl border border-(--color-line) overflow-hidden p-3 md:p-4 lg:p-6 hover:bg-(--color-surface) hover:border-(--color-surface)">

    {{-- Image --}}
    <div>
        <img src="{{ $term->get('images')?->url() ?? '' }}"
            alt="{{ $term->get('images')?->alt ?? $term->name }}"
            class="w-full md:w-[80%] lg:w-[70%] aspect-square object-contain mx-auto transition-transform duration-500" />
    </div>

    {{-- Title --}}
    <p
        class="text-center title-display text-(--color-heading) text-[11px] md:text-sm lg:text-xl tracking-tight group-hover/card:text-(--color-primary) transition-colors">
        {{ $term->name ?? $term->title }}
    </p>

</a>
