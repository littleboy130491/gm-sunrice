@props(['title', 'image', 'height' => 'min-h-[300px] md:min-h-[300px] lg:min-h-[500px]'])

@php
    // URL gambar utama
    if (is_string($image)) {
        $imageUrl = $image ?: null;
        $imageAlt = $title;
    } else {
        $imageUrl = $image?->url() ?? null;
        $imageAlt = $image?->alt ?? $title;
    }

    // Placeholder global set
    if (!$imageUrl) {
        $placeholderAsset = sunrice_global('hero_page_placeholder')?->section_images;
        $placeholderAsset = $placeholderAsset instanceof \Illuminate\Support\Collection ? $placeholderAsset->first() : $placeholderAsset;
        $imageUrl = $placeholderAsset?->url();
    }
@endphp

<section id="hero-page" class="overflow-hidden">
    <div class="relative {{ $height }}">
        @if ($imageUrl)
            <img src="{{ $imageUrl }}" alt="{{ $imageAlt }}"
                class="absolute inset-0 h-full w-full pointer-events-none object-cover" />
        @endif

        <div class="heropage-overlay absolute inset-0"></div>

        <div class="absolute inset-x-0 bottom-6 ld:bottom-10 z-10">
            <div class="container">
                <h1 class="text-left text-white md:text-center lg:text-center">
                    {{ $title }}
                </h1>
            </div>
        </div>
    </div>
</section>
