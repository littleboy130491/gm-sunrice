@props(['entry', 'blog' => null, 'currentCategory' => null])

@php
    $blog = $blog ?? sunrice_global('blog_label_information');

    $cats = collect($entry->get('categories') ?? []);

    $social = collect($entry->get('social_media') ?? []);

    // Kategori sosial-media
    if ((string) $currentCategory === 'sosial-media') {
        if ($social->isNotEmpty()) {
            $displayTerms = $social->take(2);
        } else {
            $displayTerms = $cats->filter(fn($c) => (string) $c->slug === 'sosial-media')->take(1);
        }
    } elseif ($currentCategory) {
        $active = $cats->filter(fn($c) => (string) $c->slug === (string) $currentCategory);
        $rest = $cats->reject(fn($c) => (string) $c->slug === (string) $currentCategory);
        $displayTerms = $active->concat($rest)->take(2);
    } else {
        $displayTerms = $cats->take(2);
    }

    // Post kategori sosial-media
    $isSocial = $cats->contains(fn($c) => (string) $c->slug === 'sosial-media');

    $socialLink = '';
    if ($isSocial) {
        $link = $entry->get('social_media_links');
        $socialLink = is_array($link) ? ($link['url'] ?? '') : (is_string($link) ? $link : '');
    }

    $cardUrl = $socialLink !== '' ? $socialLink : $entry->url;

    $cardTarget = $socialLink !== '' ? '_blank' : null;

    $cardRel = $socialLink !== '' ? 'noopener noreferrer' : null;

    $featuredImage = $entry->get('featured_image');
    $featuredImage = $featuredImage instanceof \Illuminate\Support\Collection ? $featuredImage->first() : $featuredImage;
    $placeholder = $blog?->image_placeholders;
    $placeholder = $placeholder instanceof \Illuminate\Support\Collection ? $placeholder->first() : $placeholder;
    $placeholderUrl = $placeholder instanceof \Sunrice\Models\Asset ? $placeholder->url() : (is_string($placeholder) ? $placeholder : '');

    $entryDate = $entry->published_at ?? $entry->get('date');
@endphp

<article class="skin-blog-cust group overflow-hidden rounded-3xl bg-white flex flex-col">
    <a href="{{ $cardUrl }}"
        @if ($cardTarget) target="{{ $cardTarget }}" rel="{{ $cardRel }}" @endif
        class="flex-cust flex flex-col flex-1">

        {{-- Featured Image --}}
        <div class="img-high overflow-hidden">
            <img src="{{ $featuredImage?->url() ?? $placeholderUrl }}"
                alt="{{ $featuredImage?->alt ?? $entry->title }}"
                class="w-full h-60 object-cover group-hover:scale-110 transition-transform duration-500" />
        </div>

        <div class="p-5 flex flex-col gap-10 justify-between flex-1">

            {{-- Heading --}}
            <div class="flex flex-col gap-2 md:gap-2 lg:gap-3">
                <p
                    class="font-(family-name:--font-display) font-semibold text-(--color-heading) text-xl lg:text-2xl tracking-tight">
                    {{ $entry->title }}</p>

                @if ($entry->get('excerpt'))
                    <p>{{ \Illuminate\Support\Str::words($entry->get('excerpt'), 18, '...') }}</p>
                @endif
            </div>

            {{-- Kategori - Tanggal --}}
            <div class="flex items-end justify-between">
                <div class="flex items-center gap-5 uppercase text-(--color-primary) font-medium text-sm lg:text-base">
                    @if ($displayTerms->isNotEmpty())
                        <span>
                            @foreach ($displayTerms as $termItem)
                                {{ $termItem->name ?? $termItem->title }}
                                @unless ($loop->last)
                                    ,
                                @endunless
                            @endforeach
                        </span>
                        @if ($entryDate)
                            <span>•</span>
                        @endif
                    @endif
                    @if ($entryDate)
                        <span>{{ $entryDate->format('d.m.Y') }}</span>
                    @endif
                </div>

                {{-- Chevron --}}
                <span
                    class="shrink-0 text-white group-hover:text-black bg-(--color-primary) group-hover:bg-(--color-secondary) w-10 h-10 rounded-full flex justify-center items-center">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                    </svg>
                </span>
            </div>

        </div>

    </a>
</article>
