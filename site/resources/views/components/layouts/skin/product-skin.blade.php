@props(['entry', 'product' => null])

@php
    $product =
        $product ??
        sunrice_global('product_label_information');
@endphp

<article class="overflow-hidden">
    <a href="{{ $entry->url }}" class="group/card flex flex-col gap-4 md:gap-4 lg:gap-5">

        {{-- Featured Image --}}
        <div class="overflow-hidden p-6 bg-white rounded-xl lg:rounded-3xl">
            <img src="{{ $entry->get('featured_image')?->url() ?? '' }}"
                alt="{{ $entry->get('featured_image')?->alt ?? $entry->title }}"
                class="w-full aspect-square object-contain transition-transform duration-500" />
        </div>

        <div class="flex flex-col gap-1">

            {{-- Heading --}}
            <div class="richtext">
                <p class="notranslate text-(--color-heading) title-display text-base md:text-xl lg:text-xl">
                    {{ $entry->title }}</p>
            </div>

            {{-- Kategori produk --}}
            <div class="flex items-end justify-between">
                <div class="flex items-center">
                    @if ($entry->get('product_categories')?->isNotEmpty())
                        <p
                            class="uppercase text-(--color-primary) font-medium group-hover/card:text-(--color-secondary) text-xs lg:text-sm">
                            @foreach ($entry->get('product_categories') as $category)
                                {{ $category->name ?? $category->title }}
                                @unless ($loop->last)
                                    ,
                                @endunless
                            @endforeach
                        </p>
                    @endif
                </div>
            </div>

        </div>

    </a>
</article>
