@php
    $bodyClass = collect([
        'background-grey',
        $pageType === 'entry' ? 'entry' : null,
        isset($collection) ? 'entry-' . $collection->handle : null,
        isset($collection) ? $collection->handle : null,
        isset($entry) ? 'slug-' . $entry->slug : null,
    ])
        ->filter()
        ->implode(' ');

    // Global Label
    $career = sunrice_global('career_label_information');

    // Sidebar Career
    $relatedCareers = gm_entries('careers')
        ?->where('id', '!=', $entry->id)
        ->limit(2)
        ->get() ?? collect();

    $normalizeTerms = function ($field) {
        if ($field instanceof \Illuminate\Support\Collection || is_array($field)) {
            return collect($field)->filter(fn ($term) => is_object($term) && filled($term->name ?? $term->title ?? null));
        }

        if (is_object($field) && filled($field->name ?? $field->title ?? null)) {
            return collect([$field]);
        }

        return collect();
    };

    $employmentLabel = gm_field_label($entry, 'employment_status');
    $hasEmploymentStatus = filled($employmentLabel);

    $locations = $normalizeTerms($entry->get('locations'));
    $hasLocations = $locations->isNotEmpty();
    $locationTitle = $locations->first()?->name ?? $locations->first()?->title;
    $tags = $normalizeTerms($entry->get('tags'));

    // Cek Component
    $hasHeader = view()->exists('components.layouts.header.single-header');
    $hasCareerSkin = view()->exists('components.layouts.skin.career-skin');
    $hasCtaSingle = view()->exists('components.layouts.cta-single-career');
    $hasPopupForm = view()->exists('components.layouts.form.popup-form-career');
    $hasFooter = view()->exists('components.layouts.footer.secondary-footer');
@endphp

<x-layouts.main :body-class="$bodyClass">

    @if ($hasHeader)
        <x-layouts.header.single-header />
    @endif

    {{-- Singel career --}}
    <main>
        <section id="single-career">
            <div class="container mt-14 mb-18 md:mt-10 md:mb-18 lg:mt-20 lg:mb-40">
                <div class="flex flex-col md:flex-row lg:flex-row gap-18 md:gap-6 lg:gap-30">
                    <article class="w-full md:w-[70%] lg:w-[70%]">

                        {{-- Head --}}
                        <header class="flex flex-col gap-4">
                            @if ($hasEmploymentStatus || $hasLocations)
                                <div id="navigation" class="flex gap-6 md:gap-8 lg:gap-8">

                                    {{-- Employment --}}
                                    @if ($hasEmploymentStatus)
                                        <p class="flex items-center gap-2 uppercase text-(--color-primary) font-medium">
                                            @if (!empty($career['icon_employment_status']))
                                                <img src="{{ gm_asset_url($career['icon_employment_status']) }}"
                                                    alt="{{ $career['icon_employment_status']?->alt }}"
                                                    class="w-5 h-5 shrink-0" />
                                            @endif
                                            {{ $employmentLabel }}
                                        </p>
                                    @endif

                                    {{-- Location --}}
                                    @if ($hasLocations)
                                        <p class="flex items-center gap-2 uppercase text-(--color-primary) font-medium">
                                            @if (!empty($career['icon_location']))
                                                <img src="{{ gm_asset_url($career['icon_location']) }}"
                                                    alt="{{ $career['icon_location']?->alt }}"
                                                    class="w-5 h-5 shrink-0" />
                                            @endif
                                            {{ $locationTitle }}
                                        </p>
                                    @endif

                                </div>
                            @endif

                            {{-- Heading Page --}}
                            <h1 class="heading-single">{{ $entry->title }}</h1>
                        </header>

                        {{-- Body --}}
                        <section class="flex flex-col gap-8 lg:gap-10">

                            {{-- Deskripsi --}}
                            @if ($entry->get('description'))
                                <div id="description" class="richtext mt-4 md:mt-4 lg:mt-5">{!! $entry->get('description') !!}
                                </div>
                            @endif

                            {{-- Persyaratan --}}
                            @if ($entry->get('qualifications'))
                                <div id="qualifications" class="richtext custom-heading-blog">
                                    <h2 class="mb-2">
                                        {{ $career['requirements_label'] ?? 'Persyaratan' }}</h2>
                                    {!! $entry->get('qualifications') !!}
                                </div>
                            @endif

                            {{-- Jobdesc --}}
                            @if ($entry->get('jobdesc'))
                                <div id="jobdesc" class="richtext custom-heading-blog">
                                    <h2 class=" mb-2">{{ $career['label_jobdesc'] ?? 'Jobdesc' }}
                                    </h2>
                                    {!! $entry->get('jobdesc') !!}
                                </div>
                            @endif

                            {{-- Tag Career --}}
                            <div id="tag-career"
                                class="flex flex-wrap gap-x-4 gap-y-2 md:gap-x-4 md:gap-y-2 lg:gap-x-8 lg:gap-y-8">
                                @foreach ($tags as $tag)
                                    <p>{{ $tag->name ?? $tag->title }}</p>
                                @endforeach
                            </div>

                            {{-- Button Kirim Lamaran --}}
                            <div id="button-submit">
                                @if ($entry->get('apply_email'))
                                    <a href="mailto:{{ $entry->get('apply_email') }}" class="button button--primary">
                                        {{ $career['label_submit_button'] ?? 'Kirim Lamaran' }}
                                    </a>
                                @elseif ($entry->get('apply_link'))
                                    <a href="{{ $entry->get('apply_link') }}" target="_blank" rel="noopener"
                                        class="button button--primary">
                                        {{ $career['label_submit_button'] ?? 'Lamar Eksternal' }}
                                    </a>
                                @else
                                    <button type="button" class="button button--primary"
                                        onclick="document.getElementById('career-popup').showModal()">
                                        {{ $career['label_submit_button'] ?? 'Kirim Lamaran' }}
                                    </button>
                                @endif
                            </div>

                        </section>
                    </article>

                    {{-- Career Lainnya --}}
                    @if ($relatedCareers->isNotEmpty() && $hasCareerSkin)
                        <aside class="w-full md:w-[40%] lg:w-[35%]">
                            <div class="flex flex-col gap-6">
                                @foreach ($relatedCareers as $relatedCareer)
                                    @includeIf('components.layouts.skin.career-skin', [
                                        'entry' => $relatedCareer,
                                        'career' => $career,
                                    ])
                                @endforeach
                            </div>
                        </aside>
                    @endif
                </div>
            </div>
        </section>


        {{-- Call to Action --}}
        @if ($hasCtaSingle)
            <x-layouts.cta-single-career />
        @endif

        {{-- Popup Form --}}
        @if ($hasPopupForm)
            <x-layouts.form.popup-form-career :job-location="$locationTitle" :job-title="$entry->title" />
        @endif
    </main>

    @if ($hasFooter)
        <x-layouts.footer.secondary-footer />
    @endif

</x-layouts.main>
