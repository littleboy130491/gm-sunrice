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

    // Cek component
    $hasHeader = view()->exists('components.layouts.header.single-header');
    $hasFooter = view()->exists('components.layouts.footer.footer');

    // Global label 404
    $error = sunrice_global('page_label_not_found');

    $notFound = $error['not_found'] ?? '404';
    $heading = $error['heading'] ?? '';
    $desc = $error['description'] ?? '';
    $btnLabel = $error['button_label'] ?? '';
    $btnLink = $error['link'] ?? '/';
@endphp

<x-layouts.main>
    @if ($hasHeader)
        <x-layouts.header.single-header />
    @endif

    <section id="error-404">
        <div class="container overflow-hidden">
            <div class="my-18 md:my-18 lg:my-30 flex flex-col items-center relative">
                <div class="relative w-fit">
                    <p
                        class="font-(family-name:--font-display) text-center text-[12rem] md:text-[16rem] lg:text-[25rem] leading-none font-semibold text-(--color-surface)/80">
                        {{ $notFound }}</p>
                    <h2 class="font-(family-name:--font-display) absolute inset-0 m-auto w-fit h-fit text-center">
                        {{ $heading }}</h2>
                </div>
                <div class="w-full flex flex-col gap-3 justify-center items-center z-10 -mt-7 md:-mt-6 lg:-mt-14">
                    <p class="text-center mb-4 w-full md:w-[55%] lg:w-[35%]">{{ $desc }}</p>
                    @if ($btnLabel)
                        <a href="{{ $btnLink }}" class="button button--secondary">{{ $btnLabel }}</a>
                    @endif
                </div>
            </div>
        </div>
    </section>

    @if ($hasFooter)
        <x-layouts.footer.footer />
    @endif
</x-layouts.main>
