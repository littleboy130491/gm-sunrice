# Findings — Statamic → Sunrice migration

Problems, gaps and judgement calls discovered during the migration. Add entries as they appear.

## Known gaps / design conflicts

1. **Nested page URLs.** Statamic `pages` uses `route: '{parent_uri}/{slug}'` with a
   structured tree; Sunrice v1 only supports flat `/{slug}` page URLs (per SPEC).
   Pages import flat; any nested children keep their own slug. Check
   `statamic/content/trees/collections/pages.yaml` — currently only `beranda` children?
   Verify when porting links.

2. **Mounted collections.** Statamic `dealers` has `mount: dealers` (URLs `/dealer/{slug}`).
   Imported with explicit route `/dealer/{slug}` since Sunrice has no mount concept.

3. **GTranslate removed.** The statamic header embeds a GTranslate script
   (`settings.embed_translate`, `.gtranslate_wrapper`). Not migrated — Sunrice serves real
   `en` translations instead. The old site's `en` URLs were query/cookie-based, so no
   redirects are needed.

4. **`hidden` / `seo_pro*` / `template` / `users*` / `revealer` / `section` field types**
   have no Sunrice equivalent and are skipped in blueprint conversion. Stored data under
   those handles is preserved in `data` anyway (Sunrice keeps unknown field data).

5. **Statamic `bard` → `rich_text`.** ProseMirror JSON is converted to HTML by
   `BardToHtml`. Node types observed in content: paragraph, heading, text
   (marks: link/bold/italic), image, bulletList, orderedList, listItem, hardBreak,
   table/tableRow/tableHeader/tableCell, blockquote, horizontalRule. If a new node type
   appears it renders as a comment placeholder — extend the converter.

6. **`code` / `markdown` fieldtypes** map to `textarea`/`rich_text`; `video` to `text`
   (URL string). Adjust templates if the statamic views expected embeds.

7. **Asset filenames.** Statamic references assets by filename relative to
   `public/assets/` (e.g. `featured_image: quiz-juli-pertn.jpg`, bard
   `src: asset::assets::file.png`). The importer builds a `relative-path → asset id` map;
   files not found are recorded in the import log (`--verbose`).

8. **Page `pop_up` collection** has a blueprint but no entries — imported empty.

9. **Form notifications.** Statamic forms define `email:` recipients; Sunrice form
   settings carry equivalent `emails`/`honeypot` config — verify field names in
   `docs/forms.md` when wiring notifications.

10. **Users not migrated.** `statamic/users/` contains CP users. Decide whether to
    recreate editors in the new CMS manually.

## Data quality issues found in source

11. **Dangling asset references (18 warnings).** These files are referenced in content
    but do not exist in `statamic/public/assets/`:
    `FD460TH-L_11zon.jpg` (10 bard refs), `Logo-aftersales-24h-red-01.png` (2),
    `WhatsApp-Image-2022-09-30-at-4.28.08-PM-1.jpeg` (2),
    `WhatsApp-Image-2022-07-15-at-2.51.12-PM.jpeg`, `cover-linkedin-1128x191_OVW-scaled-1.jpg`,
    `ebf39471-6c4c-d6ce-3712-83fb9f9ee813.png`, `SS-1680x1050-01-3-scaled-1.jpg`.
    Bard images render as `<img src="#">` — fix by uploading the files or editing content.

12. **Most pages have no explicit SEO data.** Statamic SEO Pro was generating titles/
    descriptions automatically; only posts/products carry `seo:` frontmatter. The
    `seo` JSON imports empty for pages — Sunrice should auto-fallback or SEO must be
    authored manually for key pages.

## Import mechanics notes

13. **Asset upload limit.** `php.ini` CLI default `upload_max_filesize=2M` blocks 111
    files; it is `PHP_INI_PERDIR` so `ini_set` can't change it — run the importer as
    `php -d upload_max_filesize=64M -d post_max_size=64M artisan statamic:import`.

14. **Asset resume manifest.** `site/storage/app/statamic-asset-map.json` maps
    statamic rel-path → asset id; deleted on `--fresh`. Lets `--only=entries` runs
    resolve `asset::`/`assets::` references without re-uploading.

15. **Laravel 13 `cache.serializable_classes=false` breaks sunrice.** The Laravel 13
    skeleton default refuses to unserialize objects from the database cache store —
    sunrice's `ContentCache` stores `RouteMatch`/`MenuNode`/Eloquent objects, so
    every route-matched URL 500s once the cache is warm. Fixed in
    `site/config/cache.php` (`serializable_classes` → env, default `true`).
    A proper fix belongs in sunrice-cms (cache scalars) — follow-up task there.

16. **Imported HTML no longer bakes APP_URL.** `Asset::url()` returns absolute
    `http://localhost:8000/storage/...`; link fields and bard images now store the
    relative `/storage/...` path so content survives a domain change. Bard images
    primarily render via `<img data-asset-id>` resolved server-side anyway.

## Template porting notes

17. **Hydration shapes differ per field type.** `sections` → `Items` of `Block`
    (objects); repeater/group rows → **plain PHP arrays** (`$row['field']`, never
    `->`); `link` → `['url','label','new_tab']` array; `select` options may be a list
    or keyed map; `Asset->url()` is a method, `->path` is a property (no `path()`).
    Helpers in `site/app/helpers.php` absorb most of this (`gm_asset_url`, `gm_link_url`).

18. **`$sunricePage` is not visible inside Blade components** — use
    `request()->attributes->get('sunrice.page')` (set by the frontend controller).
    Term pages get `$term`/`$taxonomy` but no `$entry` — shared partials must guard
    with `$entry ?? $term ?? null`.

19. **Taxonomy blueprints needed a glob fallback.** Statamic taxonomy yamls without a
    `blueprints:` key still have blueprint files under
    `resources/blueprints/taxonomies/{handle}/*.yaml`; importer now globs them
    (fixed `industries`/`tags` `blueprint_id` — rich text fields wouldn't hydrate
    to HTML without a blueprint). Term re-import also needs `forceDelete` +
    `TermTranslation` delete first (soft-deleted rows still hold the unique
    `(taxonomy_id, locale, slug)` key).

20. **Dealer `location` field** hydrates as an array (`$loc['latitude']`), not an
    object — templates must use array access for it.

## Frontend parity findings (statamic vs sunrice, side-by-side)

21. **Stale-id trap after re-running stages.** Stages that wipe/recreate rows
    (entries, terms) orphan ids stored elsewhere: menu `target_id`s, term ids in
    entry `data`, `homepage_entry_id`. Always re-run dependents in order:
    `entries,globals,menus,settings`. `globals()` also needs `ensureBlueprintIds()`
    before it runs standalone (NOT NULL crash otherwise).

22. **Sunrice `HtmlSanitizer` strips relative `img src`.** `allowRelativeLinks()`
    only covers link attrs — media attrs need `allowRelativeMedias()` (missing
    upstream). Imported bard images kept `data-asset-id` but lost `src` → all
    inline images broke. Fix sent upstream: littleboy130491/sunrice-cms PR #20
    (`task/fix-relative-media-src`); vendored copy patched locally meanwhile.
    Content re-synced via `entries,globals,menus,settings`.

23. **`entry↔term` pivot was never populated.** Importer now syncs
    `sunrice_entry_term` from converted `terms`-type fields (fixes KATEGORI
    sidebars, dealer grid filters, term archives).

24. **`dealers` has no single pages.** Statamic `mount: dealers` only —
    `/dealer/{slug}` 404s on production gmmobil.co.id, verified live. Moved to
    `NO_SINGLE` and removed the collection route.

25. **Product comparison API + misc template gaps.** `/api/products/{id}/comparison`
    ported to `site/routes/web.php` (AJAX spec table); post show date uses
    `Entry->published_at` (statamic `date` → published_at in importer); career
    show sidebar — `EntryQuery::where('id','!=',…)` filters *data fields* not
    columns, use limit+reject instead.
