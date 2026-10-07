@php
    $skipHandles = array_values(array_filter([
        isset($position) ? 'position' : null,
        isset($location) ? 'location' : null,
    ]));
@endphp

<section class="rounded-xl border border-zinc-200 bg-zinc-50 p-6 dark:border-zinc-800 dark:bg-zinc-900/50">
    <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Apply for this role</h2>
    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Submit your details and CV. We will review your application and get back to you.</p>

    <x-sunrice::form handle="career_apply" class="statamic-form mt-6 space-y-5">
        @if ($component->submitted() && session('errors'))
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-900 dark:bg-red-950/40 dark:text-red-200" role="alert">
                <p class="font-medium">Please fix the following:</p>
                <ul class="mt-2 list-inside list-disc space-y-1">
                    @foreach (session('errors')->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @isset($position)
            <input type="hidden" name="data[position]" value="{{ $position }}">
        @endisset

        @isset($location)
            <input type="hidden" name="data[location]" value="{{ $location }}">
        @endisset

        @include('partials.forms.fields', [
            'fields' => $component->form->fields ?? [],
            'component' => $component,
            'skipHandles' => $skipHandles,
        ])
    </x-sunrice::form>
</section>
