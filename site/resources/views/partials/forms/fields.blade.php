@foreach ($fields as $field)
    @if (! empty($skipHandles) && in_array($field['handle'], $skipHandles, true))
        @continue
    @endif

    @php
        $fieldId = 'field-' . $field['handle'];
        $required = ($field['required'] ?? false)
            || in_array('required', (array) ($field['validation'] ?? []), true);
        $error = $component->error($field['handle']);
        $type = $field['type'] ?? 'text';
    @endphp

    <div class="space-y-1.5">
        <label for="{{ $fieldId }}" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
            {{ $field['label'] ?? $field['handle'] }}
            @if ($required)
                <span class="text-emerald-600" aria-hidden="true">*</span>
            @endif
        </label>

        <div>
            @if ($type === 'textarea')
                <textarea id="{{ $fieldId }}" name="data[{{ $field['handle'] }}]" rows="5" @if ($required) required @endif
                    class="w-full rounded-lg border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900">{{ $component->old($field['handle']) }}</textarea>
            @elseif ($type === 'select')
                <select id="{{ $fieldId }}" name="data[{{ $field['handle'] }}]" @if ($required) required @endif
                    class="w-full rounded-lg border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900">
                    <option value="">{{ $field['config']['placeholder'] ?? '—' }}</option>
                    @foreach ($field['config']['options'] ?? [] as $optKey => $optLabel)
                        @php
                            $optValue = is_array($optLabel) ? ($optLabel['value'] ?? $optLabel['label'] ?? $optKey) : (is_int($optKey) ? $optLabel : $optKey);
                            $optText = is_array($optLabel) ? ($optLabel['label'] ?? $optValue) : $optLabel;
                        @endphp
                        <option value="{{ $optValue }}" @selected((string) $component->old($field['handle']) === (string) $optValue)>{{ $optText }}</option>
                    @endforeach
                </select>
            @elseif (in_array($type, ['file', 'files', 'assets'], true))
                <input id="{{ $fieldId }}" type="file" name="data[{{ $field['handle'] }}]" @if ($required) required @endif
                    class="w-full rounded-lg border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900" />
            @elseif ($type === 'toggle')
                <input id="{{ $fieldId }}" type="checkbox" name="data[{{ $field['handle'] }}]" value="1"
                    {{ $component->old($field['handle']) ? 'checked' : '' }} />
            @else
                <input id="{{ $fieldId }}" type="{{ $field['config']['input_type'] ?? 'text' }}" name="data[{{ $field['handle'] }}]"
                    value="{{ $component->old($field['handle']) }}" @if ($required) required @endif
                    class="w-full rounded-lg border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900" />
            @endif
        </div>

        @if ($field['instructions'] ?? false)
            <p id="{{ $fieldId }}-instructions" class="text-xs text-zinc-500 dark:text-zinc-400">
                {{ $field['instructions'] }}
            </p>
        @endif

        @if ($error)
            <p id="{{ $fieldId }}-error" class="text-sm text-red-600 dark:text-red-400" role="alert">
                {{ $error }}
            </p>
        @endif
    </div>
@endforeach
