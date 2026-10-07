@props([
    'asset',
    'alt' => null,
    'figure' => true,
])

@php
    $altText = $alt ?? $asset->alt ?? $asset->title ?? '';
    $caption = $asset->caption ?? null;
@endphp

@if ($caption && $figure)
    <figure>
        <img src="{{ $asset->url() }}" alt="{{ $altText }}" {{ $attributes }}>
        <figcaption class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">
            {{ $caption }}
        </figcaption>
    </figure>
@elseif ($caption)
    <div>
        <img src="{{ $asset->url() }}" alt="{{ $altText }}" {{ $attributes }}>
        <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">{{ $caption }}</p>
    </div>
@else
    <img src="{{ $asset->url() }}" alt="{{ $altText }}" {{ $attributes }}>
@endif
