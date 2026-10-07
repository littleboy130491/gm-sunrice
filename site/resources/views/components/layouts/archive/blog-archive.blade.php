@php
    // Shared by the blog page (entry) and category term pages (term).
    // $page is the entry on `blog`, the Term on `sunrice.taxonomies.categories.show`.
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

    // Global label blog
    $blog = sunrice_global('blog_label_information');

    $isCategory = isset($term);

    // Banner page
    $blogMainImage = gm_entry('pages', 'berita-dan-artikel')?->get('featured_image');

    // Banner category
    $heroImage = $page?->get('hero_banner_image') ?? ($page?->get('featured_image') ?? $blogMainImage);

    // Content opening
    $opening = collect($entry?->get('sections') ?? [])->first(
        fn($section) => (string) ($section->key ?? '') === 'opening-blog',
    );

    // Urutan mengikuti pilihan di Global > Blog Label Information
    $sortOrderBy = (string) ($blog?->sort_order_by ?? 'tanggal_upload');

    [$sortField, $sortDir] = match ($sortOrderBy) {
        'urutan_tree' => ['sort_order', 'asc'],
        'judul_az' => ['title', 'asc'],
        'judul_za' => ['title', 'desc'],
        default => ['published_at', 'desc'],
    };

    // Grid post
    $postsQuery = gm_entries('posts')?->orderBy($sortField, $sortDir);

    if ($isCategory) {
        $postsQuery?->whereTerm('categories', $term->slug);
    }

    $posts = $postsQuery ? $postsQuery->paginate(8) : collect();

    // Sidebar kategori
    $categories = gm_terms('categories')
        ->filter(function ($termItem) {
            return (gm_entries('posts')?->whereTerm('categories', $termItem->slug)->get()->count() ?? 0) > 0;
        });

    // Sidebar 3 post terbaru
    $latestPosts = gm_entries('posts')?->orderBy('published_at', 'desc')->limit(3)->get() ?? collect();

    // Cek component
    $hasHeader = view()->exists('components.layouts.header.header');
    $hasHeroPage = view()->exists('components.layouts.hero.heropage');
    $hasBlogSkin = view()->exists('components.layouts.skin.blog-skin');
    $hasBlogNewSkin = view()->exists('components.layouts.skin.blog-new-skin');
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
        @if ($opening && ($opening->show ?? false))
            <section id="{{ $opening->anchor ?? 'opening-blog' }}">
                <div class="container">
                    <div class="flex flex-col items-center my-18 lg:my-30 richtext">
                        <h2 class="text-left md:text-center lg:text-center w-full md:w-[80%] lg:w-[50%]">
                            {{ $opening->heading ?? '' }}</h2>
                        <div class="text-left md:text-center lg:text-center w-full md:w-[80%] lg:w-[50%]">
                            {!! $opening->description ?? '' !!}</div>
                    </div>
                </div>
            </section>
        @endif

        {{-- Article + sidebar --}}
        <section id="article-content">
            <div class="container my-18 md:my-18 lg:my-30">
                <div class="flex flex-col md:flex-row lg:flex-row gap-18 md:gap-6 lg:gap-6">

                    {{-- Grid blog --}}
                    <div class="w-full md:w-[60%] lg:w-[70%] flex flex-col gap-20">
                        <div id="article-grid"
                            class="grid grid-cols-1 md:grid-cols-1 lg:grid-cols-2 gap-6 md:gap-x-4 md:gap-y-10 lg:gap-x-6 lg:gap-y-16">
                            @if ($hasBlogSkin && ! is_iterable($posts))
                                {{-- paginator always iterable; guard no-op --}}
                            @endif
                            @if ($hasBlogSkin)
                                @foreach ($posts as $post)
                                    <x-layouts.skin.blog-skin :entry="$post" :current-category="$isCategory ? $term->slug ?? null : null" />
                                @endforeach
                            @endif
                        </div>

                        {{-- Pagination --}}
                        @if ($posts instanceof \Illuminate\Pagination\LengthAwarePaginator && $posts->hasPages())
                            <div class="blog-pagination">
                                {{ $posts->onEachSide(1)->links() }}
                            </div>
                        @endif
                    </div>

                    {{-- Sidebar --}}
                    <aside class="w-full md:w-[40%] lg:w-[30%] flex flex-col gap-6">

                        {{-- Kategori --}}
                        @if ($categories->isNotEmpty())
                            <div id="sidebar-categories"
                                class="bg-white p-4 lg:p-6 flex flex-col gap-6 lg:gap-8 rounded-2xl lg:rounded-3xl">
                                <p class="uppercase text-black font-medium">{{ $blog?->category_labels ?? 'Kategori' }}
                                </p>
                                <ul class="flex flex-col list-none pl-0 mb-0">
                                    @foreach ($categories as $category)
                                        @php $isActive = ($term->slug ?? null) === $category->slug; @endphp
                                        <li
                                            class="py-4 border-b border-(--color-line) last:border-b-0 first:pt-0 last:pb-0">
                                            <a href="{{ $category->url }}"
                                                class="transition-colors hover:text-(--color-primary) {{ $isActive ? 'text-(--color-primary)' : 'text-(--color-body)' }}">
                                                {{ $category->name }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        {{-- Blog terbaru --}}
                        <div id="sidebar-latest"
                            class="bg-white p-4 lg:p-6 flex flex-col gap-6 lg:gap-8 rounded-2xl lg:rounded-3xl">
                            <p class="uppercase text-black font-medium">{{ $blog?->latest_blog_label ?? 'Terbaru' }}
                            </p>
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

                    </aside>
                </div>
            </div>
        </section>

    </main>

    @if ($hasFooter)
        <x-layouts.footer.footer />
    @endif
</x-layouts.main>
