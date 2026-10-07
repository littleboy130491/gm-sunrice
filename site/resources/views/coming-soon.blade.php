@php
    $global = sunrice_global('page_preparation');

    // Background image
    $bg = $global?->background_image;
    $bg = $bg instanceof \Illuminate\Support\Collection ? $bg->first() : $bg;
    $bgUrl = $bg instanceof \Sunrice\Models\Asset ? $bg->url() : (is_string($bg) ? $bg : '');

    $heading = $global?->heading ?? 'Segera Hadir';

    // Description
    $descHtml = (string) ($global?->description ?? '');

    // Countdown
    $duration = $global?->duration;
    $target = null;
    if ($duration) {
        try {
            $target = \Carbon\Carbon::parse($duration)->toIso8601String();
        } catch (\Exception $e) {
            $target = null;
        }
    }

    // Global settings
    $settings = sunrice_global('settings');
    $siteTitle = $settings?->site_title ?: config('app.name');

    $favicon_url = $settings?->favicon?->url();

    $locale = $locale ?? app()->getLocale();
@endphp

<!doctype html>
<html lang="{{ $locale }}">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">

    <title>{{ $siteTitle }}</title>

    @if ($favicon_url)
        <link rel="icon" href="{{ $favicon_url }}" sizes="any">
        <link rel="shortcut icon" href="{{ $favicon_url }}">
        <link rel="apple-touch-icon" href="{{ $favicon_url }}">
    @endif

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Oxanium:wght@400;500;600;700;800&family=Poppins:wght@400;500;600;700&display=swap"
        rel="stylesheet" />

    {{-- CSS/JS assets --}}
    @vite('resources/css/app.css')
    @vite('resources/js/coming-soon.js')

    {{-- Custom code head --}}
    @if ($settings?->custom_code_head)
        {!! $settings->custom_code_head !!}
    @endif

    {{-- Google Analytics --}}
    @if ($settings?->google_analytics_measurement_id)
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
    @endif
</head>

<body
    class="coming-soon locale-{{ $locale }} relative flex min-h-dvh items-center justify-center overflow-hidden bg-[#0b0d12] font-sans text-white">
    @if ($settings?->custom_code_body)
        {!! $settings->custom_code_body !!}
    @endif

    {{-- Background --}}
    @if ($bgUrl)
        <img src="{{ $bgUrl }}" alt="{{ $heading }}"
            class="absolute inset-0 z-0 h-full w-full object-cover">
    @endif
    <div
        class="absolute inset-0 z-1 bg-[radial-gradient(120%_120%_at_50%_0%,rgba(11,13,18,.55)_0%,rgba(11,13,18,.85)_60%,rgba(11,13,18,.95)_100%)]">
    </div>

    {{-- Konten --}}
    <div class="container">
        <div
            class="relative z-2 mx-auto w-full max-w-3xl p-6 lg:p-10 text-center border border-black/20 rounded-xl bg-black/20 backdrop-blur-sm">
            {{-- Badge --}}
            <div
                class="mb-7 inline-flex items-center gap-2 rounded-full border border-[#29A829]/40 bg-[#29A829]/15 px-4 py-2 text-xs font-medium uppercase tracking-[0.18em]">
                <span class="relative flex h-2 w-2">
                    <span
                        class="absolute inline-flex h-full w-full animate-ping rounded-full bg-[#29A829] opacity-75"></span>
                    <span class="relative inline-flex h-2 w-2 rounded-full bg-[#29A829]"></span>
                </span>
                Under Construction
            </div>

            {{-- Heading --}}
            <h1
                class="mb-4 font-(family-name:--font-display) text-4xl tracking-tight md:text-5xl lg:tex t-6xl text-white">
                {{ $heading }}
            </h1>

            {{-- Description --}}
            @if ($descHtml)
                <div class="text-cust mx-auto max-w-xl text-lg leading-relaxed">
                    {!! $descHtml !!}
                </div>
            @endif

            {{-- Countdown --}}
            @if ($target)
                <div id="countdown" data-target="{{ $target }}"
                    class="mt-11 grid grid-cols-2 md:grid-cols-4 lg:grid-cols-4 gap-3">
                    @foreach (['days' => 'Hari', 'hours' => 'Jam', 'minutes' => 'Menit', 'seconds' => 'Detik'] as $unit => $label)
                        <div class="rounded-xl border border-white/10 bg-white/5 px-3 py-4 backdrop-blur-sm">
                            <div class="font-(family-name:--font-display) text-3xl tabular-nums md:text-5xl text-(--color-secondary)"
                                data-unit="{{ $unit }}">00</div>
                            <p class="mt-2 block text-xs uppercase tracking-[0.14em] text-white/60">{{ $label }}
                            </p>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</body>

</html>
