@if (filled($entry->get('sections')))
    @include('partials.page-sections')
@elseif (filled($entry->get('content')))
    <article class="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:py-16">
        <div class="prose prose-zinc dark:prose-invert max-w-none">
            {!! $entry->get('content') !!}
        </div>
    </article>
@endif
