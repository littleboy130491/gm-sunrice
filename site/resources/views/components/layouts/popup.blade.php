@php
    $currentEntry = request()->attributes->get('sunrice.page')?->entry ?? $sunricePage?->entry ?? null;
    $currentPageId = $currentEntry?->id;

    $popUps = (gm_entries('pop_up')?->get() ?? collect())
        ->filter(function ($popUp) use ($currentPageId) {
            $locations = $popUp->get('pop_up_location');

            if (blank($locations)) {
                return false;
            }

            $locationIds = collect(is_iterable($locations) ? $locations : [$locations])
                ->map(fn($loc) => $loc instanceof \Sunrice\Models\Entry ? $loc->id : (string) $loc)
                ->filter()
                ->all();

            return in_array($currentPageId, $locationIds, true);
        });
@endphp

@foreach ($popUps as $popUp)
    @php
        $image = $popUp->get('pop_up_image');
        $image = $image instanceof \Illuminate\Support\Collection ? $image->first() : $image;
        $imageUrl = $image?->url();
        $linkUrl = $popUp->get('url')['url'] ?? null;
        $isExternal = $linkUrl && str_starts_with($linkUrl, 'http');

        $popUpKey = 'popup-' . $popUp->id;
    @endphp

    <dialog id="site-popup-{{ $popUp->id }}" class="site-popup" data-popup-key="{{ $popUpKey }}"
        data-auto-open="true">
        <div class="site-popup-inner aspect-square w-full">

            {{-- Icon close --}}
            <button type="button"
                class="site-popup-close focus:outline-none focus:border-0 absolute cursor-pointer border-0 z-10 text-white text-xl right-2 top-1 md:text-3xl md:right-3 md:top-1"
                onclick="this.closest('dialog').close()" aria-label="Tutup">
                &times;
            </button>

            @if ($linkUrl)
                <a href="{{ $linkUrl }}" class="block h-full"
                    @if ($isExternal) target="_blank" rel="noopener noreferrer" @endif>
            @endif

            @if ($imageUrl)
                <img src="{{ $imageUrl }}" alt="{{ $image?->alt ?? $popUp->title ?? '' }}"
                    class="w-full h-full object-cover pointer-events-none block">
            @else
                <div class="p-8 text-center">
                    <h2>{{ $popUp->title ?? '' }}</h2>
                </div>
            @endif

            @if ($linkUrl)
                </a>
            @endif

        </div>
    </dialog>
@endforeach
