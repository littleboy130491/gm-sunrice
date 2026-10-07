@php
    $bodyClass = collect([
        $pageType === 'entry' ? 'entry' : null,
        isset($collection) ? 'entry-' . $collection->handle : null,
        isset($collection) ? $collection->handle : null,
        isset($entry) ? 'slug-' . $entry->slug : null,
    ])
        ->filter()
        ->implode(' ');

    $dealerSection = collect($entry->get('sections'))->first(
        fn($section) => (string) ($section->key ?? '') === 'opening-dealer',
    );

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

    // Section grid dealer
    $gridDealerSection = collect($entry->get('sections'))->first(
        fn($section) => (string) ($section->type ?? '') === 'grid_dealers_show',
    );

    // Daftar dealer untuk grid (tanpa filter koordinat)
    $dealerList = (gm_entries('dealers')?->where('is_active', 1)->orderBy('title', 'asc')->get() ?? collect())
        ->filter(fn($dealer) => $dealer->get('display_grid_view'))
        ->map(
            fn($dealer) => [
                'title' => $dealer->title,
                'category' => $dealer->get('dealer_categories')?->first()?->slug,
                'address' => $dealer->get('address'),
                'maps_url' => $dealer->get('google_maps_url'),
                'phones' => collect([
                    ['type' => 'phone', 'number' => $dealer->get('phone_number')],
                    ['type' => 'whatsapp', 'number' => $dealer->get('whatsapp_number'), 'link' => $dealer->get('whatsapp_link')],
                ])
                    ->filter(fn($phone) => filled($phone['number']))
                    ->values(),
            ],
        )
        ->values();

    // Kelompokkan dealer per kategori
    $dealerGroups = gm_terms('dealer_categories')
        ->map(
            fn($term) => [
                'title' => $term->name,
                'dealers' => $dealerList->where('category', $term->slug)->values(),
            ],
        )
        ->filter(fn($group) => $group['dealers']->isNotEmpty())
        ->values();

    // Label kategori
    $dealerCategories = gm_terms('dealer_categories')
        ->mapWithKeys(fn($term) => [$term->slug => $term->name]);

    // Label informasi dealer
    $dealerLabels =
        sunrice_global('dealer_label_information') ?? collect();

    // Icon kontak dealer
    $resolveIconUrl = function ($icon) {
        if (!$icon) {
            return null;
        }

        $asset = is_object($icon)
            ? $icon
            : gm_asset($icon);

        return $asset?->url();
    };

    $contactIcons = [
        'phone' => $resolveIconUrl($dealerLabels['icon_phone_dealer'] ?? null),
        'whatsapp' => $resolveIconUrl($dealerLabels['icon_whatsapp_dealer'] ?? null),
    ];

    // Cek component
    $hasHeader = view()->exists('components.layouts.header.header');
    $hasHeroPage = view()->exists('components.layouts.hero.heropage');
    $hasDealerMap = view()->exists('components.layouts.dealer-map');
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

        {{-- Halaman dealer --}}
        @if ($dealerSection && ($dealerSection['show'] ?? false))
            <section id="{{ $dealerSection['anchor'] ?? 'dealer-page' }}">
                <div class="container">
                    <div class="my-18 md:my-18 lg:my-30 flow flex flex-col gap-4 items-center">
                        <h2 class="text-left md:text-center lg:text-center">{{ $dealerSection['heading'] ?? '' }}</h2>
                        <div class="w-full lg:w-160 text-left md:text-center lg:text-center">
                            {!! $dealerSection['description'] ?? '' !!}
                        </div>
                    </div>
                </div>
            </section>
        @endif

        {{-- Maps dealer --}}
        @if ($hasDealerMap)
            <section id="dealer">
                <div class="container">
                    <div class="my-18 md:my-18 lg:my-30">
                        <x-layouts.dealer-map :dealers="$dealers" :categories="$dealerCategories" :labels="$dealerLabels" />
                    </div>
                </div>
            </section>
        @endif

        {{-- Grid dealer --}}
        @if ($gridDealerSection && ($gridDealerSection['show_grid_dealer'] ?? false) && $dealerGroups->isNotEmpty())
            <section id="grid-dealer">
                <div class="container">
                    <div class="my-18 md:my-18 lg:my-30 flex flex-col gap-18 md:gap-18 lg:gap-30">
                        @foreach ($dealerGroups as $group)
                            <div>
                                <h2 class="mb-6 text-2xl md:text-3xl lg:text-[2rem]">{{ $group['title'] }}</h2>

                                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 md:gap-5 lg:grid-cols-4 lg:gap-5">
                                    @foreach ($group['dealers'] as $item)
                                        <div
                                            class="flex flex-col gap-4 p-4 lg:p-5 rounded-xl bg-(--color-surface) h-full justify-between">
                                            <div class="flex flex-col gap-2">
                                                <h3
                                                    class="text-sm lg:text-[0.95rem] leading-snug font-semibold font-(family-name:--font-display)">
                                                    @if (gm_link_url($item['maps_url']))
                                                        <a href="{{ gm_link_url($item['maps_url']) }}" target="_blank"
                                                            rel="noopener noreferrer"
                                                            class="font-(family-name:--font-display) text-black hover:text-(--color-primary) transition-colors">
                                                            {{ $item['title'] }}
                                                        </a>
                                                    @else
                                                        {{ $item['title'] }}
                                                    @endif
                                                </h3>

                                                @if ($item['address'])
                                                    <p
                                                        class="text-xs lg:text-[0.7rem] leading-relaxed text-(--color-text)">
                                                        {!! nl2br(e($item['address'])) !!}</p>
                                                @endif
                                            </div>

                                            @if ($item['phones']->isNotEmpty())
                                                <div class="flex flex-wrap gap-2">
                                                    @foreach ($item['phones'] as $phone)
                                                        @php
                                                            $isWhatsapp = $phone['type'] === 'whatsapp';
                                                            $digits = preg_replace('/[^0-9]/', '', $phone['number']);
                                                            $waNumber = \Illuminate\Support\Str::startsWith(
                                                                $digits,
                                                                '0',
                                                            )
                                                                ? '62' . substr($digits, 1)
                                                                : $digits;
                                                            $href = $isWhatsapp
                                                                ? ($phone['link'] ?:
                                                                'https://wa.me/' . $waNumber)
                                                                : 'tel:' . $digits;
                                                            $iconUrl = $contactIcons[$phone['type']] ?? null;
                                                        @endphp

                                                        <a href="{{ $href }}"
                                                            @if ($isWhatsapp) target="_blank" rel="noopener noreferrer" @endif
                                                            class="group inline-flex items-center gap-1.5 px-3.5 py-1.5 border border-(--color-primary) rounded-full text-[0.7rem] lg:text-[0.72rem] text-(--color-primary) hover:text-black hover:bg-(--color-secondary) hover:border-(--color-secondary) transition-colors">
                                                            @if ($iconUrl)
                                                                <span aria-hidden="true"
                                                                    class="w-3.5 h-3.5 shrink-0 bg-current"
                                                                    style="mask: url('{{ $iconUrl }}') center / contain no-repeat; -webkit-mask: url('{{ $iconUrl }}') center / contain no-repeat;"></span>
                                                            @endif
                                                            {{ $phone['number'] }}
                                                        </a>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
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
