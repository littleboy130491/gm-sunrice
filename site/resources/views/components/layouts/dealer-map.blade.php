@props([
    'dealers' => [],
    'categories' => [],
    'labels' => [],
])

@php
    $petaLabel = $labels['peta_label'] ?? 'Peta';
    $satelitLabel = $labels['satelit_label'] ?? 'Satelit';
    $placeholderSearch = $labels['placeholder_search'] ?? 'Ketik Kota';
    $allLabel = $labels['all_label'] ?? 'Semua';
    $emptySearchLabel =
        $labels['empty_search_label'] ?? 'Dealer tidak ditemukan. Coba kata kunci atau filter lain.';

    $dealerCounts = collect($dealers)->groupBy('dealer-category')->map(fn($group) => $group->count());

    $categories = collect($categories)->filter(fn($label, $slug) => ($dealerCounts[$slug] ?? 0) > 0);

    $hasCategories = $categories->isNotEmpty();

    // Icon marker maps
    $iconMaps = $labels['icon_maps_dealer'] ?? null;

    $iconMapsUrl = null;
    if ($iconMaps) {
        if (is_object($iconMaps)) {
            $iconMapsAsset = $iconMaps;
        } else {
            $iconMapsAsset = gm_asset($iconMaps);
        }

        $iconMapsUrl = $iconMapsAsset ? $iconMapsAsset->url() : null;
    }
@endphp

{{-- Filter Kategori & Search --}}
<div id="dealer-content" class="scroll-mt-24 lg:scroll-mt-28">
    <div
        class="relative z-1000 flex flex-col gap-4 md:items-center md:flex-row-reverse md:justify-between lg:items-center lg:flex-row-reverse lg:justify-between mb-6">

        {{-- Search Kota --}}
        <div id="city-search" class="w-full md:w-[50%] lg:w-[25%] flex justify-end">
            <div class="flex w-full rounded-lg overflow-hidden border border-(--color-line)">
                <input type="text" id="dealer-search" placeholder="{{ $placeholderSearch }}"
                    class="py-3 px-4 w-full outline-none text-sm font-(family-name:--font-body)"
                    autocomplete="off" />
                <button type="button" id="dealer-search-btn" aria-label="Cari"
                    class="group flex items-center justify-center px-3.5 bg-(--color-primary) hover:bg-black shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                        class="h-5 w-5 text-white">
                        <circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2" />
                        <path d="M16.5 16.5L21 21" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                    </svg>
                </button>
            </div>
        </div>

        {{-- Kategori Dealer --}}
        @if ($hasCategories)

            {{-- Desktop: Button --}}
            <div id="dealer-category-filter" class="hidden flex-col gap-2 lg:flex lg:flex-row">
                @foreach ($categories as $slug => $label)
                    <button type="button"
                        class="dealer-cat-btn flex items-center gap-2 text-sm text-(--color-primary) hover:text-white bg-(--color-surface) hover:bg-(--color-primary) uppercase py-3 px-8 rounded-full cursor-pointer"
                        data-category="{{ $slug }}">
                        <span class="font-medium">{{ $label }}</span>
                        <svg viewBox="0 0 12 12" fill="none" aria-hidden="true" class="h-4 w-4">
                            <path d="M4 2L8 6L4 10" stroke="currentColor" stroke-width="1" stroke-linecap="round"
                                stroke-linejoin="round" />
                        </svg>
                    </button>
                @endforeach
            </div>

            {{-- Mobile: Dropdown --}}
            <select id="dealer-category-select"
                class="contact-form-input lg:hidden w-full rounded-xl px-5 py-4 text-sm border border-(--color-line)">
                <option value="all">{{ $allLabel }}</option>
                @foreach ($categories as $slug => $label)
                    <option value="{{ $slug }}">{{ $label }}</option>
                @endforeach
            </select>

        @endif

    </div>

    <p id="dealer-search-empty" class="dealer-search-empty mb-4 hidden" role="status" aria-live="polite">
        {{ $emptySearchLabel }}
    </p>

    {{-- Map --}}
    <div id="dealer-map" style="height: 350px; width: 100%; border-radius: 24px;" class="md:h-120!"></div>
</div>

{{-- Share data lokasi > dealer-map.js --}}
<script>
    window.dealerLocations = @json($dealers);
    window.dealerCategoryLabels = @json($categories);
    window.dealerMapLabels = {
        peta: @json($petaLabel),
        satelit: @json($satelitLabel),
        emptySearch: @json($emptySearchLabel),
    };
    window.dealerMapIcon = @json($iconMapsUrl);
</script>
