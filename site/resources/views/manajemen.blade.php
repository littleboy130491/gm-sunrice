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

    $bgDireksi = gm_asset('manajemen-back.jpg');
    $bgDireksiUrl = $bgDireksi?->url();
    $bgDireksiAlt = $bgDireksi?->alt ?? '';

    $opening = collect($entry->get('sections'))->first(
        fn($section) => (string) ($section->key ?? '') === 'opening-management',
    );

    $direkturUtama = collect($entry->get('sections'))->first(
        fn($section) => (string) ($section->type ?? '') === 'team_primary',
    );

    $teamGrids = collect($entry->get('sections'))->filter(fn($section) => (string) ($section->type ?? '') === 'team_grid');

    $placeholderSection = collect($entry->get('sections'))->first(
        fn($section) => (string) ($section->key ?? '') === 'placeholder-tim',
    );

    $placeholderTim = $placeholderSection?->section_images ?? null;
    $placeholderTim = $placeholderTim instanceof \Illuminate\Support\Collection ? $placeholderTim->first() : $placeholderTim;

    // Cek component
    $hasHeader = view()->exists('components.layouts.header.header');
    $hasHeroPage = view()->exists('components.layouts.hero.heropage');
    $hasFooter = view()->exists('components.layouts.footer.footer');
@endphp

<x-layouts.main :body-class="$bodyClass">
    @if ($hasHeader)
        <x-layouts.header.header />
    @endif

    {{-- Manajemen halaman --}}
    <main>
        @if ($hasHeroPage)
            <x-layouts.hero.heropage :title="$entry->title" :image="$entry->get('featured_image')" />
        @endif

        {{-- Judul halaman --}}
        @if ($opening && ($opening['show'] ?? false))
            <section id="{{ $opening['anchor'] ?? 'manajemen' }}">
                <div class="container">
                    <div class="my-18 md:my-18 lg:my-30 flow flex flex-col gap-4 items-center">
                        <h2 class="text-left md:text-center lg:text-center w-full md:w-120 lg:w-155">
                            {{ $opening['heading'] ?? '' }}
                        </h2>
                        <div class="text-left md:text-center lg:text-center w-full lg:w-220">{!! $opening['description'] ?? '' !!}
                        </div>
                    </div>
                </div>
            </section>
        @endif

        {{-- Manajemen section --}}
        <section id="manajemen-content">
            <div class="container">

                {{-- Kata sambutan --}}
                @if ($direkturUtama && ($direkturUtama['show'] ?? false))
                    @php
                        $direkturImage = $direkturUtama['image'] ?: $placeholderTim;
                        $direkturImageUrl = is_string($direkturImage) ? $direkturImage : $direkturImage?->url();
                        $direkturImageAlt =
                            (is_string($direkturImage) ? null : $direkturImage?->get('alt')) ??
                            ($direkturUtama['name'] ?? '');
                    @endphp
                    <div id="{{ $direkturUtama['anchor'] ?? 'highlight-management' }}"
                        class="flex flex-col-reverse gap-6 bg-white rounded-xl lg:rounded-3xl p-5 md:p-6 lg:p-10 md:flex-row lg:flex-row my-18 md:my-18 lg:my-30">
                        <div class="flex flex-col justify-between gap-8 md:gap-10 lg:gap-2 w-full md:w-[60%] lg:w-[60%]">

                            {{-- Sambutan --}}
                            <div class="flow flex flex-col richtext">
                                {!! $direkturUtama['bio'] ?? '' !!}
                            </div>

                            {{-- Profil --}}
                            <div class="flex flex-col">
                                <p
                                    class="notranslate text-(--color-heading) title-display text-base tracking-tight lg:text-2xl">
                                    {{ $direkturUtama['name'] ?? '' }}</p>
                                <p class="uppercase text-(--color-primary) font-medium text-xs lg:text-base lg:mt-1">
                                    {{ $direkturUtama['role'] ?? '' }}</p>
                            </div>
                        </div>

                        {{-- Background Member --}}
                        <div class="w-full md:w-[40%] lg:w-[40%] relative">
                            <img src="{{ $bgDireksiUrl }}" alt="{{ $bgDireksiAlt ?: $direkturUtama['name'] ?? '' }}"
                                class="image-grayscale pointer-events-none rounded-xl w-full md:h-105 lg:h-118 object-cover">
                            <div class="overlay-bg-management"></div>
                            <div class="flex justify-center">
                                <img src="{{ $direkturImageUrl }}" alt="{{ $direkturImageAlt }}"
                                    class="w-[48%] md:w-[80%] lg:w-[62%] absolute bottom-0 z-3">
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Card manajemen --}}
                @if ($teamGrids->isNotEmpty())
                    <div id="card-manajemen" class="flex flex-col gap-18 md:gap-18 lg:gap-30 my-18 md:my-18 lg:my-30">
                        @foreach ($teamGrids as $grid)
                            @if (($grid['show'] ?? true) && !empty($grid['members']))
                                <div id="{{ $grid['anchor'] ?? 'team-grid-' . $loop->iteration }}"
                                    class="flex flex-col gap-6 md:gap-6 lg:gap-10">
                                    <h2>{{ $grid['heading'] ?? '' }}</h2>
                                    <div
                                        class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-3 gap-y-8 gap-x-2 md:gap-y-10 md:gap-x-4 lg:gap-y-14 lg:gap-x-6">
                                        @foreach ($grid['members'] as $member)
                                            @php
                                                $memberImage = $member['image'] ?: $placeholderTim;
                                                $memberImageUrl = is_string($memberImage)
                                                    ? $memberImage
                                                    : $memberImage?->url();
                                                $memberImageAlt =
                                                    (is_string($memberImage) ? null : $memberImage?->get('alt')) ??
                                                    ($member['name'] ?? '');
                                            @endphp
                                            <div class="flex flex-col gap-3 md:gap-4 lg:gap-6 w-full">

                                                {{-- Background & Image --}}
                                                <div class="relative w-full overflow-hidden rounded-xl">
                                                    <img src="{{ $bgDireksiUrl }}" alt="{{ $bgDireksiAlt }}"
                                                        class="image-grayscale pointer-events-none w-full h-55 md:h-70 lg:h-110 object-cover">

                                                    <div class="overlay-bg-management"></div>

                                                    <img src="{{ $memberImageUrl }}" alt="{{ $memberImageAlt }}"
                                                        class="absolute bottom-0 left-1/2 -translate-x-1/2 h-full w-[70%] md:w-[64%] lg:w-[50%] object-contain object-bottom z-3">
                                                </div>

                                                {{-- Profil member --}}
                                                <div class="flex flex-col gap-1">
                                                    <p
                                                        class="notranslate text-(--color-heading) title-display text-base tracking-tight lg:text-2xl">
                                                        {{ $member['name'] ?? '' }}</p>
                                                    <p
                                                        class="uppercase text-(--color-primary) font-medium text-xs lg:text-base lg:mt-1">
                                                        {{ $member['role'] ?? '' }}</p>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endif
            </div>
        </section>

    </main>
    @if ($hasFooter)
        <x-layouts.footer.footer />
    @endif
</x-layouts.main>
