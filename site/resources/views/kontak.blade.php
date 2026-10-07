@php
    $bodyClass = collect([
        $pageType === 'entry' ? 'entry' : null,
        isset($collection) ? 'entry-' . $collection->handle : null,
        isset($collection) ? $collection->handle : null,
        isset($entry) ? 'slug-' . $entry->slug : null,
    ])
        ->filter()
        ->implode(' ');

    $globals = sunrice_global('settings');

    // Global label kontak
    $contactLabel = sunrice_global('contact_label_information');

    // Telepon
    $phones = collect($globals['phone_numbers'] ?? [])
        ->filter(fn($item) => ($item['enabled'] ?? false) && !empty($item['number']))
        ->map(fn($item) => ['label' => $item['label'] ?? null, 'number' => $item['number']])
        ->values()
        ->all();

    // Email
    $emails = collect($globals['emails'] ?? [])
        ->filter(fn($item) => ($item['enabled'] ?? false) && !empty($item['email']))
        ->map(fn($item) => ['label' => $item['label'] ?? null, 'email' => $item['email']])
        ->values()
        ->all();

    // Alamat & link maps
    $address = $globals['address'] ?? null;
    $addressUrl = $globals['google_map_link'] ?? null;

    $mapEmbedUrl = null;
    if ($addressUrl) {
        $coord = null;
        if (preg_match('/!3d(-?\d+\.\d+)!4d(-?\d+\.\d+)/', $addressUrl, $m)) {
            $coord = $m[1] . ',' . $m[2];
        } elseif (preg_match('/@(-?\d+\.\d+),(-?\d+\.\d+)/', $addressUrl, $m)) {
            $coord = $m[1] . ',' . $m[2];
        }

        $placeName = null;
        if (preg_match('#/place/([^/@]+)#', $addressUrl, $m)) {
            $placeName = str_replace('+', ' ', urldecode($m[1]));
        }

        $query = null;
        if ($coord && $placeName) {
            $query = $coord . ' (' . $placeName . ')';
        } elseif ($coord) {
            $query = $coord;
        } elseif ($placeName) {
            $query = $placeName;
        }

        if ($query) {
            $mapEmbedUrl = 'https://maps.google.com/maps?q=' . rawurlencode($query) . '&z=17&hl=id&output=embed';
        }
    }

    // Social media
    $socials = collect($globals['social_media'] ?? [])
        ->map(function ($item, $key) {
            $url = $item['url'] ?? null;
            if (!$url) {
                return null;
            }

            $iconRaw = $item['image'] ?? null;

            return [
                'name' => ucfirst($key),
                'link' => $url,
                'icon' => $iconRaw ? asset('assets/' . $iconRaw) : null,
            ];
        })
        ->filter()
        ->values()
        ->all();

    $formText = collect($entry->get('sections'))->first(
        fn($section) => (string) ($section->key ?? '') === 'text-form',
    );

    // Cek component
    $hasHeader = view()->exists('components.layouts.header.header');
    $hasHeroPage = view()->exists('components.layouts.hero.heropage');
    $hasContactForm = view()->exists('components.layouts.form.contact-form');
    $hasFooter = view()->exists('components.layouts.footer.cp-footer');
@endphp

@push('head')
    <statamic:captcha:head>
    @endpush

    <x-layouts.main :body-class="$bodyClass">
        @if ($hasHeader)
            <x-layouts.header.header />
        @endif

        <main>
            @if ($hasHeroPage)
                <x-layouts.hero.heropage :title="$entry->title" :image="$entry->get('featured_image')" />
            @endif

            {{-- Halaman kontak --}}
            <section id="{{ $formText['anchor'] ?? 'kontak' }}">
                <div class="container">
                    <div class="mt-18 flex flex-col-reverse gap-18 lg:my-0 lg:mt-30 lg:flex-row lg:gap-8">

                        {{-- Container left --}}
                        <div id="form-contact" class="lg:w-[70%]">

                            <div id="header-form"
                                class="px-4 py-6 bg-(--color-surface) rounded-2xl flow lg:p-6 lg:rounded-3xl flex flex-col gap-8 lg:gap-10">
                                <div id="text-form">
                                    @if ($formText && ($formText['show'] ?? false))
                                        <h2 class="lg:w-180 mb-2 lg:mb-4">{{ $formText['heading'] ?? '' }}</h2>
                                        <div class="richtext lg:w-150">{!! $formText['description'] ?? '' !!}</div>
                                    @endif
                                </div>

                                {{-- Form halaman kontak --}}
                                <div id="form">
                                    @if ($hasContactForm)
                                        <x-layouts.form.contact-form />
                                    @endif
                                </div>
                            </div>

                        </div>

                        {{-- Container right --}}
                        <div id="contact-information" class="lg:w-[40%]">

                            {{-- Konten kontak --}}
                            <div class="flex flex-col gap-8">

                                {{-- Nomor telepon --}}
                                @if (count($phones) > 0)
                                    <div id="phone-number" class="flex flex-col gap-2">
                                        @if (!empty($contactLabel['phone_number_heading']))
                                            <span
                                                class="title-display text-xl text-(--color-primary)">{{ $contactLabel['phone_number_heading'] }}</span>
                                        @endif
                                        <div class="flex flex-col gap-2">
                                            @foreach ($phones as $phone)
                                                <div class="flex flex-col gap-1">
                                                    @if (!empty($phone['label']))
                                                        <p class="block text-(--color-body)">{{ $phone['label'] }}</p>
                                                    @endif
                                                    <a href="tel:{{ preg_replace('/[^0-9]/', '', $phone['number']) }}"
                                                        class="notranslate text-(--color-body) hover:text-(--color-secondary)">
                                                        {{ $phone['number'] }}
                                                    </a>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                {{-- Email --}}
                                @if (count($emails) > 0)
                                    <div id="email" class="flex flex-col gap-2 border-t border-(--color-line) pt-6">
                                        @if (!empty($contactLabel['email_heading']))
                                            <span
                                                class="title-display text-xl text-(--color-primary)">{{ $contactLabel['email_heading'] }}</span>
                                        @endif
                                        <div class="flex flex-col gap-2">
                                            @foreach ($emails as $email)
                                                <div class="flex flex-col gap-1">
                                                    @if (!empty($email['label']))
                                                        <p class="block text-(--color-body)">{{ $email['label'] }}</p>
                                                    @endif
                                                    <a href="mailto:{{ $email['email'] }}"
                                                        class="notranslate text-(--color-body) hover:text-(--color-secondary)">
                                                        {{ $email['email'] }}
                                                    </a>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                {{-- Alamat kantor --}}
                                @if ($address)
                                    <div id="address-office"
                                        class="flex flex-col gap-2 border-t border-(--color-line) pt-6">
                                        @if (!empty($contactLabel['address_heading']))
                                            <span
                                                class="title-display text-xl text-(--color-primary)">{{ $contactLabel['address_heading'] }}</span>
                                        @endif
                                        @if (!empty($addressUrl))
                                            <a href="{{ $addressUrl }}" target="_blank" rel="noopener noreferrer"
                                                class="text-(--color-body) hover:text-(--color-secondary) lg:w-[90%]">
                                                {{ $address }}
                                            </a>
                                        @else
                                            <p>{{ $address }}</p>
                                        @endif
                                    </div>
                                @endif

                                {{-- Media sosial --}}
                                @if (count($socials) > 0)
                                    <div id="social-media"
                                        class="flex flex-row justify-between items-center lg:items-start lg:flex-col gap-4 border-t border-(--color-line) pt-6">
                                        @if (!empty($contactLabel['social_media_labels']))
                                            <span
                                                class="title-display text-xl text-(--color-primary)">{{ $contactLabel['social_media_labels'] }}</span>
                                        @endif
                                        <div class="flex gap-4 lg:gap-6">
                                            @foreach ($socials as $social)
                                                <a href="{{ gm_link_url($social['link']) }}" target="_blank"
                                                    rel="noopener noreferrer" title="{{ $social['name'] }}"
                                                    class="hover:text-(--color-primary)">
                                                    <span class="social-icon block w-5 h-5 social-icon-grey"
                                                        style="--icon-url: url('{{ $social['icon'] }}');"></span>
                                                </a>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                            </div>

                            {{-- Maps --}}
                            @if ($mapEmbedUrl)
                                <div
                                    class="mt-16 lg:mt-10 h-75 overflow-hidden rounded-2xl lg:rounded-3xl [&_iframe]:w-full [&_iframe]:h-full">
                                    <iframe src="{{ $mapEmbedUrl }}" title="Google Maps" width="600" height="450"
                                        style="border:0;" allowfullscreen loading="lazy"
                                        referrerpolicy="no-referrer-when-downgrade"></iframe>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </section>
        </main>

        @if ($hasFooter)
            <x-layouts.footer.cp-footer />
        @endif
    </x-layouts.main>
