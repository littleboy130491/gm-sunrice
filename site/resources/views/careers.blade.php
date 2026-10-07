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

    $career = sunrice_global('career_label_information');

    // Urutan mengikuti pilihan di Global > Career Label Information
    $sortOrderBy = (string) ($career['sort_order_by'] ?? 'tanggal_upload');

    [$sortField, $sortDir] = match ($sortOrderBy) {
        'urutan_tree' => ['sort_order', 'asc'],
        'judul_az' => ['title', 'asc'],
        'judul_za' => ['title', 'desc'],
        default => ['published_at', 'desc'],
    };

    $careers = gm_entries('careers')
        ?->orderBy($sortField, $sortDir)
        ->paginate(9) ?? collect();

    // Cek component
    $hasHeader = view()->exists('components.layouts.header.header');
    $hasHeroPage = view()->exists('components.layouts.hero.heropage');
    $hasCareerSkin = view()->exists('components.layouts.skin.career-skin');
    $hasFooter = view()->exists('components.layouts.footer.secondary-footer');
@endphp

<x-layouts.main :body-class="$bodyClass">

    @if ($hasHeader)
        <x-layouts.header.header />
    @endif

    <main>
        @if ($hasHeroPage)
            <x-layouts.hero.heropage :title="$entry?->title ?? 'Karier'" :image="$entry?->get('featured_image')" />
        @endif

        @if ($hasCareerSkin)
            <section id="careers-listing">
                <div class="container my-18 md:my-18 lg:my-30 flex flex-col gap-20">

                    {{-- Grid 3 kolom --}}
                    <div
                        class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 md:gap-x-4 md:gap-y-10 lg:gap-x-5 lg:gap-y-20">
                        @foreach ($careers as $careerEntry)
                            @includeIf('components.layouts.skin.career-skin', [
                                'entry' => $careerEntry,
                                'career' => $career,
                            ])
                        @endforeach
                    </div>

                    {{-- Pagination --}}
                    @if ($careers->hasPages())
                        <div class="careers-pagination">
                            {{ $careers->onEachSide(1)->links() }}
                        </div>
                    @endif

                </div>
            </section>
        @endif
    </main>

    @if ($hasFooter)
        <x-layouts.footer.secondary-footer />
    @endif

</x-layouts.main>
