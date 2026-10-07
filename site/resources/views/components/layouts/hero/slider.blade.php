@php
    $slider_banner = sunrice_global('hero_banner_slider')?->slider_banner ?? collect();

    $resolveUrl = function ($value) {
        // Link fields hydrate to ['url' => ...] (entry links already resolved).
        if (is_array($value)) {
            return $value['url'] ?? '#';
        }
        return $value ?: '#';
    };

    $sliderHeight = 'min-h-[90svh] md:min-h-[60vh] lg:min-h-screen';
@endphp

{{-- Hero Slider --}}
<section id="hero-banner" class="overflow-hidden">
    <div data-hero-slider class="relative select-none touch-pan-y">

        {{-- Slider Track --}}
        <div class="overflow-hidden">
            <div data-hero-track class="flex transition-transform duration-500 ease-out">

                @foreach ($slider_banner as $slide)
                    @php
                        $videoUrl = $slide['backgound_video'] ?? null;
                        $videoUrl = is_string($videoUrl) ? $videoUrl : null;
                        $youtubeId = null;

                        if ($videoUrl && preg_match('~(?:youtube(?:-nocookie)?\.com/(?:watch\?v=|embed/|shorts/)|youtu\.be/)([A-Za-z0-9_-]{11})~', $videoUrl, $matches)) {
                            $youtubeId = $matches[1];
                        }
                    @endphp

                    {{-- Slide --}}
                    <div data-hero-slide class="min-w-full">
                        <div class="relative overflow-hidden bg-slate-900 {{ $sliderHeight }}">

                            {{-- Background video --}}
                            @if ($youtubeId)
                                <iframe
                                    src="https://www.youtube-nocookie.com/embed/{{ $youtubeId }}?autoplay=1&mute=1&loop=1&playlist={{ $youtubeId }}&controls=0&rel=0&playsinline=1"
                                    title="{{ $slide['heading'] ?? 'Hero video' }}"
                                    class="pointer-events-none absolute inset-0 h-full w-full"
                                    allow="autoplay; encrypted-media; picture-in-picture"
                                    allowfullscreen></iframe>
                            {{-- Background image --}}
                            @elseif (!empty($slide['backgound_image']))
                                <img src="{{ $slide['backgound_image']?->url() ?? $slide['backgound_image'] }}"
                                    alt="{{ $slide['backgound_image']?->alt ?? $slide['heading'] ?? '' }}"
                                    class="pointer-events-none absolute inset-0 h-full w-full object-cover" />
                            @endif

                            {{-- Overlay --}}
                            <div class="hero-banner-overlay absolute inset-0"></div>

                            {{-- Slide Content --}}
                            <div class="absolute inset-0 z-10">
                                <div class="container flex items-center justify-center {{ $sliderHeight }}">
                                    <div class="text-left md:text-center md:w-170 lg:w-200">
                                        <h1 class="max-w-none text-left md:text-center lg:text-center text-white">
                                            {{ $slide['heading'] ?? '' }}
                                        </h1>
                                        <p
                                            class="mx-auto text-left md:text-center lg:text-center mt-2 md:mt-4 lg:mt-4 text-white">
                                            {{ $slide['short_description'] ?? '' }}
                                        </p>
                                        @if (!empty($slide['label_button']))
                                            <a href="{{ $resolveUrl($slide['url_button'] ?? '') }}"
                                                class="button gap-4 button--primary mt-8">
                                                <span>{{ $slide['label_button'] }}</span>
                                                <svg viewBox="0 0 12 12" fill="none" aria-hidden="true"
                                                    class="h-4 w-4">
                                                    <path d="M4 2L8 6L4 10" stroke="currentColor" stroke-width="1"
                                                        stroke-linecap="round" stroke-linejoin="round" />
                                                </svg>
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                @endforeach

            </div>
        </div>

        {{-- Navigasi Dots --}}
        <div class="pointer-events-none absolute inset-x-0 bottom-6 z-10 px-6 sm:px-8 lg:px-10">
            <div class="pointer-events-auto flex items-center gap-3 sm:gap-4">
                @foreach ($slider_banner as $slide)
                    <button type="button" data-hero-dot
                        class="hero-slider-dot {{ $loop->first ? 'is-active' : '' }} h-0.5 flex-1 rounded-full"
                        aria-label="Go to slide {{ $loop->iteration }}"
                        aria-current="{{ $loop->first ? 'true' : 'false' }}"></button>
                @endforeach
            </div>
        </div>

    </div>
</section>
