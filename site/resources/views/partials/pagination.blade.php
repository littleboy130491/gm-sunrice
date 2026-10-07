@props(['paginate', 'prevLabel' => '&larr; Previous', 'nextLabel' => 'Next &rarr;'])

@if (!empty($paginate))
    <nav class="mt-8 flex justify-center gap-4 text-sm">
        @if (!empty($paginate['prev_page']))
            <a href="{{ $paginate['prev_page'] }}" class="text-indigo-700 dark:text-indigo-400 hover:underline">{!! $prevLabel !!}</a>
        @endif
        @if (!empty($paginate['next_page']))
            <a href="{{ $paginate['next_page'] }}" class="text-indigo-700 dark:text-indigo-400 hover:underline">{!! $nextLabel !!}</a>
        @endif
    </nav>
@endif
