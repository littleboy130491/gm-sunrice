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

    $sertifikatOpening = collect($entry->get('sections'))->first(
        fn($section) => (string) ($section->key ?? '') === 'opening-sertifikat',
    );

    $certificateGallery = collect($entry->get('sections'))->first(
        fn($section) => (string) ($section->key ?? '') === 'certificate',
    );

    // Global label
    $achievements = sunrice_global('achievements_label_information');

    $certificates = gm_entries('achievements')?->get() ?? collect();

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

        {{-- Heading Sertifikat --}}
        @if ($sertifikatOpening && ($sertifikatOpening['show'] ?? false))
            <section id="{{ $sertifikatOpening['anchor'] ?? 'sertification-heading' }}">
                <div class="container my-18 md:my-18 lg:my-30">
                    <div class="flow flex flex-col gap-2 md:gap-2 lg:gap-3 items-left md:items-center lg:items-center">

                        <h2 class="text-left w-[90%] md:text-center md:w-full lg:w-full lg:text-center">
                            {{ $sertifikatOpening['heading'] ?? '' }}
                        </h2>

                        <div class="text-left md:text-center lg:text-center lg:w-[55%]">{!! $sertifikatOpening['description'] ?? '' !!}</div>
                    </div>
                </div>
            </section>
        @endif

        {{-- Galeri Sertifikat --}}
        @if ($certificates->isNotEmpty())
            <section id="{{ $certificateGallery['anchor'] ?? 'sertification-gallery' }}">
                <div class="container my-18 md:my-18 lg:my-30">
                    <div id="certificate-gallery"
                        class="grid grid-cols-2 gap-x-2 gap-y-8 md:grid-cols-4 lg:grid-cols-4 lg:gap-x-5 lg:gap-y-20">
                        @foreach ($certificates as $certificate)
                            @php
                                $certImg = $certificate->get('featured_image');
                                $placeholder = $achievements?->placeholder_image;
                                $placeholderUrl = $placeholder instanceof \Sunrice\Models\Asset ? $placeholder->url() : (is_string($placeholder) ? $placeholder : '');
                                $img = $certImg?->url() ?? $placeholderUrl;
                            @endphp
                            <div class="certificate-item">
                                <a data-fslightbox="certificates" href="{{ $img }}">
                                    <img src="{{ $img }}"
                                        alt="{{ $certImg?->alt ?? $certificate->title }}"
                                        class="w-full h-auto object-cover rounded-md mb-3 lg:mb-4">
                                </a>
                                <p class="text-(--color-heading) title-display text-base tracking-tight lg:text-2xl">
                                    {{ $certificate->title }}</p>
                                @php
                                    $certificateYears = $certificate->get('years') ?? [];
                                    $certificateYears =
                                        $certificateYears instanceof \Illuminate\Support\Collection ||
                                        is_array($certificateYears)
                                            ? collect($certificateYears)
                                            : collect([$certificateYears]);
                                @endphp
                                <p
                                    class="uppercase text-(--color-primary) font-medium group-hover/card:text-(--color-secondary) text-xs lg:text-base lg:mt-1">
                                    @foreach ($certificateYears->filter() as $year)
                                        {{ data_get($year, 'title', is_scalar($year) ? $year : '') }}
                                        @unless ($loop->last)
                                            ,
                                        @endunless
                                    @endforeach
                                </p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

    </main>

    @if ($hasFooter)
        <x-layouts.footer.footer />
    @endif
</x-layouts.main>
