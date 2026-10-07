<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\StatamicImport\BardToHtml;
use App\StatamicImport\FieldMapper;
use App\StatamicImport\Statamic;
use App\StatamicImport\ValueConverter;
use Illuminate\Console\Command;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Sunrice\Actions\Assets\UploadAsset;
use Sunrice\Actions\Entries\PublishTranslation;
use Sunrice\Models\Asset;
use Sunrice\Models\Blueprint;
use Sunrice\Models\Collection;
use Sunrice\Models\Entry;
use Sunrice\Models\Fieldset;
use Sunrice\Models\Form;
use Sunrice\Models\GlobalSet;
use Sunrice\Models\Menu;
use Sunrice\Models\Setting;
use Sunrice\Models\Taxonomy;
use Sunrice\Models\Term;
use Sunrice\Support\Locales;

/**
 * Imports the Statamic flat-file site (../statamic submodule) into Sunrice.
 *
 * See PROGRESS.md at the repo root for the stage list and current status.
 */
class ImportStatamic extends Command
{
    protected $signature = 'statamic:import
        {--source= : Path to the statamic install (default ../statamic)}
        {--fresh : Wipe all Sunrice tables before importing}
        {--only= : Comma-separated stage subset: assets,structure,taxonomies,globals,forms,entries,menus,settings}
        {--skip-assets : Skip the asset upload stage}
        {--fast : Skip image-size generation during asset upload (run sunrice:regenerate-images afterwards)}';

    protected $description = 'Import content from the Statamic flat-file site into Sunrice CMS';

    // entries before globals: globals contain entry:: links that resolve via entryMap.
    private const STAGES = ['assets', 'structure', 'taxonomies', 'entries', 'globals', 'forms', 'menus', 'settings'];

    /** Collection handles that have no public detail page in the source. */
    private const NO_SINGLE = ['achievements', 'pop_up'];

    /** Per-collection route overrides (Statamic route/mount semantics). */
    private const ROUTES = [
        'pages' => '/{slug}',
        'posts' => '/berita-dan-artikel/{slug}',
        'products' => '/products/{slug}',
        'dealers' => '/dealer/{slug}', // statamic `mount: dealers` on the /dealer page
        'careers' => '/karier/{slug}',
    ];

    private string $src;

    /** @var array<string, array<string, mixed>> statamic fieldset files keyed 'ns.name' */
    private array $fieldsetFiles = [];

    /** @var array<string, int> relative asset path => sunrice asset id */
    private array $assetMap = [];

    /** @var array<string, int> statamic entry id => sunrice entry id */
    private array $entryMap = [];

    /** @var array<string, int> "taxonomy/slug" => term id */
    private array $termMap = [];

    /** @var array<string, int> blueprint handle => id */
    private array $blueprintIds = [];

    /** @var array<string, int> collection handle => id */
    private array $collectionIds = [];

    private FieldMapper $mapper;

    private ValueConverter $values;

    private BardToHtml $bard;

    /** @var array<int, string> */
    private array $warnings = [];

    public function handle(): int
    {
        $this->src = rtrim(realpath((string) ($this->option('source') ?: base_path('../statamic'))) ?: '', '/');
        if ($this->src === '' || ! is_dir($this->src.'/content')) {
            $this->error("Statamic source not found at {$this->src} (looked for a content/ dir). Pass --source=PATH.");

            return self::FAILURE;
        }
        $this->info("Source: {$this->src}");

        if ($this->option('fresh')) {
            $this->fresh();
        }
        if ($this->option('fast')) {
            config(['sunrice.assets.image_sizes' => []]);
        }

        $this->mapper = new FieldMapper;
        $this->fieldsetFiles = $this->loadFieldsetFiles();
        $this->bard = new BardToHtml(fn (string $src): array => $this->resolveBardAsset($src));
        $this->values = new ValueConverter(
            assetIdFor: fn (string $p) => $this->assetIdFor($p),
            entryIdFor: fn (string $id) => $this->entryMap[$id] ?? null,
            termIdFor: function (string $tax, string $slug) {
                $this->ensureTermMap();

                return $this->termMap[$tax.'/'.$slug] ?? null;
            },
            assetUrlFor: function (string $id): string {
                $asset = Asset::query()->find((int) $id);

                return $asset ? static::relativeUrl($asset->url()) : '#';
            },
            warn: function (string $msg, string $ctx): void {
                $this->warnings[] = $ctx === '' ? $msg : "{$msg} (field {$ctx})";
            },
            bard: $this->bard,
        );

        $stages = $this->stages();
        foreach ($stages as $stage) {
            $this->info("== {$stage} ==");
            $this->{$stage}();
        }

        $this->reportWarnings();

        $this->info('Import finished.');

        return self::SUCCESS;
    }

    /** @return array<int, string> */
    private function stages(): array
    {
        $only = array_filter(array_map('trim', explode(',', (string) $this->option('only'))));
        if ($only !== []) {
            $invalid = array_diff($only, self::STAGES);
            if ($invalid !== []) {
                $this->warn('Unknown stage(s): '.implode(',', $invalid).'. Valid: '.implode(',', self::STAGES));
            }

            return array_values(array_intersect($only, self::STAGES));
        }
        if ($this->option('skip-assets')) {
            return array_values(array_diff(self::STAGES, ['assets']));
        }

        return self::STAGES;
    }

    private function fresh(): void
    {
        $this->warn('Wiping Sunrice tables (--fresh).');
        foreach ([
            'sunrice_references', 'sunrice_revisions', 'sunrice_redirects',
            'sunrice_entry_translations', 'sunrice_entries',
            'sunrice_menu_items', 'sunrice_menus',
            'sunrice_global_values', 'sunrice_globals',
            'sunrice_form_submissions', 'sunrice_forms',
            'sunrice_term_translations', 'sunrice_terms', 'sunrice_taxonomies',
            'sunrice_collections', 'sunrice_blueprints', 'sunrice_fieldsets',
            'sunrice_assets', 'sunrice_asset_folders', 'sunrice_settings',
        ] as $table) {
            DB::table($table)->delete();
        }
        Storage::disk('public')->deleteDirectory('sunrice');
        @unlink(storage_path('app/statamic-asset-map.json'));
    }

    // ------------------------------------------------------------------
    // Stage: assets
    // ------------------------------------------------------------------

    private function assets(): void
    {
        $dir = $this->src.'/public/assets';
        if (! is_dir($dir)) {
            $this->warn('No public/assets dir in source; skipping.');

            return;
        }
        $files = collect(\Illuminate\Support\Facades\File::allFiles($dir))
            ->filter(fn ($f) => ! str_starts_with($f->getRelativePathname(), '.meta'))
            ->filter(fn ($f) => ! str_ends_with(strtolower($f->getFilename()), '.yaml'))
            ->values();

        // Resume-aware: rel paths already imported map to asset ids via a
        // manifest kept on the local disk.
        $this->ensureAssetMap();
        $todo = $files->reject(fn ($f) => isset($this->assetMap[$f->getRelativePathname()]))->values();
        if ($todo->isEmpty()) {
            $this->info('All '.count($this->assetMap).' assets already imported; nothing to upload.');

            return;
        }

        // upload_max_filesize is PHP_INI_PERDIR (ini_set can't change it):
        // run with `php -d upload_max_filesize=64M -d post_max_size=64M`
        // for files over 2 MB. The config cap is lifted either way.
        config(['sunrice.assets.max_upload_kb' => 65536]);

        $this->info("Uploading {$todo->count()} asset files (".count($this->assetMap).' already imported)...');
        $bar = $this->output->createProgressBar($todo->count());
        $uploader = app(UploadAsset::class);
        foreach ($todo as $file) {
            $rel = $file->getRelativePathname();
            $meta = $this->assetMeta($dir.'/.meta/'.$rel.'.yaml');
            try {
                $upload = new UploadedFile($file->getPathname(), $file->getFilename(), null, 0, true);
                $asset = $uploader->handle($upload, [
                    'title' => $meta['title'] ?? pathinfo($file->getFilename(), PATHINFO_FILENAME),
                    'alt' => $meta['alt'] ?? null,
                ]);
                $this->assetMap[$rel] = $asset->id;
            } catch (\Throwable $e) {
                $this->warnings[] = "asset upload failed: {$rel} — {$e->getMessage()}";
            }
            $bar->advance();
        }
        $bar->finish();
        $this->newLine();
        file_put_contents(storage_path('app/statamic-asset-map.json'), json_encode($this->assetMap, JSON_PRETTY_PRINT));
        $this->info(count($this->assetMap).' assets imported.');
    }

    /** @return array<string, mixed> */
    private function assetMeta(string $path): array
    {
        if (! is_file($path)) {
            return [];
        }
        $yaml = Statamic::readYaml($path);

        return (array) ($yaml['data'] ?? []);
    }

    private function assetIdFor(string $path): ?int
    {
        $this->ensureAssetMap();
        $path = preg_replace('/^assets::/', '', $path) ?? $path;

        return $this->assetMap[$path] ?? null;
    }

    /**
     * Load the rel-path → asset-id manifest (written by the assets stage)
     * so stages running without `assets` can still resolve references.
     */
    private function ensureAssetMap(): void
    {
        if ($this->assetMap !== []) {
            return;
        }
        $manifest = storage_path('app/statamic-asset-map.json');
        if (! is_file($manifest)) {
            return;
        }
        $saved = json_decode((string) file_get_contents($manifest), true) ?: [];
        $valid = Asset::query()->whereIn('id', array_values($saved))->pluck('id')->all();
        $this->assetMap = array_filter($saved, fn ($id) => in_array($id, $valid, true));
    }

    /**
     * Rebuild taxonomy/slug → term-id map from the DB (for runs that
     * skip the taxonomies stage).
     */
    private function ensureTermMap(): void
    {
        if ($this->termMap !== []) {
            return;
        }
        $rows = DB::table('sunrice_terms as t')
            ->join('sunrice_taxonomies as tx', 'tx.id', '=', 't.taxonomy_id')
            ->join('sunrice_term_translations as tt', 'tt.term_id', '=', 't.id')
            ->where('tt.locale', Locales::main())
            ->get(['t.id', 'tx.handle', 'tt.slug']);
        foreach ($rows as $row) {
            $this->termMap[$row->handle.'/'.$row->slug] = $row->id;
        }
    }

    /** @return array{id: int|null, url: string} */
    private function resolveBardAsset(string $src): array
    {
        $path = preg_replace('/^asset::[^:]+::/', '', $src) ?? $src;
        $id = $this->assetIdFor($path);
        if ($id !== null) {
            $asset = Asset::query()->find($id);

            return ['id' => $id, 'url' => $asset ? static::relativeUrl($asset->url()) : '#'];
        }
        if (preg_match('#^https?://#', $src)) {
            return ['id' => null, 'url' => $src];
        }
        $this->warnings[] = "bard image not found: {$src}";

        return ['id' => null, 'url' => '#'];
    }

    /**
     * Strip the origin from an asset URL so imported HTML doesn't bake in
     * the current APP_URL (breaks when the site moves hosts).
     */
    private static function relativeUrl(string $url): string
    {
        return (string) preg_replace('#^https?://[^/]+#i', '', $url);
    }

    // ------------------------------------------------------------------
    // Stage: structure (fieldsets, blueprints, collections)
    // ------------------------------------------------------------------

    private function structure(): void
    {
        // 1. Collection blueprints (also registers replicator-set fieldsets).
        $collectionBlueprints = [];
        foreach (glob($this->src.'/content/collections/*.yaml') ?: [] as $configFile) {
            $handle = pathinfo($configFile, PATHINFO_FILENAME);
            $config = Statamic::readYaml($configFile);
            $collectionBlueprints[$handle] = $config;
        }

        foreach ($collectionBlueprints as $handle => $config) {
            $bpHandles = (array) ($config['blueprints'] ?? []);
            if ($bpHandles === []) {
                // Statamic convention: no explicit list → all files in the
                // collection's blueprint directory.
                $bpHandles = array_map(
                    fn ($f) => pathinfo($f, PATHINFO_FILENAME),
                    glob($this->src."/resources/blueprints/collections/{$handle}/*.yaml") ?: []
                );
            }
            foreach ($bpHandles as $bpHandle) {
                $bpFile = $this->src."/resources/blueprints/collections/{$handle}/{$bpHandle}.yaml";
                if (! is_file($bpFile)) {
                    $this->warnings[] = "blueprint file missing: collections/{$handle}/{$bpHandle}";

                    continue;
                }
                $bp = Statamic::readYaml($bpFile);
                $fields = $this->mapper->blueprintFields($bp, $this->fieldsetFiles, $bpHandle);
                $blueprint = Blueprint::query()->updateOrCreate(
                    ['handle' => $bpHandle],
                    ['title' => (string) ($bp['title'] ?? Str::headline($bpHandle)), 'fields' => $fields]
                );
                $this->blueprintIds[$bpHandle] = $blueprint->id;
                $this->line("  blueprint {$bpHandle} (".count($fields).' fields)');
            }
        }

        // 2. Taxonomy blueprints.
        foreach (glob($this->src.'/content/taxonomies/*.yaml') ?: [] as $configFile) {
            $handle = pathinfo($configFile, PATHINFO_FILENAME);
            $bpHandles = (array) (Statamic::readYaml($configFile)['blueprints'] ?? []);
            if ($bpHandles === []) {
                // Statamic convention: no explicit list → all files in the
                // taxonomy's blueprint directory (e.g. industries/industry.yaml).
                $bpHandles = array_map(
                    fn ($f) => pathinfo($f, PATHINFO_FILENAME),
                    glob($this->src."/resources/blueprints/taxonomies/{$handle}/*.yaml") ?: []
                );
            }
            foreach ($bpHandles as $bpHandle) {
                $bpFile = $this->src."/resources/blueprints/taxonomies/{$handle}/{$bpHandle}.yaml";
                if (! is_file($bpFile)) {
                    continue;
                }
                $bp = Statamic::readYaml($bpFile);
                $fields = $this->mapper->blueprintFields($bp, $this->fieldsetFiles, $bpHandle);
                $blueprint = Blueprint::query()->updateOrCreate(
                    ['handle' => $bpHandle],
                    ['title' => (string) ($bp['title'] ?? Str::headline($bpHandle)), 'fields' => $fields]
                );
                $this->blueprintIds[$bpHandle] = $blueprint->id;
            }
        }

        // 3. Global-set blueprints.
        foreach (glob($this->src.'/resources/blueprints/globals/*.yaml') ?: [] as $bpFile) {
            $bpHandle = pathinfo($bpFile, PATHINFO_FILENAME);
            $bp = Statamic::readYaml($bpFile);
            $fields = $this->mapper->blueprintFields($bp, $this->fieldsetFiles, 'globals_'.$bpHandle);
            $blueprint = Blueprint::query()->updateOrCreate(
                ['handle' => 'globals_'.$bpHandle],
                ['title' => (string) ($bp['title'] ?? Str::headline($bpHandle)), 'fields' => $fields]
            );
            $this->blueprintIds['globals_'.$bpHandle] = $blueprint->id;
        }

        // 4. Create all fieldsets registered during blueprint conversion.
        //    Fieldsets may themselves add new fieldsets (nested replicator
        //    sets), so loop until the queue drains.
        while ($this->mapper->fieldsets !== []) {
            $batch = $this->mapper->fieldsets;
            $this->mapper->fieldsets = [];
            foreach ($batch as $handle => $set) {
                Fieldset::query()->updateOrCreate(
                    ['handle' => $handle],
                    ['title' => $set['title'], 'fields' => $set['fields']]
                );
            }
        }
        $this->line('  '.Fieldset::count().' fieldsets created');

        // 5. Collections.
        foreach ($collectionBlueprints as $handle => $config) {
            $bpHandle = (array) ($config['blueprints'] ?? []);
            if ($bpHandle === []) {
                $bpHandle = array_map(
                    fn ($f) => pathinfo($f, PATHINFO_FILENAME),
                    glob($this->src."/resources/blueprints/collections/{$handle}/*.yaml") ?: []
                );
            }
            $settings = [
                'route' => self::ROUTES[$handle] ?? null,
                'has_single' => ! in_array($handle, self::NO_SINGLE, true),
                'has_archive' => false,
                'translatable' => true,
                'sluggable' => true,
                'dated' => (bool) ($config['date'] ?? false),
            ];
            if (isset($config['template'])) {
                $settings['template'] = $config['template'];
            }
            if (isset($config['icon'])) {
                $settings['icon'] = 'folder';
            }
            $taxonomies = array_values(array_filter(array_map(
                fn ($h) => Taxonomy::query()->where('handle', $h)->value('id'),
                (array) ($config['taxonomies'] ?? [])
            )));

            $collection = Collection::query()->updateOrCreate(
                ['handle' => $handle],
                [
                    'title' => (string) ($config['title'] ?? Str::headline($handle)),
                    'blueprint_id' => $this->blueprintIds[$bpHandle[0] ?? $handle] ?? null,
                    'settings' => array_filter($settings, fn ($v) => $v !== null),
                ]
            );
            if ($taxonomies !== []) {
                $collection->taxonomies()->sync($taxonomies);
            }
            $this->collectionIds[$handle] = $collection->id;
            $this->line("  collection {$handle}");
        }

        if ($this->mapper->skipped !== []) {
            $this->warn('Skipped statamic field types: '.collect($this->mapper->skipped)->map(fn ($n, $t) => "{$t}×{$n}")->implode(', '));
        }
    }

    // ------------------------------------------------------------------
    // Stage: taxonomies + terms
    // ------------------------------------------------------------------

    private function taxonomies(): void
    {
        foreach (glob($this->src.'/content/taxonomies/*.yaml') ?: [] as $configFile) {
            $handle = pathinfo($configFile, PATHINFO_FILENAME);
            $config = Statamic::readYaml($configFile);
            $bpHandles = (array) ($config['blueprints'] ?? []);
            if ($bpHandles === []) {
                $bpHandles = array_map(
                    fn ($f) => pathinfo($f, PATHINFO_FILENAME),
                    glob($this->src."/resources/blueprints/taxonomies/{$handle}/*.yaml") ?: []
                );
            }
            $bpHandle = $bpHandles === [] ? [$handle] : $bpHandles;
            $taxonomy = Taxonomy::query()->updateOrCreate(
                ['handle' => $handle],
                [
                    'title' => (string) ($config['title'] ?? Str::headline($handle)),
                    'blueprint_id' => $this->blueprintIds[$bpHandle[0]] ?? null,
                    'hierarchical' => true,
                    'settings' => array_filter([
                        'route' => isset($config['route']) ? (string) $config['route'] : null,
                        'has_archive' => isset($config['route']),
                    ], fn ($v) => $v !== null),
                ]
            );

            // Re-runnable: replace existing terms of this taxonomy. Terms are
            // soft-deleted, so translations must go first or the unique
            // (taxonomy_id, locale, slug) key collides on re-import.
            \Sunrice\Models\TermTranslation::query()->where('taxonomy_id', $taxonomy->id)->delete();
            Term::query()->where('taxonomy_id', $taxonomy->id)->forceDelete();
            $this->termMap = collect($this->termMap)->filter(
                fn ($id, $key) => ! str_starts_with((string) $key, $handle.'/')
            )->all();

            $terms = [];
            foreach (glob($this->src."/content/taxonomies/{$handle}/*.yaml") ?: [] as $termFile) {
                $slug = pathinfo($termFile, PATHINFO_FILENAME);
                $data = Statamic::readYaml($termFile);
                $terms[$slug] = $data;
            }
            // Pass 1: create term rows.
            $ids = [];
            foreach ($terms as $slug => $data) {
                $term = Term::query()->create(['taxonomy_id' => $taxonomy->id, 'sort_order' => count($ids)]);
                $term->translations()->create([
                    'taxonomy_id' => $taxonomy->id,
                    'locale' => Locales::main(),
                    'name' => (string) ($data['title'] ?? Str::headline($slug)),
                    'slug' => $slug,
                    'data' => $this->values->convert(
                        collect($data)->except(['title', 'slug', 'parent', 'updated_by', 'updated_at', 'blueprint'])->all(),
                        $this->fieldsForBlueprint((string) ($data['blueprint'] ?? ($bpHandle[0] ?? '')))
                    ),
                ]);
                $ids[$slug] = $term->id;
                $this->termMap[$handle.'/'.$slug] = $term->id;
            }
            // Pass 2: parent links.
            foreach ($terms as $slug => $data) {
                $parentSlug = $data['parent'] ?? null;
                if (is_string($parentSlug) && isset($ids[$parentSlug])) {
                    Term::query()->whereKey($ids[$slug])->update(['parent_id' => $ids[$parentSlug]]);
                }
            }
            $this->line("  taxonomy {$handle} (".count($ids).' terms)');
        }
    }

    /** @return array<int, array<string, mixed>> */
    private function fieldsForBlueprint(string $handle): array
    {
        $this->ensureBlueprintIds();
        $id = $this->blueprintIds[$handle] ?? null;
        if ($id === null) {
            return [];
        }
        $bp = Blueprint::query()->find($id);

        return $bp ? \Sunrice\Fields\BlueprintSchema::make((array) $bp->fields)->fields() : [];
    }

    /**
     * Populate blueprintIds from the DB so partial runs (e.g. --only=entries)
     * can resolve fields without the structure stage having run first.
     */
    private function ensureBlueprintIds(): void
    {
        Blueprint::query()->get(['id', 'handle'])
            ->each(fn ($bp) => $this->blueprintIds[$bp->handle] ??= $bp->id);
    }

    // ------------------------------------------------------------------
    // Stage: globals
    // ------------------------------------------------------------------

    private function globals(): void
    {
        foreach (glob($this->src.'/content/globals/*.yaml') ?: [] as $defFile) {
            $handle = pathinfo($defFile, PATHINFO_FILENAME);
            $def = Statamic::readYaml($defFile);
            $bpId = $this->blueprintIds['globals_'.$handle] ?? null;
            $set = GlobalSet::query()->updateOrCreate(
                ['handle' => $handle],
                [
                    'title' => (string) ($def['title'] ?? Str::headline($handle)),
                    'blueprint_id' => $bpId,
                    'group' => 'global',
                    'translatable' => true,
                ]
            );
            $valuesFile = $this->src."/content/globals/default/{$handle}.yaml";
            $raw = is_file($valuesFile) ? Statamic::readYaml($valuesFile) : [];
            $fields = $bpId ? $this->fieldsForBlueprint('globals_'.$handle) : [];
            $set->values()->updateOrCreate(
                ['locale' => Locales::main()],
                ['data' => $this->values->convert(
                    collect($raw)->except(['updated_by', 'updated_at'])->all(),
                    $fields
                )]
            );
            $this->line("  global {$handle}");
        }
    }

    // ------------------------------------------------------------------
    // Stage: forms
    // ------------------------------------------------------------------

    private function forms(): void
    {
        foreach (glob($this->src.'/resources/forms/*.yaml') ?: [] as $file) {
            $handle = pathinfo($file, PATHINFO_FILENAME);
            $config = Statamic::readYaml($file);

            // Field definitions live in the form blueprint, not the form config.
            $bpFile = $this->src."/resources/blueprints/forms/{$handle}.yaml";
            $bp = is_file($bpFile) ? Statamic::readYaml($bpFile) : [];

            $fields = [];
            foreach ((array) ($bp['tabs'] ?? $config['tabs'] ?? []) as $tab) {
                foreach ((array) ($tab['sections'] ?? []) as $section) {
                    foreach ((array) ($section['fields'] ?? []) as $item) {
                        $field = $this->formField($item);
                        if ($field !== null) {
                            $fields[] = $field;
                        }
                    }
                }
            }

            $emails = collect((array) ($config['email'] ?? []))
                ->map(fn ($e) => is_array($e) ? ($e['to'] ?? null) : $e)
                ->filter()->implode(',');

            Form::query()->updateOrCreate(
                ['handle' => $handle],
                [
                    'title' => (string) ($config['title'] ?? Str::headline($handle)),
                    'fields' => $fields,
                    'settings' => array_filter([
                        'honeypot' => $config['honeypot'] ?? null,
                        'notify_emails' => $emails !== '' ? $emails : null,
                        'success_message' => 'Terima kasih! Pesan Anda telah terkirim.',
                    ], fn ($v) => $v !== null),
                ]
            );
            $this->line("  form {$handle} (".count($fields).' fields)');
        }
    }

    /** @return array<string, mixed>|null */
    private function formField(mixed $item): ?array
    {
        if (! is_array($item) || ! isset($item['handle'], $item['field'])) {
            return null;
        }
        $def = (array) $item['field'];
        $type = match ((string) ($def['type'] ?? 'text')) {
            'textarea' => 'textarea',
            'integer', 'float' => 'number',
            'toggle' => 'toggle',
            'select', 'radio', 'checkboxes', 'button_group' => 'select',
            'date' => 'date',
            'assets' => 'file',
            default => 'text',
        };
        [$rules, $required] = [[], false];
        foreach ((array) ($def['validate'] ?? []) as $v) {
            foreach (explode('|', (string) $v) as $rule) {
                if ($rule === 'required') {
                    $required = true;
                } elseif ($rule !== '') {
                    $rules[] = $rule;
                }
            }
        }
        $options = null;
        if ($type === 'select') {
            $options = [];
            foreach ((array) ($def['options'] ?? []) as $k => $v) {
                $options[] = is_int($k)
                    ? (is_array($v) ? (string) ($v['key'] ?? $v['value'] ?? '') : (string) $v)
                    : (string) $k;
            }
        }

        return array_filter([
            'handle' => (string) $item['handle'],
            'type' => $type,
            'label' => (string) ($def['display'] ?? Str::headline((string) $item['handle'])),
            'required' => $required,
            'validation' => $rules === [] ? null : $rules,
            'config' => array_filter([
                'placeholder' => $def['placeholder'] ?? null,
                'options' => $options,
                'input_type' => $def['input_type'] ?? null,
            ], fn ($v) => $v !== null),
        ], fn ($v) => $v !== null);
    }

    // ------------------------------------------------------------------
    // Stage: entries
    // ------------------------------------------------------------------

    /**
     * Parse every statamic entry file once, caching the result for
     * stages that need it (entries themselves, menus, settings).
     *
     * @return array<int, array<string, mixed>>
     */
    private function scanEntries(): array
    {
        /** @var array<int, array<string, mixed>> */
        $parsed = [];
        foreach (glob($this->src.'/content/collections/*/') ?: [] as $dir) {
            $handle = basename(rtrim($dir, '/'));
            $collection = Collection::query()->where('handle', $handle)->first();
            if ($collection === null) {
                $this->warnings[] = "collection not imported: {$handle}";

                continue;
            }
            $tree = $this->src."/content/trees/collections/{$handle}.yaml";
            $treeOrder = is_file($tree) ? $this->treeSlugs(Statamic::readYaml($tree)) : [];

            foreach (glob($dir.'*.md') ?: [] as $i => $file) {
                [$data, $body] = Statamic::parseDoc($file);
                [$slugFromFile, $date] = Statamic::slugFromFilename(basename($file));
                $sort = array_search($slugFromFile, $treeOrder, true);
                $parsed[] = [
                    'collection' => $handle,
                    'collection_id' => $collection->id,
                    'file' => $file,
                    'data' => $data,
                    'body' => $body,
                    'slug' => (string) ($data['slug'] ?? $slugFromFile),
                    'statamic_id' => (string) ($data['id'] ?? $slugFromFile),
                    'date' => $date,
                    'sort' => $sort !== false ? $sort : $i,
                ];
            }
        }

        return $parsed;
    }

    /**
     * Rebuild statamic-id → entry-id map from the database (for runs
     * that skip the entries stage).
     */
    private function buildEntryMap(): void
    {
        if ($this->entryMap !== []) {
            return;
        }
        $translations = \Sunrice\Models\EntryTranslation::query()
            ->where('locale', Locales::main())
            ->get(['entry_id', 'collection_id', 'slug']);
        $byCollSlug = $translations->mapWithKeys(
            fn ($t) => [$t->collection_id.'/'.$t->slug => $t->entry_id]
        );
        foreach ($this->scanEntries() as $row) {
            $id = $byCollSlug[$row['collection_id'].'/'.$row['slug']] ?? null;
            if ($id !== null) {
                $this->entryMap[$row['statamic_id']] = $id;
                $this->entryMap[$row['slug']] ??= $id;
            }
        }
    }

    private function entries(): void
    {
        // Re-runnable without --fresh: drop previously imported entries.
        if (Entry::query()->exists()) {
            $this->warn('Removing existing entries before re-import.');
            DB::table('sunrice_references')->delete();
            DB::table('sunrice_revisions')->delete();
            DB::table('sunrice_entry_translations')->delete();
            DB::table('sunrice_entries')->delete();
            $this->entryMap = [];
        }

        $publish = app(PublishTranslation::class);
        $main = Locales::main();

        // Parse all entry files first so every statamic id is known.
        $parsed = $this->scanEntries();

        // Pass 1: create entries + main translations (title/slug only), build id map.
        foreach ($parsed as $i => $row) {
            $published = ($row['data']['published'] ?? true) !== false;
            $entry = Entry::query()->create([
                'collection_id' => $row['collection_id'],
                'blueprint_id' => isset($row['data']['blueprint'])
                    ? ($this->blueprintIds[$row['data']['blueprint']] ?? null)
                    : null,
                'status' => $published ? 'published' : 'draft',
                'published_at' => $row['date'] ?? now(),
                'template' => isset($row['data']['template']) ? (string) $row['data']['template'] : null,
                'sort_order' => (int) $row['sort'],
            ]);
            $translation = $entry->translations()->create([
                'collection_id' => $row['collection_id'],
                'locale' => $main,
                'title' => (string) ($row['data']['title'] ?? Str::headline($row['slug'])),
                'slug' => $row['slug'],
                'data' => [],
            ]);
            $this->entryMap[$row['statamic_id']] = $entry->id;
            // Slug fallback for refs like 'entry::products' that use a slug-shaped id.
            if (! isset($this->entryMap[$row['slug']])) {
                $this->entryMap[$row['slug']] = $entry->id;
            }
            $row['entry'] = $entry;
            $row['translation'] = $translation;
            $parsed[$i] = $row;
        }
        $this->line('  '.count($parsed).' entries created');

        // Pass 2: convert data + seo, then publish.
        $bar = $this->output->createProgressBar(count($parsed));
        foreach ($parsed as $row) {
            /** @var Entry $entry */
            $entry = $row['entry'];
            $translation = $row['translation'];
            $published = ($row['data']['published'] ?? true) !== false;

            $bpHandle = (string) ($row['data']['blueprint'] ?? '');
            $fields = $this->fieldsForBlueprint($bpHandle !== '' ? $bpHandle : $this->collectionBlueprintHandle($row['collection']));

            $reserved = ['id', 'blueprint', 'title', 'slug', 'template', 'updated_by', 'updated_at', 'created_at', 'published', 'seo', 'date', 'imported', 'mount'];
            $rawData = collect($row['data'])->except($reserved)->all();
            if ($row['body'] !== '') {
                $rawData['content'] = $row['body'];
            }
            $data = $this->values->convert($rawData, $fields);
            $data = \Sunrice\Fields\BlueprintSchema::make($fields)->normalize($data);

            $seo = $this->seo($row['data']['seo'] ?? null, $data, (string) $translation->title);

            $translation->fill([
                'title' => (string) ($row['data']['title'] ?? $translation->title),
                'slug' => $row['slug'],
                'data' => $data,
                'seo' => $seo,
            ])->save();

            if ($published) {
                $publish->handle($translation, $row['date']);
            }
            $bar->advance();
        }
        $bar->finish();
        $this->newLine();
    }

    private function collectionBlueprintHandle(string $collection): string
    {
        $collectionModel = Collection::query()->where('handle', $collection)->first();
        $bp = $collectionModel?->blueprint_id ? Blueprint::query()->find($collectionModel->blueprint_id) : null;

        return $bp?->handle ?? $collection;
    }

    /**
     * Statamic `seo:` frontmatter → Sunrice seo JSON. '@seo:handle'
     * sentinels resolve to the converted field value.
     *
     * @return array<string, mixed>
     */
    private function seo(mixed $seo, array $data, string $title): array
    {
        $seo = is_array($seo) ? $seo : [];
        $out = [];
        foreach (['title', 'description', 'canonical'] as $k) {
            $v = $seo[$k] ?? null;
            if (is_string($v) && str_starts_with($v, '@seo:')) {
                $v = $data[substr($v, 5)] ?? null;
            }
            if (is_string($v) && $v !== '') {
                $out[$k] = $v;
            }
        }
        $image = $seo['image'] ?? null;
        if (is_string($image) && str_starts_with($image, '@seo:')) {
            $image = $data[substr($image, 5)] ?? null;
        }
        if (is_numeric($image)) {
            $out['image'] = (int) $image;
        } elseif (is_string($image) && $image !== '') {
            $id = $this->assetIdFor($image);
            if ($id !== null) {
                $out['image'] = $id;
            }
        }

        return $out;
    }

    /** @return array<int, string> */
    private function treeSlugs(array $tree): array
    {
        $slugs = [];
        foreach ((array) ($tree['tree'] ?? []) as $node) {
            $this->collectTreeSlugs($node, $slugs);
        }

        return $slugs;
    }

    /** @param array<string, mixed> $node */
    private function collectTreeSlugs(array $node, array &$slugs): void
    {
        if (isset($node['entry']) && is_string($node['entry'])) {
            $slugs[] = $node['entry'];
        }
        foreach ((array) ($node['children'] ?? []) as $child) {
            if (is_array($child)) {
                $this->collectTreeSlugs($child, $slugs);
            }
        }
    }

    // ------------------------------------------------------------------
    // Stage: menus
    // ------------------------------------------------------------------

    private function menus(): void
    {
        $this->buildEntryMap();
        $seen = [];
        foreach (glob($this->src.'/content/trees/navigation/*.yaml') ?: [] as $treeFile) {
            $handle = pathinfo($treeFile, PATHINFO_FILENAME);
            $defFile = $this->src."/content/navigation/{$handle}.yaml";
            $def = is_file($defFile) ? Statamic::readYaml($defFile) : [];

            $menu = Menu::query()->updateOrCreate(
                ['handle' => $handle],
                ['title' => (string) ($def['title'] ?? Str::headline($handle)), ]
            );
            $menu->items()->delete();
            $tree = Statamic::readYaml($treeFile);
            $this->menuItems($menu, (array) ($tree['tree'] ?? []), null);
            $seen[] = $handle;
            $this->line("  menu {$handle}");
        }
        // Nav defs without a tree file still create an (empty) menu.
        foreach (glob($this->src.'/content/navigation/*.yaml') ?: [] as $defFile) {
            $handle = pathinfo($defFile, PATHINFO_FILENAME);
            if (! in_array($handle, $seen, true)) {
                $def = Statamic::readYaml($defFile);
                Menu::query()->updateOrCreate(
                    ['handle' => $handle],
                    ['title' => (string) ($def['title'] ?? Str::headline($handle)), ]
                );
                $this->line("  menu {$handle} (empty)");
            }
        }
    }

    /** @param array<int, mixed> $nodes */
    private function menuItems(Menu $menu, array $nodes, ?int $parentId): void
    {
        $order = 0;
        foreach ($nodes as $node) {
            if (! is_array($node)) {
                continue;
            }
            $item = ['menu_id' => $menu->id, 'parent_id' => $parentId, 'sort_order' => $order++];
            if (isset($node['entry']) && is_string($node['entry'])) {
                $target = $this->entryMap[$node['entry']] ?? null;
                if ($target === null) {
                    $this->warnings[] = "menu item target missing: {$node['entry']} (menu {$menu->handle})";

                    continue;
                }
                $item['type'] = 'entry';
                $item['target_id'] = $target;
            } else {
                $item['type'] = 'url';
                $item['url'] = is_string($node['url'] ?? null) ? $node['url'] : '#';
            }
            if (isset($node['title']) && is_string($node['title'])) {
                $item['labels'] = [Locales::main() => $node['title']];
            }
            $created = $menu->items()->create($item);
            if (isset($node['children'])) {
                $this->menuItems($menu, (array) $node['children'], $created->id);
            }
        }
    }

    // ------------------------------------------------------------------
    // Stage: site settings
    // ------------------------------------------------------------------

    private function settings(): void
    {
        $home = Entry::query()
            ->whereHas('translations', fn ($q) => $q->where('slug', 'beranda')->orWhere('slug', 'home'))
            ->first();
        if ($home !== null) {
            Setting::set('homepage_entry_id', $home->id);
            $this->line("  homepage entry: {$home->id}");
        } else {
            $this->warnings[] = 'homepage entry not found (looked for slug beranda/home)';
        }
    }

    // ------------------------------------------------------------------

    /** @return array<string, array<string, mixed>> */
    private function loadFieldsetFiles(): array
    {
        $files = [];
        foreach (glob($this->src.'/resources/fieldsets/*/*.yaml') ?: [] as $file) {
            $ns = basename(dirname($file));
            $name = pathinfo($file, PATHINFO_FILENAME);
            $files["{$ns}.{$name}"] = Statamic::readYaml($file);
        }
        foreach (glob($this->src.'/resources/fieldsets/*.yaml') ?: [] as $file) {
            $name = pathinfo($file, PATHINFO_FILENAME);
            $files[$name] = Statamic::readYaml($file);
        }

        return $files;
    }

    private function reportWarnings(): void
    {
        if ($this->warnings === []) {
            return;
        }
        $this->warn(count($this->warnings).' warning(s):');
        $counts = collect($this->warnings)->countBy()->sortDesc();
        foreach ($counts->take(30) as $msg => $n) {
            $this->line("  [{$n}x] {$msg}");
        }
        if ($counts->count() > 30) {
            $this->line('  ... and '.($counts->count() - 30).' more unique warnings');
        }
        if ($this->option('verbose')) {
            foreach ($this->warnings as $w) {
                $this->line('  - '.$w);
            }
        }
    }
}
