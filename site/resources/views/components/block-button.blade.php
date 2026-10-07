@props([
    'label',
    'link',
    'style' => 'primary',
])

@php
    $classes = match ($style) {
        'secondary' => 'bg-zinc-100 text-zinc-900 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-100 dark:hover:bg-zinc-700',
        'outline' => 'border border-zinc-300 bg-transparent text-zinc-900 hover:bg-zinc-50 dark:border-zinc-600 dark:text-zinc-100 dark:hover:bg-zinc-800',
        default => 'bg-emerald-600 text-white hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-600',
    };
@endphp

<a
    href="{{ $link }}"
    {{ $attributes->merge(['class' => "inline-flex items-center justify-center rounded-lg px-5 py-2.5 text-sm font-semibold transition-colors {$classes}"]) }}
>
    {{ $label }}
</a>
