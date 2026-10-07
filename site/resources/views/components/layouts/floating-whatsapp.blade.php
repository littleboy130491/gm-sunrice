@php
    $settings = sunrice_global('settings');

    $show = $settings?->get('show_button') ?? true;
    $position = $settings?->get('position') === 'right' ? 'right' : 'left';
    $label = $settings?->get('label_floating') ?: 'Chat with us';
    $heading = $settings?->get('heading');
    $shortDescription = $settings?->get('short_description');

    $iconUrl = $settings?->icon?->url();

    $contacts = collect($settings?->get('contact_whatsapp') ?? [])
        ->map(function ($contact) {
            $number = $contact['nomor_whatsapp'] ?? null;

            if (blank($number)) {
                return null;
            }

            $digits = preg_replace('/\D/', '', is_float($number) ? sprintf('%.0f', $number) : (string) $number);

            if (blank($digits)) {
                return null;
            }

            // Nomor lokal > kode 62
            $digits = str_starts_with($digits, '62') ? $digits : '62' . ltrim($digits, '0');

            $message = $contact['message'] ?? null;
            $avatar = $contact['avatar'] ?? null;

            return [
                'title' => $contact['title'] ?? null,
                'label' => $contact['label'] ?? null,
                'avatar' => $avatar?->url(),
                'url' => 'https://wa.me/' . $digits . ($message ? '?text=' . rawurlencode($message) : ''),
            ];
        })
        ->filter()
        ->values();
@endphp

@if ($show)
    <div id="floating-whatsapp" data-position="{{ $position }}">
        <div class="floating-whatsapp-panel" role="dialog" aria-label="{{ $heading ?: $label }}" aria-hidden="true">
            <div class="floating-whatsapp-panel-head">
                @if ($heading)
                    <p class="floating-whatsapp-panel-title">{{ $heading }}</p>
                @endif
                @if ($shortDescription)
                    <p class="floating-whatsapp-panel-desc">{{ $shortDescription }}</p>
                @endif
            </div>

            <div class="floating-whatsapp-panel-body">
                @if ($contacts->isNotEmpty())
                    <ul class="floating-whatsapp-list">
                        @foreach ($contacts as $contact)
                            <li>
                                <a href="{{ gm_link_url($contact['url']) }}" target="_blank" rel="noopener noreferrer">
                                    <span class="floating-whatsapp-avatar">
                                        @if ($contact['avatar'])
                                            <img src="{{ gm_asset_url($contact['avatar']) }}" alt="{{ $contact['title'] }}">
                                        @else
                                            <svg viewBox="0 0 84 84" width="26" height="26" fill="currentColor"
                                                aria-hidden="true">
                                                <path
                                                    d="M42 0a42 42 0 0 0-36 63.7L0 84l21-5.5A42 42 0 1 0 42 0Zm18.4 49.9c-1-.5-6.1-3-7-3.4-1-.3-1.6-.5-2.3.5-.7 1-2.7 3.4-3.3 4-.6.8-1.2.9-2.2.3-1-.5-4.4-1.6-8.3-5.1-3.1-2.8-5.1-6.1-5.8-7.2-.6-1-.1-1.6.5-2.1.5-.5 1-1.2 1.5-1.8s.7-1 1-1.7c.4-.7.2-1.3 0-1.8-.3-.5-2.4-5.6-3.2-7.7-.9-2-1.7-1.8-2.4-1.8h-2c-.7 0-1.8.3-2.7 1.3-1 1-3.6 3.6-3.6 8.6 0 5.1 3.7 10 4.2 10.7.5.7 7.3 11.1 17.6 15.6 2.5 1 4.4 1.7 5.9 2.2 2.5.8 4.7.6 6.5.4 2-.3 6.1-2.5 7-4.9.8-2.4.8-4.5.5-4.9-.2-.5-.9-.7-1.9-1.2Z" />
                                            </svg>
                                        @endif
                                    </span>
                                    <span class="floating-whatsapp-meta">
                                        <span class="floating-whatsapp-name">{{ $contact['title'] }}</span>
                                        @if ($contact['label'])
                                            <span class="floating-whatsapp-role">{{ $contact['label'] }}</span>
                                        @endif
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

        <button type="button" class="floating-whatsapp-toggle" aria-expanded="false" aria-label="{{ $label }}">
            <span class="floating-whatsapp-bubble">{{ $label }}</span>
            <span class="floating-whatsapp-circle">
                <span class="floating-whatsapp-icon">
                    @if ($iconUrl)
                        <img src="{{ $iconUrl }}" alt="WhatsApp" width="30" height="30">
                    @else
                        <svg viewBox="0 0 84 84" width="30" height="30" fill="currentColor" aria-hidden="true">
                            <path
                                d="M70.8 12.1A41.2 41.2 0 0 0 41.6 0C18.9 0 .3 18.5.3 41.3c0 7.3 1.9 14.4 5.5 20.6L0 83.3l21.9-5.7a41.2 41.2 0 0 0 60.9-36.2c0-11-4.3-21.4-12-29.3ZM41.6 75.6c-6.2 0-12.2-1.6-17.5-4.8l-1.2-.7-13 3.4 3.4-12.7-.8-1.3a34 34 0 0 1-5.2-18.2c0-18.9 15.4-34.3 34.3-34.3 9.2 0 17.8 3.6 24.3 10.1a34 34 0 0 1 10 24.3c.1 18.9-15.3 34.2-34.3 34.2Zm18.8-25.7c-1-.5-6.1-3-7-3.4-1-.3-1.6-.5-2.3.5-.7 1-2.7 3.4-3.3 4-.6.8-1.2.9-2.2.3-1-.5-4.4-1.6-8.3-5.1-3.1-2.8-5.1-6.1-5.8-7.2-.6-1-.1-1.6.5-2.1.5-.5 1-1.2 1.5-1.8s.7-1 1-1.7c.4-.7.2-1.3 0-1.8-.3-.5-2.4-5.6-3.2-7.7-.9-2-1.7-1.8-2.4-1.8h-2c-.7 0-1.8.3-2.7 1.3-1 1-3.6 3.6-3.6 8.6 0 5.1 3.7 10 4.2 10.7.5.7 7.3 11.1 17.6 15.6 2.5 1 4.4 1.7 5.9 2.2 2.5.8 4.7.6 6.5.4 2-.3 6.1-2.5 7-4.9.8-2.4.8-4.5.5-4.9-.2-.5-.9-.7-1.9-1.2Z" />
                        </svg>
                    @endif
                </span>
                <span class="floating-whatsapp-close" aria-hidden="true">
                    <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor"
                        stroke-width="2.4" stroke-linecap="round">
                        <path d="M6 6l12 12M18 6L6 18" />
                    </svg>
                </span>
            </span>
        </button>
    </div>

@endif
