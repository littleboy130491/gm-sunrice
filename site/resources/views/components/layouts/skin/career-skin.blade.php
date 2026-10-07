@props(['entry', 'career' => null])

@php
    $career =
        $career ??
        sunrice_global('career_label_information');

    $entryLocations = collect($entry->get('locations') ?? []);
    $entryLocationTitle = $entryLocations->first()?->name;
@endphp

<a href="{{ $entry->url }}"
    class="group hover:bg-[#E8F1E8] flex flex-col gap-6 md:gap-3 lg:gap-8 rounded-2xl lg:rounded-3xl bg-white p-2 md:p-2 lg:p-2 justify-between">

    <div class="px-3 pt-3 md:px-2 lg:px-4 md:pt-2 lg:pt-4 flex flex-col gap-6 md:gap-3 lg:gap-8">
        <header class="flex flex-col gap-3">

            {{-- Employment & Location --}}
            <div class="flex flex-wrap gap-4 md:gap-6">
                @if (gm_field_label($entry, 'employment_status'))
                    <p
                        class="flex items-center gap-2 uppercase text-(--color-primary) font-medium group-hover:text-black">
                        @if ($career?->icon_employment_status?->url())
                            <img src="{{ $career->icon_employment_status->url() }}"
                                alt="{{ $career->icon_employment_status->alt }}"
                                class="w-4 h-4 shrink-0 -mt-1 group-hover:brightness-0" />
                        @endif
                        {{ gm_field_label($entry, 'employment_status') }}
                    </p>
                @endif

                @if (filled($entryLocationTitle))
                    <p
                        class="flex items-center gap-2 uppercase text-(--color-primary) font-medium group-hover:text-black">
                        @if ($career?->icon_location?->url())
                            <img src="{{ $career->icon_location->url() }}" alt="{{ $career->icon_location->alt }}"
                                class="w-4 h-4 shrink-0 -mt-1 group-hover:brightness-0" />
                        @endif
                        {{ $entryLocationTitle }}
                    </p>
                @endif
            </div>

            {{-- Heading --}}
            <p class="text-(--color-heading) tracking-tight title-display text-2xl">{{ $entry->title }}</p>
        </header>

        <div class="flex flex-col gap-4">

            {{-- Excerpt --}}
            @if ($entry->get('excerpt'))
                <p>{{ $entry->get('excerpt') }}</p>
            @endif

            {{-- Tags --}}
            @if ($entry->get('tags'))
                <div class="flex flex-wrap gap-2">
                    @foreach ($entry->get('tags') as $tag)
                        <p class="rounded-full bg-(--color-surface) px-3.5 py-2 text-xs lg:text-sm">
                            {{ $tag->name ?? $tag->title }}
                        </p>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Button Selengkapnya --}}
    <div
        class="mt-4 bg-(--color-surface) rounded-full flex justify-between items-center py-2 pl-6 pr-2 group-hover:bg-(--color-primary)">
        <p class="uppercase title-display text-(--color-primary) tracking-widest group-hover:text-white">
            {{ $career?->button_label ?? 'Selengkapnya' }}
        </p>
        <p
            class="flex h-10 w-10 md:h-8 md:w-8 lg:h-10 lg:w-10 shrink-0 items-center justify-center rounded-full bg-(--color-primary) group-hover:bg-white">
            <svg class="h-4 w-4 text-white group-hover:text-(--color-primary)" fill="none" viewBox="0 0 24 24"
                stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
            </svg>
        </p>
    </div>

</a>
