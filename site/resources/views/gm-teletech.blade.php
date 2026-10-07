@php
    $bodyClass = collect([
        $pageType === 'entry' ? 'entry' : null,
        isset($collection) ? 'entry-' . $collection->handle : null,
        isset($collection) ? $collection->handle : null,
        isset($entry) ? 'slug-' . $entry->slug : null,
    ])
        ->filter()
        ->implode(' ');

    $opening = collect($entry->get('sections'))->first(
        fn($section) => (string) ($section->key ?? '') === 'opening-teletech',
    );

    $teletechImage = collect($entry->get('sections'))->first(
        fn($section) => (string) ($section->key ?? '') === 'section-image-teletech',
    );

    $fiturBenefit = collect($entry->get('sections'))->first(
        fn($section) => (string) ($section->key ?? '') === 'section-fitur-benefit',
    );

    $ctaGrid = collect($entry->get('sections'))->first(
        fn($section) => (string) ($section->type ?? '') === 'call_to_action_grid',
    );

    $iconPlaceholderBenefit = collect($entry->get('sections'))->first(
        fn($section) => (string) ($section->key ?? '') === 'icon-placeholder-benefit',
    );

    $iconPlaceholderCta = collect($entry->get('sections'))->first(
        fn($section) => (string) ($section->key ?? '') === 'icon-placeholder-cta-grid',
    );

    $iconBenefitPlaceholder = $iconPlaceholderBenefit['section_images'] ?? null;
    $iconCtaDefault = $iconPlaceholderCta['section_images'] ?? null;

    // Jumlah kolom feature grid
    $columnClassMap = [
        '1' => 'lg:grid-cols-1',
        '2' => 'md:grid-cols-2 lg:grid-cols-2',
        '3' => 'md:grid-cols-2 lg:grid-cols-3',
        '4' => 'md:grid-cols-2 lg:grid-cols-4',
    ];
    $featureColumns = $columnClassMap[(string) ($fiturBenefit['columns'] ?? '3')] ?? 'md:grid-cols-2 lg:grid-cols-3';

    // URL kontak
    $buildContactUrl = function ($kontak) {
        $kontak = trim((string) $kontak);

        // Email
        if (str_contains($kontak, '@')) {
            return 'mailto:' . $kontak;
        }

        // WhatsApp
        $number = preg_replace('/[^0-9]/', '', $kontak);
        return 'https://wa.me/' . $number;
    };

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

        {{-- Deskripsi teletech --}}
        @if ($opening && ($opening['show'] ?? false))
            <section id="{{ $opening['anchor'] ?? 'gm-teletech-desc' }}">
                <div class="container">
                    <div class="flex flex-col items-center my-18 lg:my-30">
                        <div class="text-left md:text-center lg:text-center lg:w-280">{!! $opening['description'] ?? '' !!}</div>
                    </div>
                </div>
            </section>
        @endif

        {{-- Image teletech --}}
        @if ($teletechImage && ($teletechImage['show'] ?? false))
            <section id="{{ $teletechImage['anchor'] ?? 'gm-teletech-map' }}">
                <div class="container">
                    <div class="flex flex-col items-center my-18 lg:my-30">
                        <img src="{{ gm_asset_url($teletechImage['section_images']) }}"
                            alt="{{ $teletechImage['section_images']?->alt ?? $entry->title }}"
                            class="rounded-2xl w-full lg:h-150 object-cover">
                    </div>
                </div>
            </section>
        @endif

        {{-- Fitur & Benefit --}}
        @if ($fiturBenefit && ($fiturBenefit['show'] ?? false) && !empty($fiturBenefit['features']))
            <section id="{{ $fiturBenefit['anchor'] ?? 'fitur-benefit' }}">
                <div class="container">
                    <div class="flex flex-col gap-8 my-18 md:gap-8 md:my-18 lg:gap-10 lg:my-30">

                        <h2 id="title-fitur-benefit">{{ $fiturBenefit['heading'] ?? '' }}</h2>

                        {{-- Grid Fitur & Benefit --}}
                        <div id="fitur-benefit-content" data-equal-height class="grid gap-5 {{ $featureColumns }}">
                            @foreach ($fiturBenefit['features'] as $item)
                                <div
                                    class="flex flex-col gap-10 p-4 bg-(--color-surface) rounded-xl lg:rounded-3xl md:p-4 md:gap-10 lg:p-6 lg:gap-20">
                                    <img src="{{ gm_asset_url($item['icon']) ?: $iconBenefitPlaceholder }}"
                                        alt="{{ $item['icon']?->alt ?? 'Icon' }}" class="w-8 h-8 lg:w-10 lg:h-10">
                                    <div class="flex flex-col gap-2">
                                        <p
                                            class="text-(--color-heading) title-display text-xl tracking-tight lg:text-2xl">
                                            {{ $item['title'] ?? '' }}
                                        </p>
                                        <p>{{ $item['text'] ?? '' }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </section>
        @endif

        {{-- Pemantauan trucks --}}
        @if ($ctaGrid && ($ctaGrid['show'] ?? true))
            <section id="{{ $ctaGrid['anchor'] ?? 'cta-gm-teletech' }}">
                <div class="container">
                    <div class="flex flex-col items-center gap-6 my-18 md:flex-row md:my-18 lg:flex-row lg:my-30">
                        <img src="{{ gm_asset_url($ctaGrid['image_call_to_action']) }}"
                            alt="{{ $ctaGrid['image_call_to_action']?->alt ?? ($ctaGrid['heading'] ?? '') }}"
                            class="md:w-[40%] lg:w-[40%] object-cover">

                        {{-- Konten CTA --}}
                        <div class="flex flex-col gap-4">
                            <h2 class="lg:w-180">{{ $ctaGrid['heading'] ?? '' }}</h2>

                            @if (!empty($ctaGrid['short_description']))
                                <div class="flow">{{ $ctaGrid['short_description'] }}</div>
                            @endif

                            @foreach ($ctaGrid['call_to_action'] ?? [] as $contact)
                                @php
                                    $contactUrl = $buildContactUrl($contact['kontak'] ?? '');
                                    $isWhatsapp = str_starts_with($contactUrl, 'https://wa.me');
                                    $contactIcon = $contact['icon'] ?? null ?: $iconCtaDefault;
                                @endphp
                                <a href="{{ $contactUrl }}"
                                    @if ($isWhatsapp) target="_blank" rel="noopener" @endif
                                    class="group bg-(--color-surface) hover:bg-(--color-secondary) flex justify-between items-center rounded-full p-3 pl-6 md:p-3 md:pl-6 lg:p-3 lg:pl-8 transition-colors">

                                    {{-- Kontak --}}
                                    <div class="lg:flex lg:w-[90%] lg:items-center lg:justify-between">
                                        <p class="font-medium group-hover:text-black transition-colors">
                                            {{ $contact['label'] ?? '' }}
                                        </p>
                                        <span
                                            class="title-display group-hover:text-black -mb-1 transition-colors">{{ $contact['kontak'] ?? '' }}</span>
                                    </div>

                                    {{-- Icon kontak --}}
                                    <div
                                        class="bg-(--color-primary) group-hover:bg-black flex items-center justify-center rounded-full transition-colors w-12 h-12 md:w-12 md:h-12 lg:w-12 lg:h-12">
                                        <img src="{{ gm_asset_url($contactIcon) }}"
                                            alt="{{ $contactIcon?->alt ?? ($contact['label'] ?? '') }}"
                                            class="w-5 h-5">
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            </section>
        @endif

    </main>

    @if ($hasFooter)
        <x-layouts.footer.footer />
    @endif

</x-layouts.main>
