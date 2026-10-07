@props(['entry', 'blog' => null])

@php
    $blog =
        $blog ??
        sunrice_global('blog_label_information');

    $displayCats = collect($entry->get('categories') ?? [])->take(1);
@endphp

<article class="group">
    <a href="{{ $entry->url }}">
        <div class="flex flex-col gap-3 uppercase text-(--color-primary) font-medium text-sm lg:text-base">

            {{-- Kategori - Tanggal --}}
            <div class="flex gap-5">
                @if ($displayCats->isNotEmpty())
                    <span>
                        @foreach ($displayCats as $category)
                            {{ $category->name ?? $category->title }}
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

            {{-- Heading --}}
            <div class="richtext custom-heading-blog">
                <p
                    class="font-(family-name:--font-display) text-xl md:text-lg lg:text-2xl font-semibold tracking-tight text-(--color-heading) title-display group-hover:text-(--color-primary)">
                    {{ $entry->title }}
                </p>
            </div>
        </div>
    </a>
</article>
