# Migration Progress — Statamic → Sunrice CMS

Goal (from AGENTS.md): rebuild the GM Mobil company website (Statamic v6, flat-file) on
[littleboy130491/sunrice-cms](https://github.com/littleboy130491/sunrice-cms), migrating all
content (collections, taxonomies, globals, navigation, forms, assets, SEO) into the CMS,
then serving a bilingual site (`id` main + `en`) translated manually in the CMS — not via
GTranslate.

This file is the handoff document. **Update it every time work is committed** so another
session/agent can resume.

## Repo layout

| Path | What it is |
|---|---|
| `statamic/` | Git submodule → `littleboy130491/statamic-test-gm` @ `2df7e172` — **source of truth** for all migrated content. Do not edit. |
| `site/` | New Laravel 13 app — the **target site**. Requires `sunrice/cms` via VCS repo (`dev-main`). |
| `site/app/StatamicImport/` | The importer (PHP). Reads `../statamic` and writes Sunrice DB records. |
| `PROGRESS.md` | This file. |
| `FINDINGS.md` | Problems/decisions found during migration. |

## Run locally

```bash
cd site
cp .env.example .env          # sqlite is default (database/database.sqlite)
php artisan key:generate
php artisan migrate            # sunrice tables already migrated by sunrice:install
php -d upload_max_filesize=64M -d post_max_size=64M artisan statamic:import
                               # full re-import; -d flags needed: 111 files > 2 MB,
                               # upload_max_filesize is PHP_INI_PERDIR (ini_set can't change it)
php artisan serve              # frontend at /, admin at /cms
```

The importer lives in `site/app/Console/Commands/ImportStatamic.php`
(command `statamic:import`). Options:

- `--fresh` — wipe all Sunrice tables first (recommended when re-running).
- `--only=a,b` — run a subset of stages: `assets,structure,taxonomies,entries,globals,forms,menus,settings`
  (entries runs before globals because globals hold `entry::` links).
- `--skip-assets` — skip the ~1300-file asset upload (useful while iterating on content).
- `--fast` — skip image-size generation during upload (run `sunrice:regenerate-images` later).

Stages are re-runnable without `--fresh`: entries/wipe-and-recreate, taxonomies per-taxonomy,
menus replace items, structure/globals/forms/settings upsert, assets resume via
`storage/app/statamic-asset-map.json` (rel-path → asset id manifest).

## Source inventory (statamic-test-gm @ 2df7e172)

| Area | Count / handles |
|---|---|
| Collections | `pages` (13, structured/tree), `posts` (310, dated, route `/berita-dan-artikel/{slug}`, taxonomies `categories`,`social_media`), `products` (26, dated, route `/products/{slug}`, taxonomies `product_categories`,`industries`), `dealers` (42, mounted on `dealers` page ⇒ route `/dealer/{slug}`, taxonomy `dealer_categories`), `careers` (4, route `/karier/{slug}`, taxonomies `locations`,`tags`), `achievements` (4, taxonomy `years`), `pop_up` (config only, 0 entries) |
| Taxonomies | `categories` 3, `dealer_categories` 3, `industries` 5, `locations` 42, `product_categories` 5, `social_media` 3, `tags` 14, `years` 4 |
| Globals | 13 sets in `content/globals/default/` (settings, hero_banner_slider, hero_page_placeholder, primary_footer, page_label_not_found, page_preparation, *_label_information ×7) |
| Navigation | `nav_header`, `secondary_footer` (+ tree `primary_header_desktop` with no def file) |
| Forms | `contact`, `career_apply` |
| Fieldsets | `resources/fieldsets/blocks/*` (38 block fieldsets) + `common/*` (7 shared) |
| Blueprints | one per collection + 2 form blueprints |
| Assets | `public/assets/` — 633 files (~597 MB incl. `public/build`/`vendor`), `.meta/*.yaml` holds title/alt |
| Templates | ~80 Blade views incl. `blocks/*.blade.php` (Statamic block renderers) |
| Locales | single site, Indonesian; `en` added by us |

## Status

| Step | Status | Notes |
|---|---|---|
| `.gitmodules` + submodule pinned | Done | `statamic/` now resolves to statamic-test-gm@2df7e172 |
| `site/` scaffold + `sunrice/cms` | Done | `sunrice:install` run; `HasRoles` added to User; no admin user yet (create with `php artisan sunrice:install` interactive or tinker) |
| Importer: field type mapping | Done | Statamic→Sunrice map implemented in `FieldMapper` |
| Importer: fieldsets + blueprints | Done | Replicator sets → Sunrice fieldsets (`{set_key}` or `bp_{blueprint}_{set}` on collision); `import:` → `fieldset` field |
| Importer: bard → rich_text HTML | Done | `BardToHtml` converts ProseMirror JSON; `asset::assets::file` → `<img data-asset-id>` |
| Importer: assets | Done | 633/633 uploaded (real count — earlier 1288 was all of `public/`); resumable via manifest; 111 files needed `php -d upload_max_filesize=64M` |
| Importer: taxonomies/terms | Done | 8 taxonomies imported |
| Importer: globals | Done | 13 sets imported |
| Importer: forms | Done | `contact`, `career_apply` with real fields from `resources/blueprints/forms/` |
| Importer: entries | Done | 399 entries (pages 13 incl. tree, posts 310, products 26, dealers 42, careers 4); flexible blocks + links/terms/assets resolved; asset URLs stored relative |
| Importer: menus | Done | 3 menus with nested items |
| Importer: settings | Done | homepage entry = `beranda` page |
| E2E check (testing agent) | Done | all routes 200, /cms populated; **fixed**: Laravel 13 `serializable_classes=false` broke sunrice content cache (500s on warm cache) — see FINDINGS #15 |
| Templates ported | Done | all 99 statamic Blade views converted → `site/resources/views/`; block partials → `sunrice/blocks/*.blade.php`; full-route sweep: **370/370 URLs → 200** |
| Frontend parity check | In progress | routes all render; visual diff vs original statamic site not yet compared side-by-side |
| Translations `id`→`en` | Manual (editors) | Decision: translate in /cms, no API. Locales already `[id,en]`. Spot-check done: `/en/*` falls back to `id` content until an `en` translation is published (Ready); an `en` translation for `kontak` → `/en/contact` exists as a working example. Per-entry workflow: open entry in /cms → add `en` translation → translate fields → publish/Ready |
| Users | Not started | statamic `users/` not migrated yet — decide if needed |

## Decisions so far

- Target app lives in `site/` (composer VCS → `sunrice/cms:dev-main`, guzzle pinned to ^7 by `-W` resolution for `spatie/laravel-sitemap 8.0.0`).
- Statamic set keys become Sunrice fieldset handles (e.g. set `hero` → fieldset `hero`); a set's fields = its `import:`s + inline fields merged.
- Statamic replicator row `identifier` → Sunrice block `key`; `enabled: false` → `hidden: true`.
- Statamic entry `id:` (arbitrary string/uuid) → lookup table during import for `entry::` references and nav items.
- SEO: statamic `seo:` frontmatter → translation `seo` JSON; `@seo:title`/`@seo:featured_image` sentinels resolved to the field value.
- Pages keep flat `/{slug}` routes (Sunrice v1 has no nested page URLs — see FINDINGS).
- Templates live under `site/resources/views/`: pages at root level (`home.blade.php`,
  `{slug}.blade.php` by entry template), collection/taxonomy shows under
  `sunrice/{collection}/show.blade.php` + `sunrice/taxonomies/{taxonomy}/{index,show}.blade.php`,
  flexible blocks under `sunrice/blocks/{key}.blade.php` (`default.blade.php` = fallback).
  Site helpers (`app/helpers.php`): `gm_entry`, `gm_entries`, `gm_terms`, `gm_term`,
  `gm_field_label`, `gm_asset_url`, `gm_link_url`.
- Statamic `sections` → Sunrice `Items` of `Block` objects (`->type`, `->key`, `->get()`/
  `__get`). Repeater/group **rows hydrate as plain PHP arrays** → `$row['field']`.
  `Entry->get(handle)` for blueprint fields; `MenuNode` has `->label/->url/->children`
  but NO ArrayAccess; hydrated `link` fields are `['url','label','new_tab']` arrays;
  `Asset->url()` is a method. Views receive `$locale`, `$pageType`, `$entry`, `$term`,
  `$collection`, `$taxonomy`; inside components use
  `request()->attributes->get('sunrice.page')` (view data does not propagate).
- Forms render via `<x-sunrice::form handle="contact|career_apply">`; slot gets
  `$component->form->fields`, `$component->error/old/success`. Inputs must be named
  `data[handle]`. Published `vendor/sunrice/components/form.blade.php` drops the
  package's default submit button (templates bring their own).

## How to resume (for the next agent)

1. `git pull`, `git submodule update --init`, `cd site && composer install`.
2. Read this file + `FINDINGS.md`, check `git log` since the last "Status" edit.
3. Importer: `site/app/StatamicImport/` (Statamic parser, FieldMapper, BardToHtml, ValueConverter) + `site/app/Console/Commands/ImportStatamic.php`.
4. Full import: `php -d upload_max_filesize=64M -d post_max_size=64M artisan statamic:import --fresh`
   (`--skip-assets` while iterating on content; assets resume via manifest file anyway).
5. Template porting is done; remaining: visual parity review vs the original statamic
   site (spot-check pages, fix markup/CSS drift).
6. Then `id`→`en` translations (`sunrice:translate` needs driver + API key, or manual in /cms).
