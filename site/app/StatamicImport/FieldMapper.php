<?php

declare(strict_types=1);

namespace App\StatamicImport;

use Illuminate\Support\Str;

/**
 * Maps Statamic blueprint/fieldset field definitions to Sunrice field
 * definitions. Replicator sets are registered as Sunrice fieldsets; the
 * mapping of Statamic set key → Sunrice fieldset handle is kept in
 * config.set_handles so the value converter can resolve row types.
 */
final class FieldMapper
{
    /**
     * Sunrice fieldsets that must be created: handle => ['title' => .., 'fields' => [defs]].
     *
     * @var array<string, array{title: string, fields: array<int, array<string, mixed>>}>
     */
    public array $fieldsets = [];

    /** @var array<string, string> fieldset handle => signature, for collision detection */
    private array $signatures = [];

    /** @var array<string, int> skipped field types => count (for reporting) */
    public array $skipped = [];

    /**
     * Statamic field types that have no Sunrice equivalent and are dropped
     * from blueprints (their stored data is preserved but hidden).
     */
    private const SKIP_TYPES = [
        'slug', 'seo_pro', 'seo_pro_previews', 'template', 'hidden', 'section',
        'revealer', 'html', 'users', 'user_roles', 'user_groups', 'entry_status',
        'spacer', 'color', 'icon', 'width', 'form', 'collections', 'sites',
    ];

    /**
     * Convert a whole Statamic field list (blueprint tab sections, grid
     * children or fieldset fields) to Sunrice field defs.
     *
     * @param  array<int, mixed>  $fields  items: {handle, field:{...}} or {import: 'ns.name'}
     * @param  array<string, array<string, mixed>>  $fieldsetFiles  statamic fieldset files, keyed 'ns.name' => parsed yaml
     * @return array<int, array<string, mixed>>
     */
    public function mapFieldList(array $fields, array $fieldsetFiles, ?string $context = null): array
    {
        $out = [];
        foreach ($fields as $item) {
            if (! is_array($item)) {
                continue;
            }
            if (isset($item['import']) && is_string($item['import'])) {
                $handle = $this->fieldsetHandleForImport($item['import'], $fieldsetFiles);
                $out[] = [
                    'handle' => 'fieldset_'.$handle,
                    'type' => 'fieldset',
                    'label' => Str::headline($handle),
                    'config' => ['fieldset' => $handle],
                ];
                continue;
            }
            $handle = (string) ($item['handle'] ?? '');
            $def = is_array($item['field'] ?? null) ? $item['field'] : [];
            if ($handle === '' || $def === []) {
                continue;
            }
            $mapped = $this->mapField($handle, $def, $fieldsetFiles, $context);
            if ($mapped !== null) {
                $out[] = $mapped;
            }
        }

        return $out;
    }

    /**
     * Extract all fields from a Statamic blueprint's tabs/sections structure.
     *
     * @return array<int, array<string, mixed>>
     */
    public function blueprintFields(array $blueprint, array $fieldsetFiles, ?string $context = null): array
    {
        $fields = [];
        foreach ((array) ($blueprint['tabs'] ?? []) as $tab) {
            foreach ((array) ($tab['sections'] ?? []) as $section) {
                foreach ((array) ($section['fields'] ?? []) as $item) {
                    $fields[] = $item;
                }
            }
        }
        // Statamic blueprints may also carry a flat `fields` list (fieldsets).
        foreach ((array) ($blueprint['fields'] ?? []) as $item) {
            $fields[] = $item;
        }

        return $this->mapFieldList($fields, $fieldsetFiles, $context);
    }

    /**
     * Map a single Statamic field def to a Sunrice def (null = skip).
     *
     * @param  array<string, mixed>  $def
     * @return array<string, mixed>|null
     */
    public function mapField(string $handle, array $def, array $fieldsetFiles, ?string $context = null): ?array
    {
        $type = (string) ($def['type'] ?? 'text');
        if (in_array($type, self::SKIP_TYPES, true)) {
            $this->skipped[$type] = ($this->skipped[$type] ?? 0) + 1;

            return null;
        }

        [$rules, $required] = $this->validation($def['validate'] ?? null);

        $field = [
            'handle' => $handle,
            'type' => null,
            'label' => (string) ($def['display'] ?? Str::headline($handle)),
            'instructions' => isset($def['instructions']) ? (string) $def['instructions'] : null,
            'required' => $required,
            'validation' => $rules === [] ? null : $rules,
            'translatable' => (bool) ($def['localizable'] ?? false),
            'config' => [],
        ];

        switch ($type) {
            case 'text':
            case 'video':
                $field['type'] = 'text';
                break;
            case 'textarea':
            case 'code':
                $field['type'] = 'textarea';
                break;
            case 'markdown':
            case 'bard':
                $field['type'] = 'rich_text';
                // Remember the source format so the value converter knows
                // whether stored data is ProseMirror JSON or markdown.
                $field['config']['_source_format'] = $type;
                break;
            case 'assets':
                $field['type'] = 'asset';
                $max = $def['max_files'] ?? null;
                $field['config']['multiple'] = $max === null || (int) $max !== 1;
                break;
            case 'entries':
                $field['type'] = 'entries';
                $field['config']['collections'] = array_values((array) ($def['collections'] ?? []));
                if (isset($def['max_items'])) {
                    $field['config']['max'] = (int) $def['max_items'];
                }
                break;
            case 'taxonomy':
            case 'terms':
                $field['type'] = 'terms';
                $taxonomies = array_values((array) ($def['taxonomies'] ?? ($def['taxonomy'] ?? [])));
                $field['config']['taxonomy'] = $taxonomies[0] ?? null;
                if (count($taxonomies) > 1) {
                    $field['config']['_extra_taxonomies'] = array_slice($taxonomies, 1);
                }
                break;
            case 'link':
                $field['type'] = 'link';
                break;
            case 'select':
            case 'radio':
            case 'button_group':
            case 'checkboxes':
                $field['type'] = 'select';
                $field['config']['options'] = $this->options($def['options'] ?? []);
                $field['config']['multiple'] = $type === 'checkboxes' || (bool) ($def['multiple'] ?? false);
                break;
            case 'toggle':
                $field['type'] = 'toggle';
                break;
            case 'integer':
            case 'float':
                $field['type'] = 'number';
                break;
            case 'date':
            case 'time':
                $field['type'] = 'date';
                break;
            case 'grid':
            case 'table': // statamic table mode is a grid variant
                $field['type'] = 'repeater';
                $field['config']['fields'] = $this->mapFieldList(
                    (array) ($def['fields'] ?? []), $fieldsetFiles, $context
                );
                break;
            case 'group':
                $field['type'] = 'group';
                $field['config']['fields'] = $this->mapFieldList(
                    (array) ($def['fields'] ?? []), $fieldsetFiles, $context
                );
                break;
            case 'replicator':
            case 'sets':
                $field['type'] = 'flexible';
                [$handles, $map] = $this->registerSets((array) ($def['sets'] ?? []), $fieldsetFiles, $context ?? $handle);
                $field['config']['fieldsets'] = $handles;
                $field['config']['set_handles'] = $map;
                break;
            default:
                $this->skipped[$type] = ($this->skipped[$type] ?? 0) + 1;

                return null;
        }

        if (array_key_exists('default', $def)) {
            $field['config']['default'] = $def['default'];
        }
        if (isset($def['placeholder'])) {
            $field['config']['placeholder'] = $def['placeholder'];
        }

        return $field;
    }

    /**
     * Flatten statamic validate (string 'required|max:255' or list) into
     * [extra rules, required].
     *
     * @return array{0: array<int, string>, 1: bool}
     */
    private function validation(mixed $validate): array
    {
        $parts = [];
        foreach ((array) $validate as $v) {
            foreach (explode('|', (string) $v) as $rule) {
                if (trim($rule) !== '') {
                    $parts[] = trim($rule);
                }
            }
        }
        $required = in_array('required', $parts, true);

        return [array_values(array_diff($parts, ['required'])), $required];
    }

    /**
     * Statamic options: assoc {key: label} or list of {key, value} or plain list.
     *
     * @return array<int, array{value: string, label: string}>
     */
    private function options(mixed $options): array
    {
        $out = [];
        foreach ((array) $options as $k => $v) {
            if (is_int($k)) {
                if (is_array($v)) {
                    $value = (string) ($v['key'] ?? $v['value'] ?? '');
                    $out[] = ['value' => $value, 'label' => (string) ($v['value'] ?? $v['label'] ?? $value)];
                } else {
                    $out[] = ['value' => (string) $v, 'label' => (string) $v];
                }
            } else {
                $out[] = ['value' => (string) $k, 'label' => is_array($v) ? (string) ($v['value'] ?? $k) : (string) $v];
            }
        }

        return array_values(array_filter($out, fn ($o) => $o['value'] !== ''));
    }

    /**
     * Register every set inside a replicator `sets` config as a Sunrice
     * fieldset. Returns [ordered fieldset handles, setKey => handle map].
     *
     * @param  array<string, mixed>  $sets
     * @return array{0: array<int, string>, 1: array<string, string>}
     */
    private function registerSets(array $sets, array $fieldsetFiles, string $context): array
    {
        $handles = [];
        $map = [];

        $walker = function (array $node) use (&$walker, &$handles, &$map, $fieldsetFiles, $context): void {
            foreach ($node as $key => $set) {
                if (! is_array($set)) {
                    continue;
                }
                if (isset($set['sets']) && is_array($set['sets'])) {
                    $walker($set['sets']); // a group of sets
                    continue;
                }
                $fields = $this->mapFieldList((array) ($set['fields'] ?? []), $fieldsetFiles, (string) $key);
                $handle = $this->claimFieldset((string) $key, (string) ($set['display'] ?? $key), $fields, $context);
                $handles[] = $handle;
                $map[(string) $key] = $handle;
            }
        };
        $walker($sets);

        return [$handles, $map];
    }

    /**
     * Claim a fieldset handle for a set key; on signature collision
     * (same handle, different fields) a {context}_{key} handle is used.
     *
     * @param  array<int, array<string, mixed>>  $fields
     */
    private function claimFieldset(string $key, string $title, array $fields, string $context): string
    {
        $base = Str::of($key)->lower()->replaceMatches('/[^a-z0-9]+/', '_')->trim('_')->toString();
        if ($base === '' || ! preg_match('/^[a-z]/', $base)) {
            $base = 'set_'.$base;
        }
        $signature = md5(json_encode($fields) ?: '');

        foreach ([$base, $context.'_'.$base, $base.'_'.$context, $base.'_'.substr($signature, 0, 6)] as $candidate) {
            if (! isset($this->signatures[$candidate])) {
                $this->signatures[$candidate] = $signature;
                $this->fieldsets[$candidate] = ['title' => $title, 'fields' => $fields];

                return $candidate;
            }
            if ($this->signatures[$candidate] === $signature) {
                return $candidate;
            }
        }

        // Last resort: unique suffix.
        $candidate = $base.'_'.substr($signature, 0, 8);
        $this->signatures[$candidate] = $signature;
        $this->fieldsets[$candidate] = ['title' => $title, 'fields' => $fields];

        return $candidate;
    }

    /**
     * Ensure a Sunrice fieldset exists for an `import: ns.name` reference,
     * converting the statamic fieldset file if needed.
     *
     * @param  array<string, array<string, mixed>>  $fieldsetFiles
     */
    public function fieldsetHandleForImport(string $ref, array $fieldsetFiles): string
    {
        $handle = str_replace('.', '_', $ref);
        if (! isset($this->fieldsets[$handle])) {
            $file = $fieldsetFiles[$ref] ?? null;
            $fields = $file ? $this->mapFieldList((array) ($file['fields'] ?? []), $fieldsetFiles, $ref) : [];
            $this->fieldsets[$handle] = [
                'title' => (string) ($file['title'] ?? Str::headline($ref)),
                'fields' => $fields,
            ];
            $this->signatures[$handle] = md5(json_encode($fields) ?: '');
        }

        return $handle;
    }
}
