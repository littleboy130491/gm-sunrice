@php
    $sectionBg = match ($block->background ?? 'default') {
        'muted' => 'bg-emerald-700',
        'dark' => 'bg-zinc-900',
        default => 'bg-emerald-600',
    };
@endphp

<section
    @if ($block->anchor) id="{{ $block->anchor }}" @endif
    class="relative overflow-hidden px-4 py-12 text-white sm:px-6 lg:py-16 {{ $sectionBg }}"
>
    @if ($block->background_image)
        @foreach ($block->background_image as $image)
            <img src="{{ $image->url() }}" alt="" class="absolute inset-0 size-full object-cover opacity-30">
        @endforeach
    @endif
    <div class="relative mx-auto grid max-w-6xl items-center gap-10 lg:grid-cols-2">
        <div class="flex items-end gap-6">
            @if ($block->side_image)
                @foreach ($block->side_image as $image)
                    <img src="{{ $image->url() }}" alt="{{ $image->alt ?? '' }}" class="max-h-48 w-auto object-contain">
                @endforeach
            @endif
            @if ($block->visual_image)
                @foreach ($block->visual_image as $image)
                    <img src="{{ $image->url() }}" alt="{{ $image->alt ?? '' }}" class="max-h-64 w-auto object-contain">
                @endforeach
            @endif
        </div>
        <div>
            @if ($block->heading)
                <h2 class="text-2xl font-bold uppercase tracking-tight sm:text-3xl">{{ $block->heading }}</h2>
            @endif
            @if ($block->text)
                <p class="mt-3 text-emerald-50">{{ $block->text }}</p>
            @endif
            @if ($block->contact_label || $block->phone)
                <div class="mt-6">
                    @if ($block->contact_label)
                        <p class="text-sm text-emerald-100">{{ $block->contact_label }}</p>
                    @endif
                    @if ($block->phone)
                        <p class="text-2xl font-bold">
                            <a href="tel:{{ preg_replace('/[^0-9]/', '', $block->phone) }}" class="notranslate hover:underline">{{ $block->phone }}</a>
                        </p>
                    @endif
                    @if ($block->whatsapp_link)
                        <a href="{{ $block->whatsapp_link }}" class="mt-2 inline-block text-sm font-semibold hover:underline" target="_blank" rel="noopener">WhatsApp</a>
                    @endif
                </div>
            @endif
            @if ($block->social_links)
                <div class="mt-6 flex flex-wrap gap-3">
                    @foreach ($block->social_links as $social)
                        @if (gm_link_url($social['url']))
                            <a href="{{ gm_link_url($social['url']) }}" class="rounded-full bg-white/20 px-3 py-1 text-sm font-medium hover:bg-white/30" target="_blank" rel="noopener">
                                {{ $social['platform'] ?? 'Link' }}
                            </a>
                        @endif
                    @endforeach
                </div>
            @endif
            @if ($block->button?->label && $block->button?->link)
                <div class="mt-6">
                    <x-block-button
                        :label="$block->button->label"
                        :link="$block->button->link"
                        :style="$block->button->style ?? 'secondary'"
                    />
                </div>
            @endif
        </div>
    </div>
</section>
