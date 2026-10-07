<?php

declare(strict_types=1);

namespace App\StatamicImport;

use League\CommonMark\GithubFlavoredMarkdownConverter;
use Sunrice\Models\Fieldset;

/**
 * Converts raw Statamic frontmatter values to the shapes Sunrice stores
 * (asset/entry/term ids, link arrays, HTML rich text, repeater/flexible
 * row envelopes). Callers then run BlueprintSchema::normalize() on the
 * result to finish normalization.
 */
final class ValueConverter
{
    /**
     * @param  callable(string): ?int  $assetIdFor
     * @param  callable(string): ?int  $entryIdFor
     * @param  callable(string, string): ?int  $termIdFor
     * @param  callable(string): string  $assetUrlFor
     * @param  callable(string, string): void  $warn
     */
    public function __construct(
        private readonly mixed $assetIdFor,
        private readonly mixed $entryIdFor,
        private readonly mixed $termIdFor,
        private readonly mixed $assetUrlFor,
        private readonly mixed $warn,
        private readonly BardToHtml $bard,
    ) {}

    /**
     * Convert one assoc array of Statamic values using Sunrice field defs.
     *
     * @param  array<string, mixed>  $values
     * @param  array<int, array<string, mixed>>  $fields
     * @return array<string, mixed>
     */
    public function convert(array $values, array $fields): array
    {
        $byHandle = collect($fields)->keyBy('handle');

        $out = [];
        foreach ($values as $handle => $value) {
            $field = $byHandle->get($handle);
            if ($field === null) {
                $out[$handle] = $value; // unknown handles pass through untouched

                continue;
            }
            $out[$handle] = $this->convertValue($value, $field);
        }

        return $out;
    }

    private function convertValue(mixed $value, array $field): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($field['type'] ?? 'text') {
            'rich_text' => $this->richText($value, $field),
            'asset' => $this->assets($value, $field),
            'entries' => $this->entries($value),
            'terms' => $this->terms($value, $field),
            'link' => $this->link($value, $field),
            'select' => $this->select($value, $field),
            'toggle' => (bool) $value,
            'number' => is_numeric($value) ? $value + 0 : $value,
            'group' => is_array($value)
                ? $this->convert($value, $this->children($field))
                : $value,
            'repeater' => $this->repeater($value, $field),
            'flexible' => $this->flexible($value, $field),
            'fieldset' => $value, // expanded at schema level; values are flat
            default => $value,
        };
    }

    private function richText(mixed $value, array $field): mixed
    {
        $source = $field['config']['_source_format'] ?? null;
        if ($source === 'bard') {
            return $this->bard->toHtml($value);
        }
        if ($source === 'markdown' && is_string($value)) {
            return (string) (new GithubFlavoredMarkdownConverter)->convert($value);
        }

        return is_string($value) ? $value : $this->bard->toHtml($value);
    }

    private function assets(mixed $value, array $field): mixed
    {
        $toId = function (mixed $v): ?int {
            if (! is_string($v) || $v === '') {
                return null;
            }
            $id = ($this->assetIdFor)($v);
            if ($id === null) {
                ($this->warn)("asset not found: {$v}", $field['handle'] ?? '');
            }

            return $id;
        };

        if (($field['config']['multiple'] ?? false) || is_array($value)) {
            return collect(is_array($value) ? $value : [$value])
                ->map($toId)->filter()->values()->all();
        }

        return $toId($value);
    }

    private function entries(mixed $value): mixed
    {
        $toId = function (mixed $v): ?int {
            $key = is_string($v) ? preg_replace('/^entry::/', '', $v) : (is_numeric($v) ? (string) $v : null);
            if ($key === null || $key === '') {
                return null;
            }
            $id = ($this->entryIdFor)($key);
            if ($id === null) {
                ($this->warn)("entry ref not found: {$key}", '');
            }

            return $id;
        };

        return collect(is_array($value) ? $value : [$value])
            ->map($toId)->filter()->values()->all();
    }

    private function terms(mixed $value, array $field): mixed
    {
        $taxonomy = (string) ($field['config']['taxonomy'] ?? '');
        $toId = function (mixed $v) use ($taxonomy, $field): ?int {
            if (! is_string($v) || $v === '') {
                return null;
            }
            // Values can be 'taxonomy::slug' or a bare slug.
            [$tax, $slug] = str_contains($v, '::')
                ? explode('::', $v, 2)
                : [$taxonomy, $v];
            $id = ($this->termIdFor)($tax, $slug);
            if ($id === null) {
                ($this->warn)("term not found: {$v}", $field['handle'] ?? '');
            }

            return $id;
        };

        return collect(is_array($value) ? $value : [$value])
            ->map($toId)->filter()->values()->all();
    }

    private function link(mixed $value, array $field): mixed
    {
        // Statamic link fields store a scalar ('entry::id', URL, 'mailto:', '#anchor')
        // or an array {link, text, target_blank}.
        $raw = is_array($value) ? ($value['link'] ?? $value['url'] ?? '') : $value;
        $label = is_array($value) ? ($value['text'] ?? $value['label'] ?? null) : null;
        $newTab = is_array($value) ? (bool) ($value['target_blank'] ?? $value['new_tab'] ?? false) : false;

        if (! is_string($raw) || $raw === '') {
            return null;
        }
        if (str_starts_with($raw, 'entry::')) {
            $statId = substr($raw, 7);
            $id = ($this->entryIdFor)($statId);
            if ($id === null) {
                ($this->warn)("link target not found: {$raw}", $field['handle'] ?? '');

                return null;
            }

            return ['type' => 'entry', 'entry_id' => $id, 'label' => $label, 'new_tab' => $newTab];
        }
        if (str_starts_with($raw, 'asset::')) {
            // e.g. asset::assets::brochure.pdf → point at the stored file URL.
            $path = preg_replace('/^asset::[^:]+::/', '', $raw);
            $id = ($this->assetIdFor)((string) $path);
            if ($id === null) {
                ($this->warn)("link asset not found: {$raw}", $field['handle'] ?? '');

                return null;
            }

            return ['type' => 'url', 'url' => ($this->assetUrlFor)((string) $id), 'label' => $label, 'new_tab' => $newTab];
        }

        return ['type' => 'url', 'url' => $raw, 'label' => $label, 'new_tab' => $newTab];
    }

    private function select(mixed $value, array $field): mixed
    {
        if ($field['config']['multiple'] ?? false) {
            return is_array($value) ? array_values($value) : [$value];
        }

        return is_array($value) ? (array_values($value)[0] ?? null) : $value;
    }

    private function repeater(mixed $value, array $field): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        $children = $this->children($field);
        $out = [];
        foreach ($value as $row) {
            if (! is_array($row)) {
                continue;
            }
            $converted = $this->convert($this->rowValues($row), $children);
            $converted['_id'] = is_string($row['id'] ?? null) ? $row['id'] : null;
            $converted['_key'] = isset($row['identifier']) && is_string($row['identifier']) ? $row['identifier'] : null;
            $converted['_hidden'] = isset($row['enabled']) && $row['enabled'] === false;
            $out[] = $converted;
        }

        return $out;
    }

    private function flexible(mixed $value, array $field): mixed
    {
        if (! is_array($value)) {
            return $value;
        }
        $setMap = (array) ($field['config']['set_handles'] ?? []);

        $out = [];
        foreach ($value as $row) {
            if (! is_array($row)) {
                continue;
            }
            $setKey = (string) ($row['type'] ?? '');
            $handle = $setMap[$setKey] ?? $setKey;
            $fieldsetFields = $this->fieldsetFields($handle);
            if ($fieldsetFields === []) {
                ($this->warn)("unknown replicator set: {$setKey}", $field['handle'] ?? '');
            }
            $out[] = [
                'id' => is_string($row['id'] ?? null) ? $row['id'] : null,
                'type' => $handle,
                'key' => isset($row['identifier']) && is_string($row['identifier']) ? $row['identifier'] : null,
                'hidden' => isset($row['enabled']) && $row['enabled'] === false,
                'values' => $this->convert($this->rowValues($row), $fieldsetFields),
            ];
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $field
     * @return array<int, array<string, mixed>>
     */
    private function children(array $field): array
    {
        $defs = (array) ($field['config']['fields'] ?? []);

        return $this->expandFieldsets($defs);
    }

    /**
     * Fields stored inside a fieldset by handle (DB lookup; fieldsets are
     * imported before entries).
     *
     * @return array<int, array<string, mixed>>
     */
    private function fieldsetFields(string $handle): array
    {
        static $cache = [];
        if (! array_key_exists($handle, $cache)) {
            $fs = Fieldset::query()->where('handle', $handle)->first();
            $cache[$handle] = $fs ? $this->expandFieldsets((array) $fs->fields) : [];
        }

        return $cache[$handle];
    }

    /**
     * Expand 'fieldset' include nodes in a field list, same rule as
     * BlueprintSchema but against the fieldsets we created.
     *
     * @param  array<int, array<string, mixed>>  $defs
     * @return array<int, array<string, mixed>>
     */
    private function expandFieldsets(array $defs, int $depth = 0): array
    {
        if ($depth > 5) {
            return $defs;
        }
        $out = [];
        foreach ($defs as $def) {
            if (($def['type'] ?? null) === 'fieldset' && ($h = $def['config']['fieldset'] ?? null)) {
                foreach ($this->fieldsetFields((string) $h) as $child) {
                    $out[] = $child;
                }
                continue;
            }
            $out[] = $def;
        }

        return $out;
    }

    /**
     * Strip Statamic row control keys so only field values remain.
     *
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function rowValues(array $row): array
    {
        foreach (['id', 'type', 'enabled', 'identifier'] as $k) {
            unset($row[$k]);
        }

        return $row;
    }
}
