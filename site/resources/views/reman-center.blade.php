@php
    $bodyClass = collect([
        $pageType === 'entry' ? 'entry' : null,
        isset($collection) ? 'entry-' . $collection->handle : null,
        isset($collection) ? $collection->handle : null,
        isset($entry) ? 'slug-' . $entry->slug : null,
    ])
        ->filter()
        ->implode(' ');

    $remanSection = collect($entry->get('sections'))->first(
        fn($section) => (string) ($section->key ?? '') === 'opening-reman',
    );

    $imgRemanSection = collect($entry->get('sections'))->first(
        fn($section) => (string) ($section->key ?? '') === 'section-image-reman',
    );

    // Cek component
    $hasHeader = view()->exists('components.layouts.header.header');
    $hasHeroPage = view()->exists('components.layouts.hero.heropage');
    $hasFooter = view()->exists('components.layouts.footer.footer');
@endphp

<x-layouts.main :body-class="$bodyClass">
    @if ($hasHeader)
        <x-layouts.header.header />
    @endif

    <main>
        @if ($hasHeroPage)
            <x-layouts.hero.heropage :title="$entry->title" :image="$entry->get('featured_image')" />
        @endif

        {{-- Halaman Reman Center --}}
        <section id="reman-center">
            <div class="container">
                <div class="flex flex-col items-center my-18 gap-18 lg:my-30 lg:gap-30">

                    @if ($remanSection && ($remanSection['show'] ?? false))
                        <div id="{{ $remanSection['anchor'] ?? 'reman-center' }}"
                            class="text-left md:text-center lg:text-center lg:w-240">{!! $remanSection['description'] ?? '' !!}</div>
                    @endif

                    @if ($imgRemanSection && ($imgRemanSection['show'] ?? false))
                        <img id="{{ $imgRemanSection['anchor'] ?? 'image-reman-center' }}"
                            src="{{ gm_asset_url($imgRemanSection['section_images']) }}" alt=""
                            class="rounded-2xl w-full lg:h-150 object-cover">
                    @endif
                </div>
            </div>
        </section>

    </main>

    @if ($hasFooter)
        <x-layouts.footer.footer />
    @endif
</x-layouts.main>
