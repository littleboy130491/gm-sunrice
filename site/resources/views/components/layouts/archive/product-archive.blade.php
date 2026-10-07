@php
    // Shared by the produk page (entry) and product_categories / industries term pages.
    $page = $entry ?? $term ?? null;

    $bodyClass = collect([
        'background-grey',
        $pageType === 'entry' ? 'entry' : null,
        isset($collection) ? 'entry-' . $collection->handle : null,
        isset($collection) ? $collection->handle : null,
        isset($page) ? 'slug-' . $page->slug : null,
    ])
        ->filter()
        ->implode(' ');

    // Global label product
    $product = sunrice_global('product_label_information');

    $isCategory = isset($term);

    // Halaman arsip produk
    $productArchive = gm_entry('pages', 'produk');

    $productMainImage = $productArchive?->get('featured_image');
    $productArchiveUrl = $productArchive?->url ?? '/produk';

    // Banner category
    $heroImage = $page?->get('hero_banner_image') ?? ($page?->get('featured_image') ?? $productMainImage);

    // Content opening
    $opening = collect($entry?->get('sections') ?? [])->first(
        fn($section) => (string) ($section->key ?? '') === 'opening-product',
    );

    // Grid produk
    $productsQuery = gm_entries('products');

    if ($isCategory) {
        $taxonomyHandle = $taxonomy->handle; // product_categories atau industries
        $productsQuery?->whereTerm($taxonomyHandle, $term->slug);
    }

    // Urutan produk mengikuti pilihan di Global > Product Label Information
    $sortOrderBy = (string) ($product?->product_sort_order_by ?? 'tanggal_upload');

    [$sortField, $sortDir] = match ($sortOrderBy) {
        'urutan_tree' => ['sort_order', 'asc'],
        'judul_az' => ['title', 'asc'],
        'judul_za' => ['title', 'desc'],
        default => ['published_at', 'desc'],
    };

    $products = $productsQuery ? $productsQuery->orderBy($sortField, $sortDir)->paginate(18) : collect();

    // Sidebar kategori / industri: hanya term yang punya produk
    $product_categories = gm_terms('product_categories')
        ->filter(fn($termItem) => (gm_entries('products')?->whereTerm('product_categories', $termItem->slug)->get()->count() ?? 0) > 0);

    $industries = gm_terms('industries')
        ->filter(fn($termItem) => (gm_entries('products')?->whereTerm('industries', $termItem->slug)->get()->count() ?? 0) > 0);

    $hasSidebar = $product_categories->isNotEmpty() || $industries->isNotEmpty();

    // Cek component
    $hasHeader = view()->exists('components.layouts.header.header');
    $hasHeroPage = view()->exists('components.layouts.hero.heropage');
    $hasProductSkin = view()->exists('components.layouts.skin.product-skin');
    $hasFooter = view()->exists('components.layouts.footer.footer');
@endphp

<x-layouts.main :body-class="$bodyClass">
    @if ($hasHeader)
        <x-layouts.header.header />
    @endif

    <main>
        @if ($hasHeroPage)
            <x-layouts.hero.heropage :title="$page?->title ?? $page?->name" :image="$heroImage" />
        @endif

        {{-- Text opening --}}
        @if ($opening && ($opening->show ?? false) && $products->isNotEmpty())
            <section id="{{ $opening->anchor ?? 'opening-product' }}">
                <div class="container">
                    <div class="flex flex-col items-center my-18 lg:my-30 richtext">
                        <h2 class="text-left md:text-center lg:text-center w-full md:w-[80%] lg:w-[50%]">
                            {{ $opening->heading ?? '' }}</h2>
                        <div class="text-left md:text-center lg:text-center w-full md:w-[80%] lg:w-[40%]">
                            {!! $opening->description ?? '' !!}</div>
                    </div>
                </div>
            </section>
        @endif

        {{-- Product + sidebar --}}
        <section id="product-content">
            <div class="container my-18 md:my-18 lg:my-30">
                <div class="flex flex-col-reverse md:flex-row lg:flex-row gap-18 md:gap-5 lg:gap-5">

                    {{-- Sidebar (kiri) --}}
                    @if ($hasSidebar)
                        <aside class="w-full md:w-[30%] lg:w-[25%] flex flex-col gap-5">

                            {{-- Kategori produk --}}
                            @if ($product_categories->isNotEmpty())
                                <div id="sidebar-categories"
                                    class="bg-white p-4 lg:p-6 flex flex-col gap-6 lg:gap-8 rounded-xl lg:rounded-3xl">
                                    <p class="uppercase text-black font-medium">
                                        {{ $product?->category_labels ?? 'Kategori' }}
                                    </p>
                                    <ul class="flex flex-col list-none pl-0 mb-0">
                                        @foreach ($product_categories as $category)
                                            @php $isActive = ($term->slug ?? null) === $category->slug; @endphp
                                            <li
                                                class="text-sm py-4 border-b border-(--color-line) last:border-b-0 first:pt-0 last:pb-0">
                                                <a href="{{ $category->url }}"
                                                    class="transition-colors hover:text-(--color-primary) {{ $isActive ? 'text-(--color-primary)' : 'text-(--color-body)' }}">
                                                    {{ $category->name }}
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            {{-- Industri --}}
                            @if ($industries->isNotEmpty())
                                <div id="sidebar-industries"
                                    class="bg-white p-4 lg:p-6 flex flex-col gap-6 lg:gap-8 rounded-xl lg:rounded-3xl">
                                    <p class="uppercase text-black font-medium">
                                        {{ $product?->industry_labels ?? 'Industri' }}
                                    </p>
                                    <ul class="flex flex-col list-none pl-0 mb-0">
                                        @foreach ($industries as $industry)
                                            @php $isActive = ($term->slug ?? null) === $industry->slug; @endphp
                                            <li
                                                class="text-sm py-4 border-b border-(--color-line) last:border-b-0 first:pt-0 last:pb-0">
                                                <a href="{{ $industry->url }}"
                                                    class="transition-colors hover:text-(--color-primary) {{ $isActive ? 'text-(--color-primary)' : 'text-(--color-body)' }}">
                                                    {{ $industry->name }}
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                        </aside>
                    @endif

                    {{-- Grid card produk (kanan) --}}
                    <div class="w-full flex flex-col gap-20 {{ $hasSidebar ? 'md:w-[70%] lg:w-[75%]' : '' }}">
                        @if ($products->isNotEmpty())
                            <div id="product-grid"
                                class="grid grid-cols-2 md:grid-cols-2 lg:grid-cols-3 gap-x-4 gap-y-10 md:gap-x-4 md:gap-y-10 lg:gap-x-5 lg:gap-y-16">
                                @if ($hasProductSkin)
                                    @foreach ($products as $productEntry)
                                        <x-layouts.skin.product-skin :entry="$productEntry" />
                                    @endforeach
                                @endif
                            </div>

                            {{-- Pagination --}}
                            @if ($products instanceof \Illuminate\Pagination\LengthAwarePaginator && $products->hasPages())
                                <div class="blog-pagination">
                                    {{ $products->onEachSide(1)->links() }}
                                </div>
                            @endif
                        @else
                            {{-- Not found --}}
                            <div id="product-not-found"
                                class="bg-white rounded-xl lg:rounded-3xl px-6 py-14 lg:px-10 lg:py-20 flex flex-col items-center text-center gap-5">

                                <span
                                    class="shrink-0 w-16 h-16 rounded-full bg-(--color-surface) text-(--color-primary) flex items-center justify-center">
                                    <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                        stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M21 21l-4.35-4.35M11 19a8 8 0 110-16 8 8 0 010 16z" />
                                    </svg>
                                </span>

                                <div class="richtext max-w-xl [&_p]:text-(--color-body)">
                                    {!! $product?->product_not_found ?? '<p>Produk tidak ditemukan.</p>' !!}
                                </div>

                                {{-- Kembali ke semua produk > filter kategori --}}
                                @if ($isCategory)
                                    <a href="{{ $productArchiveUrl }}" class="button button--primary">
                                        {{ $product?->all_products_labels ?? 'Lihat Semua Produk' }}
                                    </a>
                                @endif
                            </div>
                        @endif
                    </div>

                </div>
            </div>
        </section>

    </main>

    @if ($hasFooter)
        <x-layouts.footer.footer />
    @endif
</x-layouts.main>
