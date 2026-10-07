<x-layouts.app>
    <article class="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:py-16">
        <header class="mb-8">
            @if ($entry->get('dealer_categories'))
                <p class="text-sm font-semibold uppercase tracking-widest text-emerald-600">
                    @foreach ($entry->get('dealer_categories') as $category)
                        {{ $category->name ?? $category->title }}@unless ($loop->last)
                        ,
                    @endunless
                @endforeach
            </p>
        @endif

        <h1 class="mt-3 text-3xl font-bold tracking-tight text-zinc-900 sm:text-4xl">
            {{ $entry->title }}
        </h1>

        @if ($entry->get('city') || $entry->get('region') || $entry->get('country'))
            <p class="mt-2 text-zinc-600">
                {{ collect([$entry->get('city'), $entry->get('region'), $entry->get('country')])->filter()->implode(', ') }}
            </p>
        @endif
    </header>

    @php $dealerLocation = $entry->get('location'); @endphp
    @if (($dealerLocation['latitude'] ?? null) && ($dealerLocation['longitude'] ?? null))
        <div id="dealer-map" class="mb-8 aspect-video w-full rounded-xl bg-zinc-100"
            data-latitude="{{ $dealerLocation['latitude'] }}" data-longitude="{{ $dealerLocation['longitude'] }}"
            data-zoom="{{ $dealerLocation['map_zoom'] ?? 14 }}" data-title="{{ $entry->title }}"></div>
    @endif

    <dl class="space-y-6">
        @if ($entry->get('address'))
            <div>
                <dt class="text-sm font-semibold uppercase tracking-wide text-zinc-500">Address</dt>
                <dd class="mt-1 whitespace-pre-line text-zinc-700">{{ $entry->get('address') }}</dd>
            </div>
        @endif

        @if ($entry->get('phone_number'))
            <div>
                <dt class="text-sm font-semibold uppercase tracking-wide text-zinc-500">Phone</dt>
                <dd class="mt-1">
                    <a href="tel:{{ preg_replace('/[^0-9]/', '', $entry->get('phone_number')) }}" class="notranslate text-emerald-600 hover:underline">
                        {{ $entry->get('phone_number') }}
                    </a>
                </dd>
            </div>
        @endif

        @if ($entry->get('whatsapp_number') || $entry->get('whatsapp_link'))
            <div>
                <dt class="text-sm font-semibold uppercase tracking-wide text-zinc-500">WhatsApp</dt>
                <dd class="mt-1">
                    @php
                        $whatsappUrl =
                            $entry->get('whatsapp_link') ?:
                            ($entry->get('whatsapp_number')
                                ? 'https://wa.me/' . preg_replace('/\D+/', '', $entry->get('whatsapp_number'))
                                : null);
                    @endphp
                    @if ($whatsappUrl)
                        <a href="{{ $whatsappUrl }}" class="text-emerald-600 hover:underline" target="_blank"
                            rel="noopener noreferrer">
                            {{ $entry->get('whatsapp_number') ?: 'Chat on WhatsApp' }}
                        </a>
                    @endif
                </dd>
            </div>
        @endif

        @if ($entry->get('google_maps_url'))
            <div>
                <dt class="text-sm font-semibold uppercase tracking-wide text-zinc-500">Google Maps</dt>
                <dd class="mt-1">
                    <a href="{{ $entry->get('google_maps_url') }}" class="text-emerald-600 hover:underline" target="_blank"
                        rel="noopener noreferrer">
                        Open in Google Maps
                    </a>
                </dd>
            </div>
        @endif
    </dl>

    <p class="mt-12 text-center">
        <a href="/dealers" class="text-sm text-emerald-600 hover:underline">&larr; Back to dealers</a>
    </p>
</article>
</x-layouts.app>
