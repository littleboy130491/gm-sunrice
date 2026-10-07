@php
    $globals = sunrice_global('settings');

    $company_name = $globals?->site_title ?? 'PT Gaya Makmur Mobil';

    $socials = collect($globals?->social_media ?? [])
        ->map(function ($item, $key) {
            $url = $item['url'] ?? null;
            if (!$url) {
                return null;
            }

            $icon = $item['image'] ?? null;

            return [
                'name' => ucfirst((string) $key),
                'link' => $url,
                'icon' => $icon?->url(),
            ];
        })
        ->filter()
        ->values()
        ->all();
@endphp

<footer id="secondary-footer" class="bg-white relative z-10 rounded-t-2xl md:rounded-t-3xl lg:rounded-t-4xl">
    <div class="container">
        <div id="footer-menu-wrapper" class="overflow-x-auto border-b border-(--color-line)">
            <ul
                class="flex min-w-max items-center gap-6 whitespace-nowrap py-8 md:justify-between lg:min-w-0 lg:justify-between lg:gap-4">
                @foreach (sunrice_menu('secondary_footer') as $item)
                    <li class="shrink-0">
                        <a href="{{ $item->url ?: '#' }}"
                            class="text-(--color-text) hover:text-(--color-primary) font-(family-name:--font-body) font-normal">
                            {{ $item->label }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>

        <div id="footer-copyright"
            class="flex flex-col-reverse md:flex-row lg:flex-row items-center justify-between gap-8 pt-8 pb-6">
            <p class="text-(--color-text)">© {{ date('Y') }} {{ $company_name }}</p>
            @if (count($socials) > 0)
                <div id="social-icons-wrapper" class="flex items-center gap-5">
                    @foreach ($socials as $social)
                        <a href="{{ gm_link_url($social['link']) }}" target="_blank" rel="noopener noreferrer"
                            title="{{ $social['name'] }}">
                            <svg class="social-icon block w-5 h-5"
                                style="--icon-url: url('{{ $social['icon'] }}');"></svg>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</footer>
