@props(['entry', 'blog' => null, 'currentCategory' => null])

@php
    $blog =
        $blog ??
        sunrice_global('blog_label_information');

    $cats = collect($entry->get('categories') ?? []);
    $social = collect($entry->get('social_media') ?? []);

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
@endphp

<article class="group overflow-hidden rounded-3xl bg-(--color-surface) flex flex-col">
    <a href="{{ $cardUrl }}"
        @if ($cardTarget) target="{{ $cardTarget }}" rel="{{ $cardRel }}" @endif
        class="flex flex-col md:flex-row lg:flex-row">

        {{-- Featured Image --}}
        <div class="overflow-hidden w-full md:w-[50%] lg:w-[50%] shrink-0 md:self-stretch md:min-h-56 lg:min-h-52">
            <img src="{{ $entry->get('featured_image')?->url() ?? '' }}"
                alt="{{ $entry->get('featured_image')?->alt ?? $entry->title }}"
                class="w-full h-65 md:h-full object-cover group-hover:scale-110 transition-transform duration-500" />
        </div>

        <div class="p-5 flex flex-col gap-10 md:gap-6 lg:gap-6 justify-between flex-1">

            {{-- Heading --}}
            <div class="richtext custom-heading-blog">
                <p class="text-(--color-heading) title-display text-xl tracking-tight lg:text-2xl">{{ $entry->title }}
                </p>
            </div>

            {{-- Kategori - Tanggal --}}
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-5 uppercase text-(--color-primary) font-medium text-sm lg:text-base">
                    @if ($displayTerms->isNotEmpty())
                        <span>
                            @foreach ($displayTerms as $term)
                                {{ $term->name ?? $term->title }}
                                @unless ($loop->last)
                                    ,
                                @endunless
                            @endforeach
                        </span>
                        @if ($entry->published_at)
                            <span>•</span>
                        @endif
                    @endif
                    @if ($entry->published_at)
                        <span>{{ $entry->published_at->format('d.m.Y') }}</span>
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
