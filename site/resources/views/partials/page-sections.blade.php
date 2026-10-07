@foreach ($entry->get('sections') ?? [] as $section)
    @includeFirst([
        'sunrice.blocks.' . $section->type,
        'sunrice.blocks.default',
    ], ['block' => $section])
@endforeach
