@php
    $bodyClass = collect([
        $pageType === 'entry' ? 'entry' : null,
        isset($collection) ? 'entry-' . $collection->handle : null,
        isset($collection) ? $collection->handle : null,
        isset($entry) ? 'slug-' . $entry->slug : null,
    ])
        ->filter()
        ->implode(' ');

    // Cek component
    $hasHeader = view()->exists('components.layouts.header.header');
    $hasHeroPage = view()->exists('components.layouts.hero.heropage');
    $hasFooter = view()->exists('components.layouts.footer.footer');

    // Global label product
    $product = sunrice_global('product_label_information');

    $ctaLink = $entry->get('cta_link');
    $ctaUrl = is_array($ctaLink) ? ($ctaLink['url'] ?? null) : $ctaLink;

    // Catalogue link
    $catalogue = $entry->get('catalogue_link') ?? null;
    $catalogueShow = $catalogue['displayed'] ?? false;
    $catalogueLabel = $catalogue['label'] ?? '';
    $catalogueUrl = $catalogue['url'] ?? '#';

    $productGlobal = \Sunrice\Models\GlobalSet::where('handle', 'product_label_information')->first();

    $specOptions = collect(
        collect($productGlobal?->blueprint?->fields ?? [])->firstWhere('handle', 'spesification_info')['config']['options'] ?? [],
    )->mapWithKeys(function ($opt, $k) {
        if (is_array($opt) && array_key_exists('value', $opt)) {
            return [$opt['value'] => $opt['label'] ?? $opt['value']];
        }
        if (is_array($opt) && array_key_exists('key', $opt)) {
            return [$opt['key'] => $opt['value'] ?? $opt['key']];
        }
        return [$k => $opt];
    });

    $selectedSpecKeys = collect($product?->spesification_info ?? [])->filter(fn($v) => is_string($v));

    $specs = $specOptions
        ->map(fn($label, $key) => ['label' => $label ?: $key, 'value' => $entry->get($key)])
        ->concat(
            collect($entry->get('product_specifications') ?? [])->map(
                fn($s) => ['label' => $s['heading'] ?? '', 'value' => $s['short_description'] ?? ''],
            ),
        )
        ->filter(fn($s) => !empty($s['value']))
        ->values();

    // Other Descriptions
    $otherDescriptions = $entry->get('other_descriptions');

    // Features & Benefits
    $features = collect($entry->get('features_and_benefits') ?? [])
        ->filter(fn($f) => !empty($f['heading']) || !empty($f['image']))
        ->values();

    $featuresIsSlider = $features->count() > 1;

    // Product Gallery
    $gallery = collect($entry->get('product_gallery') ?? [])
        ->filter()
        ->values();

    // Hero banner > placeholder global (foto produk di gallery)
    $heroBackground = $product?->background_image_placeholder ?? null;

    //  Background pattern
    $backgroundPattern = $entry->get('background_pattern_image');

    if (!$backgroundPattern) {
        $backgroundPattern = $product?->background_pattern_image ?? null;
    }

    // Comparison
    $compareProducts = gm_entries('products')
        ?->orderBy('title')
        ->get() ?? collect();

    $compareGroups = $compareProducts
        ->groupBy(fn($item) => collect($item->get('product_categories') ?? [])->first()?->name ?: 'Lainnya')
        ->map(
            fn($group) => $group->map(
                fn($item) => [
                    'id' => $item['id'],
                    'label' => $item->get('sku') ?: $item['title'],
                ],
            ),
        );

    $productFieldHandles = collect($entry->activeBlueprint()?->fields ?? [])->pluck('handle');

    $compareRows = collect([['handle' => 'model', 'label' => $product?->model_labels ?: 'Model']])
        ->concat(
            $specOptions
                ->filter(fn($label, $key) => $selectedSpecKeys->contains($key) && $productFieldHandles->contains($key))
                ->map(fn($label, $key) => ['handle' => $key, 'label' => $label ?: $key])
                ->values(),
        )
        ->values();

    // Kolom perbandingan
    $compareColumns = 3;
    $compareDefaults = $compareProducts->pluck('id')->prepend($entry->id)->unique()->values()->take($compareColumns);
@endphp

<x-layouts.main :body-class="$bodyClass">
    @if ($hasHeader)
        <x-layouts.header.header />
    @endif

    <main>

        {{-- Produk informasi --}}
        <section id="product-information" class="relative bg-zinc-900">
            {{-- Hero banner (placeholder global) --}}
            @if ($heroBackground)
                <div class="absolute inset-0 overflow-hidden pointer-events-none select-none" aria-hidden="true">
                    <img src="{{ $heroBackground->url() }}" alt=""
                        class="h-full w-full object-cover object-center">
                    <div class="heropage-product-overlay absolute inset-0"></div>
                </div>
            @else
                <div class="absolute inset-0 overflow-hidden bg-linear-to-b from-zinc-900 via-zinc-800 to-zinc-900"
                    aria-hidden="true">
                </div>
            @endif

            <div class="container relative z-10">
                <div
                    class="flex min-h-120 flex-col items-stretch py-30 md:min-h-145 md:py-30 lg:min-h-160 lg:flex-row lg:items-center lg:gap-10 lg:py-20">
                    {{-- Konten --}}
                    <div class="flex w-full flex-col justify-center gap-8 lg:max-w-[55%] lg:gap-10">
                        <div class="flex flex-col gap-3">
                            @if ($entry->get('product_categories') && $entry->get('product_categories')->isNotEmpty())
                                <p class="text-left font-medium uppercase text-(--color-primary)">
                                    @foreach ($entry->get('product_categories') as $category)
                                        {{ $category->name ?? $category->title }}
                                        @unless ($loop->last)
                                            ,
                                        @endunless
                                    @endforeach
                                </p>
                            @endif

                            <h1 class="notranslate heading-single text-left text-white">{{ $entry->title }}</h1>
                            @if ($entry->get('description'))
                                <div class="richtext text-cust w-full text-left text-white/90">
                                    {!! $entry->get('description') !!}
                                </div>
                            @endif
                        </div>

                        <div class="flex flex-wrap gap-3">
                            @if ($ctaUrl)
                                <a href="{{ $ctaUrl }}" class="button button--primary">
                                    {{ $entry->get('cta_label') ?: '' }}
                                </a>
                            @endif

                            @if ($catalogueShow)
                                <a href="{{ $catalogueUrl }}" class="button button--secondary">
                                    {{ $catalogueLabel }}
                                </a>
                            @endif
                        </div>
                    </div>

                    {{-- Featured Image --}}
                    @if ($entry->get('featured_image'))
                        <div
                            class="relative z-20 mt-8 flex w-full self-end justify-center lg:mt-0 lg:w-auto lg:flex-1 lg:justify-end">
                            <img src="{{ $entry->get('featured_image')->url() }}"
                                alt="{{ $entry->get('featured_image')->alt ?? $entry->title }}"
                                class="w-full h-100 md:h-120 lg:h-150 object-contain object-bottom -mb-50 md:-mb-40 lg:-mb-50" />
                        </div>
                    @endif
                </div>
            </div>
        </section>

        {{-- Other Descriptions --}}
        @if (filled((string) $otherDescriptions))
            <section id="other-descriptions">
                <div class="container">
                    <div class="mb-18 mt-20 lg:my-30 richtext">
                        {!! $otherDescriptions !!}
                    </div>
                </div>
            </section>
        @endif

        {{-- Features & Benefit --}}
        @if ($features->isNotEmpty())
            <section id="features-benefit">
                <div class="container">
                    <div class="mb-18 mt-20 md:mb-18 lg:my-30 flex flex-col gap-8 lg:gap-10">
                        <div class="flex items-center justify-between gap-4">
                            <h2>{{ $product['benefit_label'] ?? '' }}</h2>

                            @if ($featuresIsSlider)
                                {{-- Arrow navigation --}}
                                <div class="flex items-center gap-3 shrink-0">
                                    {{-- Arrow Prev --}}
                                    <button type="button" aria-label="Previous"
                                        class="features-prev rounded-full w-10 h-10 lg:w-11 lg:h-11 text-(--color-primary) hover:text-white bg-(--color-surface) hover:bg-(--color-primary) p-3 transition-colors">
                                        <svg class="rotate-180 w-full h-full" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </button>

                                    {{-- Arrow Next --}}
                                    <button type="button" aria-label="Next"
                                        class="features-next rounded-full w-10 h-10 lg:w-11 lg:h-11 text-(--color-primary) hover:text-white bg-(--color-surface) hover:bg-(--color-primary) p-3 transition-colors">
                                        <svg class="w-full h-full" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </button>
                                </div>
                            @endif
                        </div>

                        @if ($featuresIsSlider)
                            {{-- Slider mode --}}
                            <div class="swiper features-swiper">
                                <div class="swiper-wrapper">
                                    @foreach ($features as $feature)
                                        <div
                                            class="swiper-slide h-auto bg-(--color-surface) overflow-hidden rounded-xl">
                                            <div class="features-card flex flex-col h-full">
                                                @if (!empty($feature['image']))
                                                    <div class="features-card-image">
                                                        <img src="{{ $feature['image']->url() }}"
                                                            alt="{{ $feature['image']->alt ?? ($feature['heading'] ?? '') }}"
                                                            class="w-full aspect-square object-cover" />
                                                    </div>
                                                @endif
                                                <div class="p-5 flex flex-col gap-2">
                                                    @if (!empty($feature['heading']))
                                                        <p
                                                            class="tracking-tight font-(family-name:--font-display) font-semibold text-xl">
                                                            {{ $feature['heading'] }}</p>
                                                    @endif
                                                    @if (!empty($feature['description']))
                                                        <div class="richtext text-(--color-body)">
                                                            {!! $feature['description'] !!}
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            {{-- Static mode --}}
                            <div class="md:w-1/3">
                                @foreach ($features as $feature)
                                    <div
                                        class="bg-(--color-surface) overflow-hidden rounded-xl features-card flex flex-col h-full">
                                        @if (!empty($feature['image']))
                                            <div class="features-card-image">
                                                <img src="{{ $feature['image']->url() }}"
                                                    alt="{{ $feature['image']->alt ?? ($feature['heading'] ?? '') }}"
                                                    class="w-full aspect-square object-cover" />
                                            </div>
                                        @endif
                                        <div class="p-5 flex flex-col gap-2">
                                            @if (!empty($feature['heading']))
                                                <h3 class="tracking-tight">{{ $feature['heading'] }}</h3>
                                            @endif
                                            @if (!empty($feature['description']))
                                                <div class="richtext text-(--color-body)">
                                                    {!! $feature['description'] !!}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </section>

            @pushOnce('styles')
                <style>
                    .features-swiper .swiper-wrapper {
                        align-items: stretch;
                    }

                    .features-swiper .swiper-slide {
                        height: auto;
                    }
                </style>
            @endPushOnce
        @endif

        {{-- Product Gallery --}}
        @if ($gallery->isNotEmpty())
            <section id="product-gallery">
                <div class="container overflow-hidden md:overflow-visible lg:overflow-visible">
                    <div class="mb-18 mt-30 md:mb-18 md:mt-30 lg:my-30">
                        <div class="gallery-wrapper flex flex-col gap-4">

                            {{-- Gambar besar --}}
                            <div class="gallery-main rounded-2xl overflow-hidden">
                                @foreach ($gallery as $image)
                                    <div class="gallery-slide {{ $loop->first ? '' : 'hidden' }}">
                                        <img src="{{ $image->url() }}" alt="{{ $image->alt ?? $entry->title }}"
                                            class="w-full h-auto md:h-90 lg:h-90 object-contain" />
                                    </div>
                                @endforeach
                            </div>

                            {{-- Thumbnail slider --}}
                            @if ($gallery->count() > 1)
                                <div class="flex items-center gap-1 md:gap-1 lg:gap-0">

                                    {{-- Prev --}}
                                    <button type="button" aria-label="Previous"
                                        class="gallery-prev shrink-0 w-4 h-4 md:w-4 md:h-4 lg:w-6 lg:h-6 text-black -ml-4 md:-ml-4 lg:-ml-6">
                                        <svg class="rotate-180 w-full h-full" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </button>

                                    {{-- Track thumbnail --}}
                                    <div
                                        class="gallery-thumbs-track flex overflow-x-auto scroll-smooth flex-1 scrollbar-none [&::-webkit-scrollbar]:hidden">
                                        @foreach ($gallery as $image)
                                            <button type="button"
                                                class="gallery-thumb shrink-0 mr-3 last:mr-0 w-[calc((100%-3*0.75rem)/4)] rounded-xl overflow-hidden transition-opacity {{ $loop->first ? 'opacity-100' : 'opacity-50' }}">
                                                <img src="{{ $image->url() }}" alt="{{ $image->alt ?? $entry->title }}"
                                                    class="w-full h-15 md:h-15 lg:h-30 object-contain" />
                                            </button>
                                        @endforeach
                                    </div>

                                    {{-- Next --}}
                                    <button type="button" aria-label="Next"
                                        class="gallery-next shrink-0 w-4 h-4 md:w-4 md:h-4 lg:w-6 lg:h-6 text-black -mr-4 md:-mr-4 lg:-mr-6">
                                        <svg class="w-full h-full" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </button>

                                </div>
                            @endif

                        </div>
                    </div>
                </div>
            </section>
        @endif

        {{-- Specification --}}
        @if ($specs->isNotEmpty())
            <section id="specification" class="bg-(--color-surface)">
                <div class="container">
                    <div class="py-18 md:py-18 lg:py-30 flex flex-col gap-4">
                        <h2>{{ $product['spesification_labels'] ?? '' }}</h2>
                        <div id="specification-grid">
                            <div class="grid grid-cols-1 sm:grid-cols-2 md:gap-x-6 lg:gap-x-10">
                                @foreach ($specs as $spec)
                                    <div
                                        class="flex justify-between gap-4 border-b border-[#CECECE] py-4 {{ $loop->remaining < 2 ? 'sm:border-b-0' : '' }} {{ $loop->last ? 'border-b-0' : '' }}">
                                        <p class="specifi-title w-[45%] font-medium">{{ $spec['label'] }}</p>
                                        <p class="w-[55%] text-(--color-body)">{{ $spec['value'] }}</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        @endif

        {{-- Comparison --}}
        <section id="comparison" class="relative overflow-hidden">

            @if ($backgroundPattern)
                <div class="absolute inset-0 z-0 pointer-events-none select-none">
                    <img src="{{ $backgroundPattern->url() }}" alt="{{ $backgroundPattern->alt ?? $entry->title }}"
                        oncontextmenu="return false" draggable="false"
                        class="h-full w-[40%] md:w-[50%] lg:w-100 opacity-10 object-cover">
                </div>
            @endif

            <div class="container relative z-10">
                <div class="pt-18 pb-18 lg:pt-30 flex flex-col gap-8 lg:gap-10">
                    <h2>{{ $product['comparison_labels'] ?? '' }}</h2>

                    {{-- Compare Grid --}}
                    <div id="comparison-grid" data-endpoint="{{ url('/api/products') }}"
                        data-empty-placeholder="{{ $product['empty_placeholder_data'] ?? '' }}">
                        <div class="overflow-x-auto">
                            <div class="min-w-180 lg:min-w-0">

                                {{-- Baris dropdown --}}
                                <div class="grid rounded-2xl bg-(--color-surface) p-2 pl-5"
                                    style="grid-template-columns: minmax(140px, 1fr) repeat({{ $compareColumns }}, minmax(0, 1fr));">
                                    <div class="flex items-center">
                                        <p class="uppercase font-medium text-(--color-body)">
                                            {{ $product['type_labels'] ?? 'Tipe Model' }}
                                        </p>
                                    </div>

                                    @for ($col = 0; $col < $compareColumns; $col++)
                                        <div class="px-2">
                                            <select
                                                class="comparison-select w-full rounded-full bg-white py-2.5 pl-4 pr-4 text-xs lg:text-sm text-(--color-body) focus:outline-none"
                                                data-column="{{ $col }}">
                                                @foreach ($compareGroups as $groupName => $items)
                                                    <optgroup label="{{ strtoupper($groupName) }}">
                                                        @foreach ($items as $item)
                                                            <option value="{{ $item['id'] }}" class="notranslate"
                                                                @selected(($compareDefaults[$col] ?? null) === $item['id'])>
                                                                {{ $item['label'] }}
                                                            </option>
                                                        @endforeach
                                                    </optgroup>
                                                @endforeach
                                            </select>
                                        </div>
                                    @endfor
                                </div>

                                {{-- Baris gambar --}}
                                <div class="grid my-8"
                                    style="grid-template-columns: minmax(140px, 1fr) repeat({{ $compareColumns }}, minmax(0, 1fr));">
                                    <div></div>
                                    @for ($col = 0; $col < $compareColumns; $col++)
                                        <div
                                            class="comparison-image-wrap px-2 md:px-3 lg:px-4 flex items-center justify-center h-32 md:h-36 lg:h-44">
                                            <img data-comparison-image="{{ $col }}" src=""
                                                alt=""
                                                class="comparison-image w-full h-full object-contain opacity-0 transition-opacity duration-300" />
                                        </div>
                                    @endfor
                                </div>

                                {{-- Baris spesifikasi --}}
                                @foreach ($compareRows as $rowIndex => $row)
                                    <div class="comparison-row grid border-b border-(--color-line) last:border-b-0"
                                        data-row="{{ $rowIndex }}" data-field="{{ $row['handle'] }}"
                                        style="grid-template-columns: minmax(140px, 1fr) repeat({{ $compareColumns }}, minmax(0, 1fr));">
                                        <div class="flex items-center py-4 pl-5">
                                            <p
                                                class="font-(family-name:--font-display) text-sm lg:text-2xl font-semibold tracking-tight">
                                                {{ $row['label'] }}</p>
                                        </div>
                                        @for ($col = 0; $col < $compareColumns; $col++)
                                            <div
                                                class="px-2 md:px-3 lg:px-4 flex items-center justify-center py-4 text-center">
                                                <p class="text-(--color-body) {{ $rowIndex === 0 ? 'md:text-sm lg:text-xl font-(family-name:--font-display) font-semibold' : 'text-sm lg:text-base' }}"
                                                    data-comparison-cell="{{ $col }}"
                                                    data-row="{{ $rowIndex }}"></p>
                                            </div>
                                        @endfor
                                    </div>
                                @endforeach

                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </section>

    </main>

    @if ($hasFooter)
        <x-layouts.footer.footer :compact="true" />
    @endif
</x-layouts.main>
