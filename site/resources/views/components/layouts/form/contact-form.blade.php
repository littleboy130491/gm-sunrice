@php
    $set = sunrice_global('contact_label_information');

    $successHtml = nl2br((string) ($set?->success_message ?? ''));
    $failedHtml = nl2br((string) ($set?->message_failed ?? ''));
@endphp

<x-sunrice::form handle="contact" class="contact-form flex flex-col gap-4">

    {{-- Fields Form --}}
    <div class="flex flex-wrap gap-4">
        @foreach ($component->form->fields ?? [] as $field)
            @php
                $fieldType = $field['type'] ?? 'text';
                $options = collect($field['config']['options'] ?? []);
                $required = ($field['required'] ?? false)
                    || in_array('required', (array) ($field['validation'] ?? []), true);
            @endphp
            <div class="contact-form-field text-sm flex flex-col gap-1 w-full">
                @if ($fieldType === 'select')
                    <select name="data[{{ $field['handle'] }}]" @if ($required) required @endif
                        class="contact-form-input rounded-xl px-5 py-4 w-full border border-[#CECECE]">
                        <option value="" disabled {{ $component->old($field['handle']) === null ? 'selected' : '' }}>
                            {{ $field['config']['placeholder'] ?? ($field['label'] ?? '') }}
                        </option>
                        @foreach ($options as $optKey => $optLabel)
                            @php
                                $optValue = is_array($optLabel) ? ($optLabel['value'] ?? $optKey) : $optLabel;
                                $optText = is_array($optLabel) ? ($optLabel['label'] ?? $optValue) : $optLabel;
                                if (is_int($optKey)) {
                                    $optValue = $optText = is_array($optLabel) ? ($optLabel['value'] ?? $optLabel['label'] ?? '') : $optLabel;
                                }
                            @endphp
                            <option value="{{ $optValue }}" @selected((string) $component->old($field['handle']) === (string) $optValue)>
                                {{ ucwords(str_replace('_', ' ', (string) $optText)) }}
                            </option>
                        @endforeach
                    </select>
                @elseif ($fieldType === 'textarea')
                    <textarea name="data[{{ $field['handle'] }}]" rows="5"
                        placeholder="{{ $field['config']['placeholder'] ?? ($field['label'] ?? '') }}"
                        class="contact-form-input rounded-xl px-5 py-4 w-full border border-[#CECECE] h-50 lg:h-76">{{ $component->old($field['handle']) }}</textarea>
                @else
                    <input type="{{ $field['config']['input_type'] ?? 'text' }}" name="data[{{ $field['handle'] }}]"
                        value="{{ $component->old($field['handle']) }}"
                        @if ($required) required @endif
                        placeholder="{{ $field['config']['placeholder'] ?? ($field['label'] ?? '') }}"
                        class="contact-form-input rounded-xl px-5 py-4 w-full border border-[#CECECE]" />
                @endif

                @if ($err = $component->error($field['handle']))
                    <p class="text-sm text-red-600">{{ $err }}</p>
                @endif
            </div>
        @endforeach
    </div>

    {{-- Button Submit --}}
    <div>
        <button type="submit" class="button button--primary">
            {{ $set?->submit_button_label ?? 'Kirim' }}
        </button>
    </div>

    {{-- Error Summary --}}
    @if ($component->submitted() && session('errors'))
        <div class="rounded-xl bg-red-50 px-5 py-4 text-red-800 border border-red-800/30">
            @if (!empty($failedHtml))
                <div class="mb-2 font-medium">{!! $failedHtml !!}</div>
            @endif
            <ul class="flex flex-col gap-1">
                @foreach (session('errors')->all() as $error_message)
                    <li>{{ $error_message }}</li>
                @endforeach
            </ul>
        </div>
    @endif

</x-sunrice::form>
