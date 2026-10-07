@if (app()->environment('local'))
    <div class="mx-auto max-w-6xl px-4 py-8">
        <p class="rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            Missing block template for type: <code>{{ $block->type ?? 'unknown' }}</code>
        </p>
    </div>
@endif
