@php
    $globals = sunrice_global('settings');

    $site_title = $globals?->site_title;
    $logo_url = $globals?->site_logo?->url();

    $sticky_desktop = (bool) ($globals?->header_desktop ?? false);
    $sticky_mobile = (bool) ($globals?->header_mobile ?? false);
@endphp

<header id="header" class="header-fixed" data-sticky-desktop="{{ $sticky_desktop ? 'true' : 'false' }}"
    data-sticky-mobile="{{ $sticky_mobile ? 'true' : 'false' }}">
    <div class="container">
        <div
            class="flex items-center {{ $logo_url ? 'justify-between' : 'justify-end' }} border-b border-(--color-line)/20 py-5 lg:gap-8">
            @if ($logo_url)
                <a href="/" class="inline-flex items-center">
                    <img src="{{ $logo_url }}" alt="{{ $site_title }} Logo" class="h-auto w-24 md:w-28 lg:w-32" />
                </a>
            @endif

            {{-- Desktop Navigation --}}
            @if (sunrice_menu('nav_header')->isNotEmpty())
                <nav id="desktop-menu" class="hidden lg:flex flex-1 justify-center">
                    <ul class="flex items-center justify-center gap-10 font-(family-name:--font-body)">
                        @foreach (sunrice_menu("nav_header") as $item)
@php
    $title = $item->label;
    $url = $item->url;
    $children = $item->children;
@endphp
                            <li class="group relative shrink-0">
                                @if (count($children) > 0)
                                    <div
                                        class="flex cursor-pointer items-center gap-2 font-medium text-white hover:text-(--color-secondary)">
                                        <span>{{ $title }}</span>
                                        <span
                                            class="flex h-2.5 w-2.5 items-center justify-center transition-transform duration-200 group-hover:rotate-180">
                                            <svg viewBox="0 0 12 8" fill="none" aria-hidden="true" class="h-2.5 w-3">
                                                <path d="M1 1.25L6 6.25L11 1.25" stroke="currentColor" stroke-width="2"
                                                    stroke-linecap="round" stroke-linejoin="round" />
                                            </svg>
                                        </span>
                                    </div>

                                    <ul
                                        class="invisible absolute left-1/2 z-30 mt-8 min-w-56 -translate-x-1/2 rounded-2xl bg-black/50 backdrop-blur p-3 opacity-0 transition-all duration-200 group-hover:visible group-hover:opacity-100">
                                        @foreach ($children as $child)
                                            <li>
                                                <a href="{{ $child->url }}"
                                                    class="block px-1 py-1.5 text-white transition-colors hover:text-(--color-secondary) text-sm">
                                                    {{ $child->label }}
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <a href="{{ $url ?? '#' }}"
                                        class="flex items-center gap-2 font-medium text-white hover:text-(--color-secondary) active:text-(--color-secondary)">
                                        <span>{{ $title }}</span>
                                    </a>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </nav>
            @endif

            {{-- Language Desktop --}}
            <div class="hidden lg:flex items-center shrink-0">
                <div class="gtranslate_wrapper"></div>
            </div>

            {{-- Hamburger Button --}}
            <button id="menu-toggle" type="button" aria-controls="mobile-menu" aria-expanded="false"
                class="lg:hidden flex flex-col justify-center items-center w-8 h-8 space-y-1">
                <span class="block w-6 h-0.5 bg-white"></span>
                <span class="block w-6 h-0.5 bg-white"></span>
                <span class="block w-6 h-0.5 bg-white"></span>
            </button>
        </div>

        @include('components.layouts.header.flyout')
    </div>
</header>
