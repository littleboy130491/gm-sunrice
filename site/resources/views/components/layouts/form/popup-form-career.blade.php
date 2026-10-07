@props(['jobLocation' => null, 'jobTitle' => null])

<dialog id="career-popup" class="career-popup"
    data-auto-open="{{ session('errors') ? 'true' : 'false' }}">
    <div class="career-popup-inner p-5 md:p-8 lg:p-10">

        {{-- Tombol close --}}
        <button type="button"
            class="absolute cursor-pointer border-0 z-10 text-(--color-primary) tracking-tighter text-4xl right-4 top-2 md:text-5xl md:right-8 md:top-5 lg:text-6xl lg:right-8 lg:top-5"
            onclick="document.getElementById('career-popup').close()" aria-label="Tutup">
            &times;
        </button>

        <x-sunrice::form handle="career_apply" class="career-form flex flex-col gap-6 md:gap-8 lg:gap-8"
            data-ajax-form>
            @php
                $set = sunrice_global('career_label_information');

                $str = fn($val, $default = '') => is_array($val) || is_null($val) ? $default : (string) $val;
                $positionValue = $component->old('position') !== null ? (string) $component->old('position') : $str($jobTitle);
                $locationValue = $component->old('location') !== null ? (string) $component->old('location') : $str($jobLocation);

                $fieldValue = fn(string $handle) => $handle === 'position' ? $positionValue : $component->old($handle);

                $successHtml = (string) ($set?->success_message ?? '');
                $failedHtml = (string) ($set?->message_failed ?? '');
            @endphp

            {{-- Heading & Description --}}
            @if (!empty($set?->form_heading) || !empty($set?->form_description))
                <div class="flow">
                    @if (!empty($set?->form_heading))
                        <h2>{{ $str($set->form_heading) }}</h2>
                    @endif
                    @if (!empty($set?->form_description))
                        <p class="w-full md:w-[90%] lg:w-[75%]">{{ $str($set->form_description) }}</p>
                    @endif
                </div>
            @endif

            <input type="hidden" name="data[location]" value="{{ $locationValue }}" />

            {{-- Fields --}}
            <div class="flex flex-wrap gap-4">
                @foreach ($component->form->fields ?? [] as $field)
                    @php $fieldType = $field['type'] ?? 'text'; @endphp
                    @continue($fieldType === 'hidden' || ($field['handle'] ?? null) === 'location')

                    <div class="career-form-field flex flex-col gap-1 w-full">

                        @if (in_array($fieldType, ['assets', 'file', 'files'], true))
                            <label class="font-medium">{{ $str($field['label'] ?? '') }}</label>
                            <label class="career-form-file flex items-center gap-3 cursor-pointer">
                                <p class="career-form-file-button shrink-0">
                                    {{ $str($set?->label_input_file, 'Choose File') }}</p>
                                <p class="career-form-file-name font-medium border border-[#A1A1A1] bg-[#F1F1F1] px-4 py-2 text-sm text-black hover:bg-gray-100"
                                    data-placeholder="{{ $str($set?->label_button_input_file, 'No File Choosen') }}">
                                    {{ $str($set?->label_button_input_file, 'No File Choosen') }}</p>
                                <input type="file" name="data[{{ $field['handle'] }}]" accept=".pdf,.doc,.docx"
                                    class="hidden"
                                    onchange="this.parentElement.querySelector('.career-form-file-name').textContent = this.files.length ? this.files[0].name : this.parentElement.querySelector('.career-form-file-name').dataset.placeholder" />
                            </label>
                            @if (!empty($field['instructions']))
                                <p class="text-sm text-(--color-text)">{{ $str($field['instructions']) }}</p>
                            @endif
                        @elseif ($fieldType === 'toggle')
                            <label class="flex items-start gap-3 cursor-pointer">
                                <input type="checkbox" name="data[{{ $field['handle'] }}]" value="1"
                                    {{ $component->old($field['handle']) ? 'checked' : '' }} class="mt-1 shrink-0" />
                                <p class="text-(--color-text)">
                                    {{ $str($field['config']['inline_label'] ?? ($field['label'] ?? '')) }}</p>
                            </label>
                        @elseif ($fieldType === 'textarea')
                            <textarea name="data[{{ $field['handle'] }}]" rows="4" placeholder="{{ $str($field['label'] ?? '') }}"
                                class="career-form-input rounded-xl bg-white px-5 py-4 w-full border border-[#CECECE]">{{ $fieldValue($field['handle']) }}</textarea>
                        @else
                            <input type="{{ $field['config']['input_type'] ?? 'text' }}" name="data[{{ $field['handle'] }}]"
                                value="{{ $fieldValue($field['handle']) }}"
                                placeholder="{{ $str($set?->{'placeholder_' . $field['handle']} ?? ($field['label'] ?? '')) }}"
                                class="career-form-input rounded-xl bg-white px-5 py-4 w-full border border-[#CECECE] font-(family-name:--font-body)" />
                        @endif

                        @if ($err = $component->error($field['handle']))
                            <p class="text-sm text-red-600">{{ $err }}</p>
                        @endif
                    </div>
                @endforeach
            </div>

            {{-- Submit --}}
            <div>
                <button type="submit" class="button button--primary">
                    {{ $str($set?->button_submit_label, 'Kirim') }}
                </button>
            </div>

            {{-- Success --}}
            <div class="career-form-success rounded-xl bg-green-50 px-5 py-4 text-(--color-primary)/50 border border-(--color-primary)/30 {{ $component->success() ? '' : 'hidden' }}"
                data-success-message="{{ $successHtml }}">
                @if ($component->success())
                    {!! $successHtml !!}
                @endif
            </div>

            {{-- Error --}}
            <div class="career-form-error rounded-xl bg-red-50 px-5 py-4 text-red-800 border border-red-800/30 {{ $component->submitted() && session('errors') ? '' : 'hidden' }}"
                data-message-failed="{{ $failedHtml }}">
                @if (!empty($failedHtml))
                    <div class="career-form-error-heading mb-2 font-medium">{!! $failedHtml !!}</div>
                @else
                    <div class="career-form-error-heading mb-2 font-medium hidden"></div>
                @endif
                <ul class="career-form-error-list flex flex-col gap-1">
                    @if ($component->submitted() && session('errors'))
                        @foreach (session('errors')->all() as $error_message)
                            <li>{{ $error_message }}</li>
                        @endforeach
                    @endif
                </ul>
            </div>

        </x-sunrice::form>

    </div>
</dialog>
