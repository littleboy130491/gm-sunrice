@props([
    'bodyClass' => '',
    'locale' => null,
])

@php
    $locale = $locale ?? app()->getLocale();
    $classes = trim('locale-' . $locale . ' ' . $bodyClass);
    $settings = sunrice_global('settings');

    $favicon = $settings?->favicon;
    $favicon_url = $favicon?->url();

    $showScrollTop = $settings?->get('show_scroll_top') ?? true;
    $scrollTopPosition = $settings?->get('scroll_top_position') === 'left' ? 'left' : 'right';

    $whatsappVisible = $settings?->get('show_button') ?? true;
    $whatsappPosition = $settings?->get('position') === 'right' ? 'right' : 'left';
    $scrollTopSharesSideWithWhatsapp = $whatsappVisible && $whatsappPosition === $scrollTopPosition;
@endphp

@if ($settings?->custom_code_head)
    @push('head')
        {!! $settings->custom_code_head !!}
    @endpush
@endif

@if ($settings?->google_analytics_measurement_id)
    @push('head')
        {{-- Google tag (gtag.js) --}}
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ $settings->google_analytics_measurement_id }}">
        </script>
        <script>
            window.dataLayer = window.dataLayer || [];

            function gtag() {
                dataLayer.push(arguments);
            }
            gtag('js', new Date());
            gtag('config', '{{ $settings->google_analytics_measurement_id }}');
        </script>
    @endpush
@endif

@if ($settings?->custom_code_body)
    @push('body_end')
        {!! $settings->custom_code_body !!}
    @endpush
@endif

@if ($settings?->embed_translate)
    @push('body_end')
        {!! $settings->embed_translate !!}
    @endpush
@endif

<!doctype html>
<html lang="{{ $locale }}">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @if ($favicon_url)
        <link rel="icon" href="{{ $favicon_url }}" sizes="any">
        <link rel="shortcut icon" href="{{ $favicon_url }}">
        <link rel="apple-touch-icon" href="{{ $favicon_url }}">
    @endif

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Oxanium:wght@400;500;600;700;800&family=Poppins:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600;700&display=swap"rel="stylesheet" />

    {{-- SEO meta tags --}}
    <x-sunrice::seo />

    {{-- Main CSS/JS assets --}}
    @vite(['resources/js/app.js', 'resources/css/app.css'])

    @stack('styles')
    @stack('head')
</head>

<body class="{{ $classes }}" {{ $attributes->except('class') }}>
    @stack('body_start')
    {{ $slot }}
    <x-layouts.popup />

    {{-- Scroll to top --}}
    @if ($showScrollTop)
        <button id="scroll-to-top" type="button" aria-label="Scroll to top"
            data-position="{{ $scrollTopPosition }}">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <line x1="12" y1="19" x2="12" y2="5"></line>
                <polyline points="5 12 12 5 19 12"></polyline>
            </svg>
        </button>

        <style>
            @if ($scrollTopPosition === 'left')
                #scroll-to-top[data-position="left"] {
                    right: auto;
                    left: 24px;
                }

                @media (max-width: 640px) {
                    #scroll-to-top[data-position="left"] {
                        right: auto;
                        left: 16px;
                    }
                }
            @endif

            @if ($scrollTopSharesSideWithWhatsapp)
                /* Geser naik > tidak tumpuk floating WhatsApp */
                #scroll-to-top {
                    bottom: 96px;
                }

                @media (max-width: 640px) {
                    #scroll-to-top {
                        bottom: 84px;
                    }
                }
            @endif
        </style>
    @endif

    <x-layouts.floating-whatsapp />

    @stack('scripts')
    @stack('body_end')
</body>

</html>
