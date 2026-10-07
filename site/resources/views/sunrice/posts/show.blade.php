@php
    $bodyClass = collect([
        $pageType === 'entry' ? 'entry' : null,
        isset($collection) ? 'entry-' . $collection->handle : null,
        isset($collection) ? $collection->handle : null,
        isset($entry) ? 'slug-' . $entry->slug : null,
    ])
        ->filter()
        ->implode(' ');

    // Global label blog
    $blog = sunrice_global('blog_label_information');

    // Banner page
    $blogMainImage = gm_entry('pages', 'berita-dan-artikel')?->get('featured_image');

    // Banner category
    $heroImage = $entry->get('hero_banner_image') ?? ($entry->get('featured_image') ?? $blogMainImage);

    // Content opening
    $opening = collect($entry->get('sections') ?? [])->first(
        fn($section) => (string) ($section->key ?? '') === 'opening-blog',
    );

    // Kategori post
    $currentCategorySlugs = collect($entry->get('categories') ?? [])
        ->map(fn($cat) => $cat->slug)
        ->all();

    // Grid post terkait
    $posts = gm_entries('posts')
        ?->where('id', '!=', $entry->id)
        ->orderBy('published_at', 'desc')
        ->get()
        ->when(
            !empty($currentCategorySlugs),
            fn($q) => $q->filter(fn($post) => collect($post->get('categories') ?? [])
                ->contains(fn($c) => in_array($c->slug, $currentCategorySlugs, true)))
        )
        ->reject(
            fn($post) => collect($post->get('categories') ?? [])->contains(fn($c) => (string) $c->slug === 'sosial-media'),
        )
        ->take(3)
        ->values() ?? collect();

    // Sidebar 3 post terbaru
    $latestPosts = gm_entries('posts')
        ?->orderBy('published_at', 'desc')
        ->limit(3)
        ->get() ?? collect();

    // Sidebar kategori
    $categories = gm_terms('categories')
        ->filter(
            fn($term) => (gm_entries('posts')?->whereTerm('categories', $term->slug)->get()->count() ?? 0) > 0,
        );

    $galleryImages = collect($entry->get('gallery') ?? [])->filter();
    $hasGallery = $galleryImages->isNotEmpty();

    // Share icons
    $shareIcons = collect($blog['social_share'] ?? [])->filter(fn($s) => $s['show'] ?? false);
    $currentUrl = urlencode(url($entry->url));
    $currentTitle = urlencode($entry->title);

    $shareLinks = [
        'share-wa' => 'https://wa.me/?text=' . $currentTitle . '%20' . $currentUrl,
        'share-fb' => 'https://www.facebook.com/sharer/sharer.php?u=' . $currentUrl,
        'share-th' => 'https://www.threads.net/intent/post?text=' . $currentTitle . '%20' . $currentUrl,
        'share-x' => 'https://twitter.com/intent/tweet?text=' . $currentTitle . '&url=' . $currentUrl,
        'share-link' => url($entry->url),
    ];

    // Cek component
    $hasHeader = view()->exists('components.layouts.header.single-header');
    $hasHeroPage = view()->exists('components.layouts.hero.heropage');
    $hasBlogSkin = view()->exists('components.layouts.skin.blog-skin');
    $hasBlogNewSkin = view()->exists('components.layouts.skin.blog-new-skin');
    $hasFooter = view()->exists('components.layouts.footer.footer');
@endphp

<x-layouts.main :body-class="$bodyClass">
    @if ($hasHeader)
        <x-layouts.header.single-header />
    @endif

    <main>
        <section id="single-blog">
            <div class="container mt-14 mb-18 md:mt-10 md:mb-18 lg:mt-20 lg:mb-30">
                <article class="flex flex-col gap-8 lg:gap-10">

                    {{-- Heading + Meta data --}}
                    <div id="header-article" class="flex flex-col gap-4">
                        @php
                            $postCats = collect($entry->get('categories') ?? []);
                        @endphp

                        <div class="flex items-center gap-5">

                            {{-- Tanggal --}}
                            @if ($entry->published_at)
                                <p class="uppercase text-(--color-primary) tracking-wider font-medium">
                                    {{ $entry->published_at->format('d.m.Y') }}</p>
                            @endif

                            {{-- Kategori --}}
                            @if ($postCats->isNotEmpty())
                                <div class="flex items-center gap-2">
                                    @foreach ($postCats as $category)
                                        <p
                                            class="uppercase text-(--color-primary) font-(family-name:--font-body) font-medium tracking-wider">
                                            {{ $category->name ?? $category->title }}
                                        </p>
                                        @unless ($loop->last)
                                            <p class="text-(--color-primary) font-medium">,</p>
                                        @endunless
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <h1 class="heading-single text-3xl md:text-3xl lg:text-5xl w-full lg:w-[80%]">
                            {{ $entry->title }}</h1>
                    </div>

                    {{-- Content blog --}}
                    <div id="content-article"
                        class="richtext flex flex-col md:flex-row lg:flex-row gap-18 md:gap-6 lg:gap-6">

                        {{-- Kolom konten utama --}}
                        <div class="w-full md:w-[60%] lg:w-[70%] flex flex-col gap-10">

                            {{-- Gallery / Featured Image --}}
                            @if ($hasGallery)
                                <div class="gallery-wrapper flex flex-col gap-4">

                                    {{-- Gambar besar --}}
                                    <div class="gallery-main rounded-2xl overflow-hidden">
                                        @foreach ($galleryImages as $image)
                                            <div class="gallery-slide {{ $loop->first ? '' : 'hidden' }}">
                                                <figure>
                                                    <img src="{{ $image->url() }}"
                                                        alt="{{ $image->alt ?? $entry->title }}"
                                                        class="w-full aspect-video object-cover" />
                                                    @if ($image->caption)
                                                        <figcaption
                                                            class="px-4 py-3 text-sm text-(--color-body)">
                                                            {{ $image->caption }}
                                                        </figcaption>
                                                    @endif
                                                </figure>
                                            </div>
                                        @endforeach
                                    </div>

                                    {{-- Thumbnail slider + arrow --}}
                                    <div class="flex items-center gap-1 md:gap-1 lg:gap-0">

                                        {{-- Prev --}}
                                        <button type="button"
                                            class="gallery-prev shrink-0 w-4 h-4 md:w-4 md:h-4 lg:w-6 lg:h-6 text-black -ml-4 md:-ml-4 lg:-ml-6">
                                            <svg class="rotate-180 w-full h-full" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor" stroke-width="1.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                            </svg>
                                        </button>

                                        {{-- Track thumbnail (4 tampil) --}}
                                        <div
                                            class="gallery-thumbs-track flex overflow-x-auto scroll-smooth flex-1 scrollbar-none [&::-webkit-scrollbar]:hidden">
                                            @foreach ($galleryImages as $image)
                                                <button type="button"
                                                    class="gallery-thumb shrink-0 mr-3 last:mr-0 w-[calc((100%-3*0.75rem)/4)] rounded-xl overflow-hidden transition-opacity {{ $loop->first ? 'opacity-100' : 'opacity-50' }}">
                                                    <img src="{{ $image->url() }}"
                                                        alt="{{ $image->alt ?? $entry->title }}"
                                                        class="w-full h-15 md:h-15 lg:h-30 object-cover" />
                                                </button>
                                            @endforeach
                                        </div>

                                        {{-- Next --}}
                                        <button type="button"
                                            class="gallery-next shrink-0 w-4 h-4 md:w-4 md:h-4 lg:w-6 lg:h-6 text-black -mr-4 md:-mr-4 lg:-mr-6">
                                            <svg class="w-full h-full" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor" stroke-width="1.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                            </svg>
                                        </button>

                                    </div>

                                </div>
                            @elseif ($entry->get('featured_image'))
                                <x-asset-figure :asset="$entry->get('featured_image')"
                                    :alt="$entry->get('featured_image')->alt ?? $entry->title"
                                    class="w-full h-auto rounded-2xl" />
                            @endif

                            {{-- Content --}}
                            <div class="content-custom richtext">
                                @if ($entry->get('description'))
                                    {!! $entry->get('description') !!}
                                @endif
                            </div>

                        </div>

                        {{-- Sidebar --}}
                        <aside class="w-full md:w-[40%] lg:w-[30%] flex flex-col gap-8">

                            {{-- Kategori --}}
                            @if ($categories->isNotEmpty())
                                <div id="sidebar-categories"
                                    class="bg-(--color-surface) p-4 lg:p-6 flex flex-col gap-6 lg:gap-8 rounded-2xl lg:rounded-3xl">
                                    <p class="uppercase text-black font-medium">
                                        {{ $blog['category_labels'] ?? 'Kategori' }}</p>
                                    <ul class="flex flex-col list-none pl-0 mb-0">
                                        @foreach ($categories as $category)
                                            <li
                                                class="py-4 border-b border-(--color-line) last:border-b-0 first:pt-0 last:pb-0">
                                                <a href="{{ $category->url }}"
                                                    class="text-(--color-body) hover:text-(--color-primary) transition-colors">
                                                    {{ $category->name ?? $category->title }}
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            {{-- Terbaru --}}
                            <div id="sidebar-latest"
                                class="bg-(--color-surface) p-4 lg:p-6 flex flex-col gap-6 lg:gap-8 rounded-2xl lg:rounded-3xl">
                                <p class="uppercase text-black font-medium">
                                    {{ $blog['latest_blog_label'] ?? 'Terbaru' }}</p>
                                @if ($hasBlogNewSkin && $latestPosts->isNotEmpty())
                                    <div class="flex flex-col">
                                        @foreach ($latestPosts as $post)
                                            <div
                                                class="py-8 border-b border-(--color-line) last:border-b-0 first:pt-0 last:pb-0">
                                                <x-layouts.skin.blog-new-skin :entry="$post" />
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            {{-- Share --}}
                            @if ($shareIcons->isNotEmpty())
                                <div id="share-icon"
                                    class="bg-(--color-surface) p-4 lg:p-6 flex gap-6 lg:gap-8 rounded-2xl lg:rounded-3xl items-center justify-between">
                                    <p class="uppercase text-black font-medium">{{ $blog['label_share'] ?? 'Bagikan' }}
                                    </p>
                                    <div class="flex items-center gap-3 lg:gap-4">
                                        @foreach ($shareIcons as $share)
                                            @php
                                                $iconUrl = $share['icon']?->url() ?? null;
                                                $iconKey = pathinfo($share['icon']?->path ?? '', PATHINFO_FILENAME);
                                                $isCopy = str_contains($iconKey, 'link');
                                                $link = $shareLinks[$iconKey] ?? '#';
                                            @endphp

                                            @if ($iconUrl)
                                                @if ($isCopy)
                                                    <a href="#"
                                                        class="share-link shrink-0 relative transition-opacity hover:opacity-70">
                                                        <img src="{{ $iconUrl }}"
                                                            alt="{{ $share['icon']?->alt ?? '' }}"
                                                            class="w-4 h-4 lg:w-5 lg:h-5" />
                                                    </a>
                                                @else
                                                    <a href="{{ $link }}" target="_blank"
                                                        rel="noopener noreferrer"
                                                        class="shrink-0 transition-opacity hover:opacity-70">
                                                        <img src="{{ $iconUrl }}"
                                                            alt="{{ $share['icon']?->alt ?? '' }}"
                                                            class="w-4 h-4 lg:w-5 lg:h-5" />
                                                    </a>
                                                @endif
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                        </aside>
                    </div>

                </article>
            </div>
        </section>

        {{-- Berita terkait --}}
        @if ($hasBlogSkin && $posts->isNotEmpty())
            <section id="related-news" class="bg-(--color-surface)">
                <div class="container pt-18 pb-25 md:pt-18 md:pb-25 -mb-10 lg:pt-30 lg:pb-50 lg:-mb-20">
                    <div class="flex flex-col gap-6 md:gap-6 lg:gap-8">
                        <h2>{{ $blog['related_news_labels'] ?? 'Berita Terkait' }}</h2>
                        <div id="article-grid"
                            class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 md:gap-x-4 md:gap-y-10 lg:gap-x-6 lg:gap-y-16">
                            @foreach ($posts as $post)
                                <x-layouts.skin.blog-skin :entry="$post" />
                            @endforeach
                        </div>
                    </div>
                </div>
            </section>
        @endif
    </main>

    @if ($hasFooter)
        {{-- Section berita terkait berlatar warna, jadi margin atas footer dinetralkan biar tidak ada gap putih --}}
        <div @class(['lg:-mt-50' => $hasBlogSkin && $posts->isNotEmpty()])>
            <x-layouts.footer.footer />
        </div>
    @endif

</x-layouts.main>
