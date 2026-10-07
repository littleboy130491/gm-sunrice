@php
    $globals = sunrice_global('settings');

    $site_title = $globals?->site_title;
    $logo_url = $globals?->site_logo?->url();

    $phones = collect($globals?->phone_numbers ?? [])
        ->filter(fn($item) => ($item['enabled'] ?? false) && !empty($item['number']))
        ->map(fn($item) => ['label' => $item['label'] ?? null, 'number' => $item['number']])
        ->values()
        ->all();

    $emails = collect($globals?->emails ?? [])
        ->filter(fn($item) => ($item['enabled'] ?? false) && !empty($item['email']))
        ->map(fn($item) => ['label' => $item['label'] ?? null, 'email' => $item['email']])
        ->values()
        ->all();

    $translate_label = $globals?->translate_label;
    $has_translate = !empty($globals?->embed_translate);

    $contactLabel = sunrice_global('contact_label_information');
    $phone_label = $contactLabel?->phone_number_heading ?? 'Telepon';
    $email_label = $contactLabel?->email_heading ?? 'Email';
@endphp

<nav>
    {{-- Mobile Flyout Menu --}}
    <div id="mobile-menu"
        class="pointer-events-none invisible opacity-0 lg:hidden fixed inset-0 z-50 transition-opacity duration-300 ease-out">
        <button type="button" id="mobile-menu-backdrop" aria-label="Close menu"
            class="absolute inset-0 bg-black/45 opacity-0 transition-opacity duration-300 ease-out"></button>

        <div id="mobile-menu-panel"
            class="-translate-x-full flex h-full w-full max-w-[90%] md:max-w-[40%] flex-col overflow-y-auto overscroll-contain bg-white px-4 py-6 transition-transform duration-300 ease-out">

            {{-- Flyout Header --}}
            <div id="logo-flyout" class="flex items-start {{ $logo_url ? 'justify-between' : 'justify-end' }}">
                @if ($logo_url)
                    <a href="/" class="inline-flex items-center">
                        <img src="{{ $logo_url }}" alt="{{ $site_title }} Logo" class="h-auto w-28" />
                    </a>
                @endif
                <button type="button" id="mobile-menu-close" aria-label="Close menu"
                    class="flex h-4 w-4 items-center justify-center text-2xl text-black">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            {{-- Flyout Menu --}}
            @if (sunrice_menu('nav_header')->isNotEmpty())
                <div id="flyout-menu" class="border-t border-(--color-line) my-5 pt-5">
                    <ul class="flex flex-col gap-4 font-(family-name:--font-body)">
                        @foreach (sunrice_menu("nav_header") as $item)
@php
    $title = $item->label;
    $url = $item->url;
    $children = $item->children;
@endphp
                            <li>
                                @if (count($children) > 0)
                                    <details class="group">
                                        <summary
                                            class="flex cursor-pointer list-none items-center justify-between gap-4 text-black transition-colors hover:text-(--color-primary)">
                                            <span class="block flex-1">{{ $title }}</span>
                                            <span
                                                class="flex h-3 w-3 items-center justify-center text-black transition-transform duration-200 group-open:rotate-180">
                                                <svg viewBox="0 0 12 8" fill="none" aria-hidden="true"
                                                    class="h-2.5 w-3">
                                                    <path d="M1 1.25L6 6.25L11 1.25" stroke="currentColor"
                                                        stroke-width="2" stroke-linecap="round"
                                                        stroke-linejoin="round" />
                                                </svg>
                                            </span>
                                        </summary>

                                        <ul class="mt-3 mb-1 flex flex-col gap-2">
                                            @foreach ($children as $child)
                                                <li>
                                                    <a href="{{ $child->url }}"
                                                        class="block text-(--color-text) hover:text-(--color-primary) active:text-(--color-primary)">
                                                        {{ $child->label }}
                                                    </a>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </details>
                                @else
                                    <a href="{{ $url ?? '#' }}"
                                        class="block text-black hover:text-(--color-primary) active:text-(--color-primary)">
                                        {{ $title }}
                                    </a>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="border-t border-(--color-line) py-5 font-(family-name:--font-body) text-black">

                {{-- Language Mobile --}}
                @if ($has_translate)
                    <div class="mb-5 flex items-center justify-between gap-4">
                        @if ($translate_label)
                            <p class="uppercase text-(--color-heading)">{{ $translate_label }}</p>
                        @endif
                        <div class="gtranslate_wrapper"></div>
                    </div>
                @endif

                <!-- Contact Info -->
                @if (count($phones) > 0 || count($emails) > 0)
                    <div id="contact-flyout"
                        class="{{ $has_translate ? 'border-t border-(--color-line) pt-5' : '' }} flex flex-col gap-5">
                        @if (count($phones) > 0)
                            <div>
                                <p class="uppercase text-(--color-primary) mb-2">{{ $phone_label }}</p>
                                <div class="flex flex-col gap-2">
                                    @foreach ($phones as $phone)
                                        <div class="flex flex-col gap-1">
                                            @if (!empty($phone['label']))
                                                <p class="block text-(--color-text)">{{ $phone['label'] }}
                                                </p>
                                            @endif
                                            <a href="tel:{{ preg_replace('/[^0-9]/', '', $phone['number']) }}"
                                                class="text-black hover:text-(--color-primary) text-sm">
                                                {{ $phone['number'] }}
                                            </a>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                        @if (count($emails) > 0)
                            <div>
                                <p class="uppercase text-(--color-primary) mb-2">{{ $email_label }}</p>
                                <div class="flex flex-col gap-2">
                                    @foreach ($emails as $email)
                                        <div class="flex flex-col gap-1">
                                            @if (!empty($email['label']))
                                                <p class="block text-(--color-text)">{{ $email['label'] }}
                                                </p>
                                            @endif
                                            <a href="mailto:{{ $email['email'] }}"
                                                class="text-black hover:text-(--color-primary) text-sm">
                                                {{ $email['email'] }}
                                            </a>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</nav>
