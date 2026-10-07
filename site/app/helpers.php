<?php

declare(strict_types=1);

use Sunrice\Models\Collection;
use Sunrice\Models\Entry;
use Sunrice\Models\Term;
use Sunrice\Query\EntryQuery;
use Sunrice\Support\Locales;

if (! function_exists('gm_entry')) {
    /**
     * Find a published entry by collection + main-language slug —
     * the sunrice equivalent of statamic's
     * Entry::query()->where('collection', $c)->where('slug', $s)->first().
     */
    function gm_entry(string $collection, string $slug): ?Entry
    {
        return Entry::query()
            ->inCollection($collection)
            ->published()
            ->whereRelation('translations', function ($q) use ($slug) {
                $q->where('locale', Locales::main())->where('slug', $slug);
            })
            ->first();
    }
}

if (! function_exists('gm_entries')) {
    /**
     * Published-entry query for a collection handle —
     * statamic's Entry::query()->where('collection', $c)->whereStatus('published').
     * Returns null when the collection does not exist.
     */
    function gm_entries(string $collection): ?EntryQuery
    {
        $model = Collection::query()->where('handle', $collection)->first();

        return $model ? EntryQuery::forCollection($model) : null;
    }
}

if (! function_exists('gm_asset')) {
    /**
     * Find an imported asset by its original statamic path — replaces
     * statamic's Asset::find('assets::' . $path). Uses the importer's
     * manifest; falls back to a filename suffix match.
     */
    function gm_asset(string $path): ?\Sunrice\Models\Asset
    {
        $path = ltrim(preg_replace('#^assets::#', '', $path), '/');

        static $manifest = null;
        if ($manifest === null) {
            $file = storage_path('app/statamic-asset-map.json');
            $manifest = is_file($file) ? (json_decode(file_get_contents($file), true) ?: []) : [];
        }

        if (isset($manifest[$path])) {
            $asset = \Sunrice\Models\Asset::find($manifest[$path]);
            if ($asset) {
                return $asset;
            }
        }

        return \Sunrice\Models\Asset::query()
            ->where('path', 'like', '%'.str_replace(['%', '_'], ['\\%', '\\_'], $path))
            ->first();
    }
}

if (! function_exists('gm_field_label')) {
    /**
     * Label of a select/button_group option for an entry's stored value —
     * statamic's `$entry->field->label()`. Falls back to the raw value.
     */
    function gm_field_label(Entry $entry, string $handle): ?string
    {
        $value = $entry->get($handle);

        if ($value === null || $value === '') {
            return null;
        }

        $field = collect($entry->activeBlueprint()?->fields ?? [])->firstWhere('handle', $handle);
        $options = collect($field['config']['options'] ?? []);

        foreach ($options as $key => $label) {
            if ((string) $key === (string) $value || (is_array($label) && ($label['value'] ?? null) === $value)) {
                return is_array($label) ? ($label['label'] ?? $label['value'] ?? $value) : (string) $label;
            }
            if (is_string($label) && $label === $value) {
                return $label;
            }
        }

        return (string) $value;
    }
}

if (! function_exists('gm_terms')) {
    /**
     * All terms of a taxonomy, ordered by title —
     * statamic's Term::query()->where('taxonomy', $handle).
     */
    function gm_terms(string $taxonomy): \Illuminate\Support\Collection
    {
        return Term::query()
            ->whereHas('taxonomy', fn ($q) => $q->where('handle', $taxonomy))
            ->get()
            ->sortBy(fn (Term $t) => $t->name ?? '')
            ->values();
    }
}

if (! function_exists('gm_term')) {
    /**
     * Find a term by taxonomy + main-language slug.
     */
    function gm_term(string $taxonomy, string $slug): ?Term
    {
        return Term::query()
            ->whereHas('taxonomy', fn ($q) => $q->where('handle', $taxonomy))
            ->whereRelation('translations', function ($q) use ($slug) {
                $q->where('locale', Locales::main())->where('slug', $slug);
            })
            ->first();
    }
}

/**
 * Best-effort URL for an asset-ish value: Asset model, collection of
 * assets, URL/path string, or null.
 */
if (! function_exists('gm_asset_url')) {
    function gm_asset_url(mixed $value): ?string
    {
        if ($value instanceof \Illuminate\Support\Collection) {
            $value = $value->first();
        }

        if ($value instanceof \Sunrice\Models\Asset) {
            return $value->url();
        }

        if (is_string($value) && $value !== '') {
            if (str_starts_with($value, 'http') || str_starts_with($value, '/')) {
                return $value;
            }

            return asset('storage/' . ltrim($value, '/'));
        }

        return null;
    }
}

/**
 * URL of a hydrated link field: ['url','label','new_tab'] or plain string.
 */
if (! function_exists('gm_link_url')) {
    function gm_link_url(mixed $value): ?string
    {
        if (is_array($value)) {
            return $value['url'] ?? null;
        }

        return is_string($value) && $value !== '' ? $value : null;
    }
}
