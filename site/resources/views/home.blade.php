@php
    $bodyClass = collect([
        $pageType === 'entry' ? 'entry' : null,
        isset($collection) ? 'entry-' . $collection->handle : null,
        isset($collection) ? $collection->handle : null,
        isset($entry) ? 'slug-' . $entry->slug : null,
    ])
        ->filter()
        ->implode(' ');

    $about = collect($entry->get('sections'))->first(
        fn($section) => (string) ($section->key ?? '') === 'section-about',
    );

    $productCategory = collect($entry->get('sections'))->first(
        fn($section) => (string) ($section->key ?? '') === 'section-product-category',
    );

    // Kategori produk
    $productCategories = gm_terms('product_categories')
        ->filter(
            fn($term) => (gm_entries('products')?->whereTerm('product_categories', $term->slug)->get()->count() ?? 0) > 0,
        );

    $services = collect($entry->get('sections'))->first(
        fn($section) => (string) ($section->key ?? '') === 'section-services',
    );

    $marketplace = collect($entry->get('sections'))->first(
        fn($section) => (string) ($section->key ?? '') === 'section-marketplace',
    );

    $blogSosmed = collect($entry->get('sections'))->first(
        fn($section) => (string) ($section->key ?? '') === 'section-blog-sosmed',
    );

    $dealer = collect($entry->get('sections'))->first(
        fn($section) => (string) ($section->key ?? '') === 'section-dealer',
    );

    $groupGm = collect($entry->get('sections'))->first(
        fn($section) => (string) ($section->key ?? '') === 'section-group-gm',
    );

    $blogSection = collect($entry->get('sections'))->first(
        fn($section) => (string) ($section->key ?? '') === 'section-blog',
    );

    // Resolve link (link fields hydrate to ['url' => ...])
    $resolveUrl = function ($value) {
        if (is_array($value)) {
            return $value['url'] ?? null;
        }
        return $value ?: null;
    };

    // Link button
    $aboutBtn = $resolveUrl($about['button']['link'] ?? null);
    $productCategoryUrl = $resolveUrl($productCategory['link'] ?? null);

    //  Blog kategori media sosial
    $sosmedPosts = gm_entries('posts')
        ?->whereTerm('categories', 'sosial-media')
        ->orderBy('published_at', 'desc')
        ->limit(3)
        ->get() ?? collect();

    $parseMapsCoords = function ($url) {
        if (!$url) {
            return [null, null];
        }
        if (preg_match('/!3d(-?\d+\.\d+)!4d(-?\d+\.\d+)/', $url, $m)) {
            return [(float) $m[1], (float) $m[2]];
        }
        if (preg_match('/@(-?\d+\.\d+),(-?\d+\.\d+)/', $url, $m)) {
            return [(float) $m[1], (float) $m[2]];
        }
        return [null, null];
    };

    // Query dealer aktif
    $dealers = (gm_entries('dealers')?->where('is_active', 1)->get() ?? collect())
        ->map(function ($dealer) use ($parseMapsCoords) {
            $cat = $dealer->get('dealer_categories')?->first();

            // Koordinat dari URL maps > fallback lat/lng manual
            [$urlLat, $urlLng] = $parseMapsCoords($dealer->get('google_maps_url'));
            $lat = $urlLat ?? ($dealer->get('location')['latitude'] ?? null);
            $lng = $urlLng ?? ($dealer->get('location')['longitude'] ?? null);

            return [
                'company' => $dealer->title,
                'address' => $dealer->get('address'),
                'city' => $dealer->get('city'),
                'region' => $dealer->get('region'),
                'phone' => $dealer->get('phone_number'),
                'whatsapp' => $dealer->get('whatsapp_number'),
                'whatsapp_link' => $dealer->get('whatsapp_link'),
                'maps_url' => $dealer->get('google_maps_url'),
                'lat' => $lat,
                'lng' => $lng,
                'dealer-category' => $cat?->slug ?? '',
            ];
        })
        ->filter(fn($d) => $d['lat'] && $d['lng'])
        ->values();

    // Blog terbaru
    $blogLatest = (gm_entries('posts')?->orderBy('published_at', 'desc')->get() ?? collect())
        ->reject(
            fn($post) => collect($post->get('categories') ?? [])->contains(fn($c) => (string) $c->slug === 'sosial-media'),
        )
        ->values();

    // Highlight: 1 post terbaru
    $blogHighlight = $blogLatest->take(1);

    // List: 3 post
    $blogList = $blogLatest->slice(1, 3)->values();

    // Jumlah dealer per kategori
    $dealerCounts = collect($dealers ?? [])
        ->groupBy('dealer-category')
        ->map(fn($group) => $group->count());

    // Dealer kategori
    $dealerCategories = gm_terms('dealer_categories')
        ->mapWithKeys(fn($term) => [$term->slug => $term->name])
        ->filter(fn($label, $slug) => ($dealerCounts[$slug] ?? 0) > 0);

    $dealerCategoryCount = $dealerCategories->count();
    $dealerPageUrl = gm_entry('pages', 'dealer')?->url ?? gm_entry('pages', 'dealers')?->url ?? '/dealer';

    // Label informasi dealer
    $dealerLabels =
        sunrice_global('dealer_label_information') ?? collect();

    // Cek component
    $hasHeader = view()->exists('components.layouts.header.header');
    $hasSlider = view()->exists('components.layouts.hero.slider');
    $hasHeroPage = view()->exists('components.layouts.hero.heropage');
    $hasCatProductSkin = view()->exists('components.layouts.skin.category-product-skin');
    $hasFlipService = view()->exists('components.layouts.skin.flip-service-skin');
    $hasDealerMap = view()->exists('components.layouts.dealer-map');
    $hasFooter = view()->exists('components.layouts.footer.footer');
@endphp

<x-layouts.main :body-class="$bodyClass">
    @if ($hasHeader)
        <x-layouts.header.header />
    @endif

    <main>
        @if ($hasSlider)
            <x-layouts.hero.slider />
        @endif

        {{-- Tentang --}}
        @if ($about && ($about['show'] ?? false))
            <section id="{{ $about['anchor'] ?? 'about-us' }}">
                <div class="relative overflow-hidden -mt-14">

                    {{-- Background --}}
                    <div id="background-about"
                        class="overlay-section-about rounded-t-3xl lg:rounded-t-[60px] overflow-hidden">
                        <img src="{{ gm_asset_url($about['section_images']) }}" alt="{{ $about['section_images']?->alt }}"
                            class="w-full h-265 md:h-250 lg:h-224 object-cover pointer-events-none">
                    </div>

                    {{-- Konten --}}
                    <div class="absolute inset-x-0 top-0 z-10 flex items-end bottom-0">
                        <div id="content-about"
                            class="container flex flex-col md:flex-col lg:flex-row gap-15 md:gap-0 lg:gap-0">

                            {{-- Kolom kiri: konten --}}
                            <div class="w-full md:w-full lg:w-[50%] flex flex-col gap-4 z-10">

                                {{-- Heading --}}
                                @if (!empty($about['heading']))
                                    <h2 class="text-(--color-heading)">{{ $about['heading'] }}</h2>
                                @endif

                                {{-- Text --}}
                                @if (!empty($about['text']))
                                    <div class="richtext">{!! $about['text'] !!}</div>
                                @endif

                                {{-- Counter grid --}}
                                @if (!empty($about['counter_grid']))
                                    <div
                                        class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-2 gap-3 lg:gap-5 my-4 md:my-4 lg:my-8 w-full lg:w-[80%]">
                                        @foreach ($about['counter_grid'] as $counter)
                                            <div
                                                class="flex flex-col gap-2 items-start p-4 rounded-xl blur-cus bg-white/30">
                                                <p class="text-3xl lg:text-4xl font-medium text-(--color-primary)">
                                                    <span>{{ $counter['prefix'] ?? '' }}</span><span
                                                        class="counter-number"
                                                        data-target="{{ $counter['number'] ?? 0 }}">0</span><span>{{ $counter['suffix'] ?? '' }}</span>
                                                </p>
                                                <p class="text-(--color-body) text-[11px] md:text-xs lg:text-sm">
                                                    {{ $counter['caption'] ?? '' }}</p>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                {{-- Button --}}
                                @if ($aboutBtn && !empty($about['button']['label']))
                                    <a href="{{ $aboutBtn }}" class="button gap-4 button--primary w-fit">
                                        <span>{{ $about['button']['label'] }}</span>
                                        <svg viewBox="0 0 12 12" fill="none" aria-hidden="true" class="h-4 w-4">
                                            <path d="M4 2L8 6L4 10" stroke="currentColor" stroke-width="1"
                                                stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </a>
                                @endif

                            </div>

                            {{-- Kolom Kanan: image --}}
                            <div
                                class="w-full md:w-full lg:w-[50%] flex flex-col md:items-end lg:items-stretch justify-between gap-8 md:gap-4 lg:gap-4">

                                {{-- Sertifikat --}}
                                @if (!empty($about['images']))
                                    <div
                                        class="grid grid-cols-2 justify-items-start md:flex md:flex-row md:flex-wrap md:justify-end lg:items-center gap-2 lg:gap-6 md:-mt-18 lg:mt-0 w-full md:w-[60%] lg:w-full md:ml-auto lg:ml-auto">
                                        @foreach ($about['images'] as $img)
                                            <div
                                                class="bg-white/50 rounded-lg lg:rounded-2xl p-4 md:p-4 lg:p-4 flex flex-col gap-2 w-full md:w-[40%] lg:w-[30%]">
                                                @if ($img->caption)
                                                    <p>{{ $img->caption }}</p>
                                                @endif
                                                <img src="{{ $img->url() }}" alt="{{ $img->alt }}"
                                                    class="w-full object-contain">
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                {{-- Truk --}}
                                @if (!empty($about['image']))
                                    <div class="md:w-full lg:w-[145%]">
                                        <img src="{{ $about['image']->url() }}"
                                            alt="{{ $about['image']->alt ?? ($about['heading'] ?? '') }}"
                                            class="w-full object-cover lg:-ml-50" />
                                    </div>
                                @endif
                            </div>

                        </div>
                    </div>

                </div>
            </section>
        @endif

        {{-- Produk Kategori --}}
        @if ($productCategory && ($productCategory['show'] ?? false))
            <section id="{{ $productCategory['anchor'] ?? 'category-product' }}">
                <div class="bg-white relative z-20 rounded-t-3xl lg:rounded-t-[60px] -mt-10 lg:-mt-10">
                    <div class="container pt-18 pb-32 md:pt-18 md:pb-32 lg:pt-30 lg:pb-45">
                        <div
                            class="flex flex-col md:flex-row lg:flex-row justify-between items-start md:items-end lg:items-end flex-wrap gap-8 md:gap-8 lg:gap-10">

                            {{-- Heading --}}
                            <div id="heading-product-category" class="flex flex-col gap-2 w-full md:w-[60%] lg:w-[55%]">
                                @if (!empty($productCategory['heading']))
                                    <h2 class="text-(--color-heading)">{{ $productCategory['heading'] }}</h2>
                                @endif
                                @if (!empty($productCategory['description']))
                                    <p class="color-(--color-text)">{{ $productCategory['description'] }}</p>
                                @endif
                            </div>

                            {{-- Button --}}
                            <div id="button-product-category" class="order-last md:order-0 lg:order-0">
                                @if ($productCategoryUrl && !empty($productCategory['label']))
                                    <a href="{{ $productCategoryUrl }}" class="button gap-4 button--primary">
                                        <span>{{ $productCategory['label'] }}</span>
                                        <svg viewBox="0 0 12 12" fill="none" aria-hidden="true" class="h-4 w-4">
                                            <path d="M4 2L8 6L4 10" stroke="currentColor" stroke-width="1"
                                                stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </a>
                                @endif
                            </div>

                            {{-- Kategori Produk --}}
                            @if ($hasCatProductSkin && $productCategories->isNotEmpty())
                                <div class="category-slider relative w-full">

                                    {{-- Arrow Prev --}}
                                    <button type="button"
                                        class="category-prev rounded-full absolute -left-3 lg:-left-7 top-[45%] z-10 w-10 h-10 md:w-10 md:h-10 lg:w-14 lg:h-14 text-(--color-primary) hover:text-white bg-(--color-surface) hover:bg-(--color-primary) p-3 md:p-3 lg:p-4">
                                        <svg class="rotate-180 w-full h-full" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </button>

                                    {{-- Arrow Next --}}
                                    <button type="button"
                                        class="category-next rounded-full absolute -right-3 lg:-right-7 top-[45%] z-10 w-10 h-10 md:w-10 md:h-10 lg:w-14 lg:h-14 text-(--color-primary) hover:text-white bg-(--color-surface) hover:bg-(--color-primary) p-3 md:p-3 lg:p-4">
                                        <svg class="w-full h-full" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </button>

                                    <div class="swiper category-swiper">
                                        <div class="swiper-wrapper">
                                            @foreach ($productCategories as $term)
                                                <div class="swiper-slide h-auto">
                                                    <x-layouts.skin.category-product-skin :term="$term" />
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>

                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </section>
        @endif

        {{-- Layanan --}}
        @if ($services && ($services['show'] ?? false))
            <section id="{{ $services['anchor'] ?? 'layanan' }}">
                <div class="relative z-20 overflow-hidden -mt-14">

                    {{-- Background --}}
                    <div id="background-services"
                        class="overlay-section-services rounded-t-3xl lg:rounded-t-[60px] overflow-hidden">
                        <img src="{{ gm_asset_url($services['background_image']) }}"
                            alt="{{ $services['background_image']?->alt }}"
                            class="w-full h-325 md:h-220 lg:h-190 object-cover pointer-events-none">
                    </div>

                    {{-- Konten --}}
                    <div class="absolute inset-0 z-10 flex items-center">
                        <div id="content-services" class="container flex flex-col gap-8 lg:gap-10">

                            <div id="content-service"
                                class="flex flex-col gap-2 items-start md:items-center lg:items-center">
                                {{-- Heading --}}
                                @if (!empty($services['heading']))
                                    <h2 class="text-(--color-heading) text-start md:text-center lg:text-center">
                                        {{ $services['heading'] }}</h2>
                                @endif

                                {{-- Text --}}
                                @if (!empty($services['description']))
                                    <div
                                        class="richtext text-start md:text-center lg:text-center w-full md:w-[70%] lg:w-[55%]">
                                        {!! $services['description'] !!}</div>
                                @endif
                            </div>

                            @if ($hasFlipService && !empty($services['flip_content']))
                                <div id="flip-services" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                                    @foreach ($services['flip_content'] as $flip)
                                        <x-layouts.skin.flip-service-skin :flip="$flip" />
                                    @endforeach
                                </div>
                            @endif

                        </div>
                    </div>

                </div>
            </section>
        @endif

        {{-- Marketplace --}}
        @if ($marketplace && ($marketplace['show'] ?? false))
            <section id="{{ $marketplace['anchor'] ?? 'marketplace' }}">
                <div class="container">
                    <div class="mb-18 md:mb-18 lg:mb-30 flex flex-col gap-6 md:gap-8 lg:gap-10">

                        <div class="flex gap-6 items-center">
                            {{-- Heading --}}
                            @if (!empty($marketplace['heading']))
                                <p class="text-(--color-primary) uppercase font-medium">
                                    {{ $marketplace['heading'] }}</p>
                                <span class="flex-1 border-t border-[#E8E8E8] flex"></span>
                            @endif
                        </div>

                        {{-- Marketplace --}}
                        @if (!empty($marketplace['marketplace']))
                            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-4 gap-2 md:gap-6 lg:gap-6">
                                @foreach ($marketplace['marketplace'] as $item)
                                    @php
                                        $mpUrl = trim((string) gm_link_url($item['marketplace_url'] ?? null));
                                        $mpValid = $mpUrl && $mpUrl !== '#';
                                    @endphp

                                    @if (!empty($item['marketplace_logo']))
                                        @if ($mpValid)
                                            <a href="{{ $mpUrl }}" target="_blank" rel="noopener noreferrer"
                                                class="transition-opacity hover:opacity-70 border border-(--color-surface) md:border-0 lg:border-0 rounded-xl p-4">
                                                <img src="{{ $item['marketplace_logo']?->url() }}"
                                                    alt="{{ $item['marketplace_logo']?->alt ?? '' }}"
                                                    class="h-10 lg:h-14 object-contain mx-auto">
                                            </a>
                                        @else
                                            <div
                                                class="border border-(--color-surface) md:border-0 lg:border-0 rounded-xl p-4">
                                                <img src="{{ $item['marketplace_logo']?->url() }}"
                                                    alt="{{ $item['marketplace_logo']?->alt ?? '' }}"
                                                    class="h-10 lg:h-14 object-contain mx-auto">
                                            </div>
                                        @endif
                                    @endif
                                @endforeach
                            </div>
                        @endif

                    </div>
                </div>
            </section>
        @endif

        {{-- Dealer --}}
        @if ($dealer && ($dealer['show'] ?? false))
            <section id="{{ $dealer['anchor'] ?? 'dealer' }}">
                <div class="container">
                    <div class="my-18 md:my-18 lg:my-30 flex flex-col gap-8 lg:gap-10">

                        {{-- Heading --}}
                        <div class="flex flex-col md:flex-col lg:flex-row items-center gap-5">
                            <div class="w-full lg:w-[55%]">
                                @if (!empty($dealer['heading']))
                                    <h2 class="text-(--color-heading) w-full md:w-[70%] lg:w-[70%]">
                                        {!! $dealer['heading'] !!}</h2>
                                @endif
                            </div>

                            {{-- Counter --}}
                            @if ($dealerCategoryCount > 0)
                                <div class="w-full lg:w-[45%] grid gap-2 md:gap-4 lg:gap-4"
                                    style="grid-template-columns: repeat({{ $dealerCategoryCount }}, minmax(0, 1fr));">
                                    @foreach ($dealerCategories as $slug => $label)
                                        <a href="{{ $dealerPageUrl . '?' . http_build_query(['dealer_category' => $slug]) . '#dealer-content' }}"
                                            class="group flex flex-col items-start lg:items-center gap-2 lg:gap-4 bg-(--color-surface) hover:bg-(--color-primary) p-4 lg:px-4 lg:py-8 rounded-xl transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-(--color-primary)">
                                            <p
                                                class="text-3xl lg:text-4xl text-(--color-primary) group-hover:text-white group-focus-visible:text-white font-(family-name:--font-display) font-semibold transition-colors">
                                                {{ $dealerCounts[$slug] ?? 0 }}
                                            </p>
                                            <p
                                                class="text-(--color-primary) group-hover:text-white group-focus-visible:text-white text-xs md:text-xs lg:text-sm lg:leading-[1.2rem] transition-colors">
                                                {{ $label }}</p>
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        {{-- Konten --}}
                        @if ($hasDealerMap)
                            <x-layouts.dealer-map :dealers="$dealers" :categories="$dealerCategories" :labels="$dealerLabels" />
                        @endif

                    </div>
                </div>
            </section>
        @endif

        {{-- GM Group --}}
        @if ($groupGm && ($groupGm['show'] ?? false))
            <section id="{{ $groupGm['anchor'] ?? 'gm-group' }}">
                <div class="container">
                    <div class="mb-18 md:mb-18 lg:mb-30 flex flex-col gap-6 lg:gap-8">

                        <div class="flex gap-6 items-center">
                            {{-- Heading --}}
                            @if (!empty($groupGm['heading']))
                                <p class="text-(--color-primary) uppercase font-medium">
                                    {{ $groupGm['heading'] }}</p>
                                <span class="flex-1 border-t border-[#E8E8E8] flex"></span>
                            @endif
                        </div>

                        {{-- Gallery --}}
                        @if (!empty($groupGm['gallery_images']))
                            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-4 gap-2 md:gap-4 lg:gap-6">
                                @foreach ($groupGm['gallery_images'] as $item)
                                    @php $itemUrl = trim((string) gm_link_url($item['url_button'] ?? null)); @endphp

                                    @if ($itemUrl)
                                        <a href="{{ $itemUrl }}" target="_blank" rel="noopener noreferrer"
                                            class="flex flex-col gap-4 items-center lg:justify-center p-3.5 lg:py-5 lg:px-8 border border-(--color-line) rounded-xl">
                                            @if (!empty($item['images']))
                                                <img src="{{ $item['images']?->url() }}"
                                                    alt="{{ $item['label'] ?? '' }}"
                                                    class="w-full h-20 md:h-20 lg:h-25 object-contain">
                                            @endif
                                            @if (!empty($item['label']))
                                                <p
                                                    class="text-center text-(--color-heading) md:text-xs lg:text-xl font-(family-name:--font-display) font-semibold tracking-tight">
                                                    {{ $item['label'] }}</p>
                                            @endif
                                        </a>
                                    @else
                                        <div
                                            class="flex flex-col gap-4 items-center lg:justify-center p-3.5 lg:py-5 lg:px-8 border border-(--color-line) rounded-xl">
                                            @if (!empty($item['images']))
                                                <img src="{{ $item['images']?->url() }}"
                                                    alt="{{ $item['label'] ?? '' }}"
                                                    class="w-full h-20 md:h-20 lg:h-25 object-contain">
                                            @endif
                                            @if (!empty($item['label']))
                                                <p
                                                    class="text-center text-(--color-heading) md:text-xs lg:text-xl font-(family-name:--font-display) font-semibold tracking-tight">
                                                    {{ $item['label'] }}</p>
                                            @endif
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        @endif

                    </div>
                </div>
            </section>
        @endif

        {{-- Blog --}}
        @if ($blogSection && ($blogSection['show'] ?? false))
            <section id="{{ $blogSection['anchor'] ?? 'blog' }}">
                <div class="bg-white">
                    <div class="container my-18 md:my-18 lg:my-30">
                        <div
                            class="flex flex-col md:flex-row lg:flex-row justify-between items-start md:items-end lg:items-end flex-wrap gap-8 md:gap-8 lg:gap-10">

                            {{-- Heading --}}
                            <div id="heading-blog" class="flex flex-col gap-2 w-full md:w-[55%] lg:w-[55%]">
                                @if (!empty($blogSection['heading']))
                                    <h2 class="text-(--color-heading)">{{ $blogSection['heading'] }}</h2>
                                @endif
                                @if (!empty($blogSection['description']))
                                    <p class="color-(--color-text)">{{ $blogSection['description'] }}</p>
                                @endif
                            </div>

                            {{-- Button --}}
                            <div id="button-blog" class="order-last md:order-0 lg:order-0">
                                @if ($blogSection && !empty($blogSection['label']))
                                    <a href="{{ gm_link_url($blogSection['link']) }}" class="button gap-4 button--primary">
                                        <span>{{ $blogSection['label'] }}</span>
                                        <svg viewBox="0 0 12 12" fill="none" aria-hidden="true" class="h-4 w-4">
                                            <path d="M4 2L8 6L4 10" stroke="currentColor" stroke-width="1"
                                                stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </a>
                                @endif
                            </div>

                            {{-- Blog --}}
                            <div class="blog-style flex flex-col md:flex-col lg:flex-row gap-6 w-full">

                                {{-- Highlight Post --}}
                                @if ($blogHighlight->isNotEmpty())
                                    <div class="w-full md:w-full lg:w-[50%] grid grid-cols-1 gap-5">
                                        @foreach ($blogHighlight as $entry)
                                            <x-layouts.skin.blog-skin :entry="$entry" />
                                        @endforeach
                                    </div>
                                @endif

                                {{-- 3 post --}}
                                @if ($blogList->isNotEmpty())
                                    <div class="w-full md:w-full lg:w-[60%] grid grid-cols-1 gap-5">
                                        @foreach ($blogList as $entry)
                                            <x-layouts.skin.blog-hori-skin :entry="$entry" />
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>

                    </div>
                </div>
                </div>
            </section>
        @endif

        {{-- Blog Katagori Media Sosial --}}
        @if ($blogSosmed && ($blogSosmed['show'] ?? false))
            <section id="{{ $blogSosmed['anchor'] ?? 'social-media-blog' }}">
                <div class="container">
                    <div class="my-18 md:my18 lg:my-30 flex flex-col gap-8 lg:gap-10">

                        {{-- Heading --}}
                        <div class="flex flex-col gap-2">
                            @if (!empty($blogSosmed['heading']))
                                <h2 class="text-(--color-heading)">{{ $blogSosmed['heading'] }}</h2>
                            @endif

                            {{-- Text --}}
                            @if (!empty($blogSosmed['description']))
                                <div class="richtext w-full md:w-full lg:w-[50%]">
                                    {!! $blogSosmed['description'] !!}</div>
                            @endif
                        </div>

                        {{-- Konten --}}
                        @if ($sosmedPosts->isNotEmpty())
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                                @foreach ($sosmedPosts as $entry)
                                    <x-layouts.skin.sosmed-blog-skin :entry="$entry" />
                                @endforeach
                            </div>
                        @endif

                    </div>
                </div>
            </section>
        @endif

    </main>
    @if ($hasFooter)
        <x-layouts.footer.footer />
    @endif
</x-layouts.main>
