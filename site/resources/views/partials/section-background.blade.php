@props(['background' => 'default'])

@php
    $classes = match ($background) {
        'muted' => 'bg-zinc-50 dark:bg-zinc-900',
        'dark' => 'bg-zinc-900 text-zinc-100 dark:bg-zinc-950',
        default => 'bg-white dark:bg-zinc-950',
    };
@endphp

{{ $attributes->merge(['class' => $classes]) }}
