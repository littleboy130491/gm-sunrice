<section class="rounded-xl border border-zinc-200 bg-zinc-50 p-6 dark:border-zinc-800 dark:bg-zinc-900/50">
    <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Contact us</h2>
    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Send a message and we will respond as soon as we can.</p>

    <x-sunrice::form handle="contact" class="statamic-form mt-6 space-y-5">
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

        @include('partials.forms.fields', ['fields' => $component->form->fields ?? [], 'component' => $component])
    </x-sunrice::form>
</section>
