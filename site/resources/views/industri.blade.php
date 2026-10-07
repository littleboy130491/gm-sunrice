@php
    $bodyClass = collect([
        'background-grey',
        $pageType === 'entry' ? 'entry' : null,
        isset($collection) ? 'entry-' . $collection->handle : null,
        isset($collection) ? $collection->handle : null,
        isset($entry) ? 'slug-' . $entry->slug : null,
    ])
        ->filter()
        ->implode(' ');

    $acc = sunrice_global('industry_label_information');

    // Global label product (untuk pilihan urutan produk)
    $productGlobal = sunrice_global('product_label_information');

    $sortOrderBy = (string) ($productGlobal['product_sort_order_by'] ?? 'tanggal_upload');

    [$sortField, $sortDir] = match ($sortOrderBy) {
        'urutan_tree' => ['sort_order', 'asc'],
        'judul_az' => ['title', 'asc'],
        'judul_za' => ['title', 'desc'],
        default => ['published_at', 'desc'],
    };

    $industries = gm_terms('industries');

    // Cek component
    $hasHeader = view()->exists('components.layouts.header.header');
    $hasHeroPage = view()->exists('components.layouts.hero.heropage');
    $hasProductSkin = view()->exists('components.layouts.skin.product-skin');
    $hasFooter = view()->exists('components.layouts.footer.footer');

    // Label global
    $relatedProductLabel = $acc['related_product_label'] ?? 'Produk Terkait';
    $buttonLabel = $acc['button_label'] ?? 'Semua Produk';
    $iconAccordion = $acc?->icon_accordion?->url();
    $iconAccordionActive = $acc?->icon_accordion_active?->url();
    $iconAccordionAlt = $acc?->icon_accordion?->alt;
    $iconAccordionActiveAlt = $acc?->icon_accordion_active?->alt;

    // Data industri
    $industryCollection = collect($industries)->values();
    $lastIndex = $industryCollection->count() - 1;

    $industryItems = $industryCollection->map(function ($industry, $index) use ($hasProductSkin, $lastIndex, $sortField, $sortDir) {
        $relatedProducts = $hasProductSkin
            ? (gm_entries('products')
                ?->whereTerm('industries', $industry->slug)
                ->orderBy($sortField, $sortDir)
                ->limit(6)
                ->get() ?? collect())
            : collect();

        return [
            'industry' => $industry,
            'number' => $index + 1,
            'related_products' => $relatedProducts,
            'is_first' => $index === 0,
            'is_last' => $index === $lastIndex,
            'featured_image_url' => gm_asset_url($industry->get('featured_image')),
            'featured_image_alt' => $industry->get('featured_image')?->alt ?? $industry->name,
        ];
    });
@endphp

<x-layouts.main :body-class="$bodyClass">
    @if ($hasHeader)
        <x-layouts.header.header />
    @endif

    <main>
        @if ($hasHeroPage)
            <x-layouts.hero.heropage :title="$entry?->title ?? 'Industri'" :image="$entry?->get('featured_image')" />
        @endif

        <section id="industries-accordion">
            <div class="container my-18 md:my-18 lg:my-30">
                <div class="flex flex-col gap-8 md:gap-10 lg:gap-16">

                    {{-- Accordion industri --}}
                    @foreach ($industryItems as $item)
                        <details class="industry-item group overflow-hidden" name="industry-accordion"
                            {{ $item['is_first'] ? 'open' : '' }}>

                            {{-- Header --}}
                            <summary class="flex items-center justify-between gap-4 cursor-pointer">
                                <div id="accordion-heading" class="flex items-center gap-3 md:gap-4 lg:gap-8">
                                    <p
                                        class="title-display lg:text-xl bg-white border border-white w-10 h-10 md:w-12 md:h-12 lg:w-18 lg:h-18 flex justify-center items-center rounded-full transition-colors group-open:text-(--color-primary) group-open:border-(--color-primary) group-open:bg-white/0">
                                        {{ $item['number'] . '.' }}
                                    </p>

                                    <h2 class="text-xl lg:text-3xl normal-case">{{ $item['industry']->name ?? $item['industry']->title }}</h2>
                                </div>

                                @if (!empty($iconAccordion))
                                    <img src="{{ $iconAccordion }}" alt="{{ $iconAccordionAlt }}"
                                        class="w-5 h-5 lg:w-8 lg:h-8 shrink-0 group-open:hidden" />
                                @endif

                                @if (!empty($iconAccordionActive))
                                    <img src="{{ $iconAccordionActive }}" alt="{{ $iconAccordionActiveAlt }}"
                                        class="w-5 h-5 lg:w-8 lg:h-8 shrink-0 hidden group-open:block" />
                                @endif
                            </summary>

                            {{-- Body --}}
                            <div
                                class="flex flex-col gap-10 md:gap-10 lg:gap-15 pt-6 md:pt-4 lg:pt-6 md:pl-16 lg:pl-26">

                                {{-- Content --}}
                                <div class="flex flex-col lg:flex-row lg:items-end gap-6">
                                    @if ($item['featured_image_url'])
                                        <div class="w-full">
                                            <img src="{{ gm_asset_url($item['featured_image_url']) }}"
                                                alt="{{ $item['featured_image_alt'] }}"
                                                class="w-full rounded-xl lg:rounded-3xl" />
                                        </div>
                                    @endif

                                    @if ($item['industry']->get('content'))
                                        <div class="w-full richtext">
                                            {!! $item['industry']->get('content') !!}
                                        </div>
                                    @endif
                                </div>

                                {{-- Label produk terkait --}}
                                @if ($item['related_products']->isNotEmpty())
                                    <div class="flex items-center gap-4">
                                        <p class="uppercase text-(--color-primary) font-medium">
                                            {{ $relatedProductLabel }}
                                        </p>
                                        <span class="flex-1 border-t border-(--color-line)"></span>
                                    </div>

                                    {{-- Slot produk terkait --}}
                                    <div
                                        class="flex flex-col items-start gap-8 lg:flex-row lg:items-end lg:justify-between -mt-4 lg:-mt-8">
                                        <div
                                            class="industry-products grid grid-cols-2 md:grid-cols-2 lg:grid-cols-3 gap-y-8 gap-x-4 md:gap-y-10 md:gap-x-6 lg:gap-y-12 lg:gap-x-6 w-full">
                                            @foreach ($item['related_products'] as $entry)
                                                <x-layouts.skin.product-skin :entry="$entry" />
                                            @endforeach
                                        </div>

                                        {{-- Button --}}
                                        <a href="{{ $item['industry']->url() }}"
                                            class="button button--primary shrink-0">
                                            {{ $buttonLabel }}
                                        </a>
                                    </div>
                                @endif

                            </div>

                        </details>

                        @unless ($item['is_last'])
                            <hr class="border-0 border-t border-[#CECECE]">
                        @endunless
                    @endforeach

                </div>
            </div>
        </section>

    </main>

    @if ($hasFooter)
        <x-layouts.footer.footer />
    @endif
</x-layouts.main>
