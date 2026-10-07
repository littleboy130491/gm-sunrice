<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

/*
| Product comparison (AJAX)
|--------------------------------------------------------------------------
| Returns the specification data for a single product so the comparison
| section on the single-product page can swap columns without a reload.
*/
Route::get('/api/products/{id}/comparison', function (int $id) {
    $entry = \Sunrice\Models\Entry::find($id);

    if (! $entry || $entry->collection?->handle !== 'products') {
        return response()->json(['message' => 'Product not found'], 404);
    }

    $global = \Sunrice\Models\GlobalSet::query()->where('handle', 'product_label_information')->first();
    $globalValues = sunrice_global('product_label_information');

    $image = $entry->get('featured_image');

    // Sumber tunggal: checkboxes "spesification_info".
    // Options (key => label) dari blueprint, key tercentang dari value.
    $options = collect(
        collect($global?->blueprint?->fields ?? [])->firstWhere('handle', 'spesification_info')['config']['options'] ?? [],
    )->mapWithKeys(function ($opt, $k) {
        if (is_array($opt) && array_key_exists('value', $opt)) {
            return [$opt['value'] => $opt['label'] ?? $opt['value']];
        }
        if (is_array($opt) && array_key_exists('key', $opt)) {
            return [$opt['key'] => $opt['value'] ?? $opt['key']];
        }

        return [$k => $opt];
    });

    $selected = collect($globalValues?->spesification_info ?? [])->filter(fn ($v) => is_string($v));

    // Handle field yang benar-benar ada di produk (key tanpa field diabaikan).
    $productFieldHandles = collect($entry->activeBlueprint()?->fields ?? [])->pluck('handle');

    $specRows = $options
        ->filter(fn ($label, $key) => $selected->contains($key) && $productFieldHandles->contains($key))
        ->map(fn ($label, $key) => [
            'field' => $key,
            'label' => $label ?: $key,
            'value' => $entry->get($key),
        ])
        ->values();

    // Baris Model selalu paling atas, lalu baris dari spesification_info.
    $rows = collect([
        ['field' => 'model', 'label' => $globalValues?->model_labels ?: 'Model', 'value' => $entry->get('sku') ?: $entry['title']],
    ])->concat($specRows)->map(fn ($row) => [
        'field' => $row['field'],
        'label' => $row['label'],
        // Nilai kosong ditangani klien (placeholder / hide baris).
        'value' => is_scalar($row['value'] ?? null) ? (string) $row['value'] : '',
    ])->values();

    return response()->json([
        'id' => $entry->id,
        'title' => $entry['title'],
        'model' => $entry->get('sku') ?: $entry['title'],
        'image' => $image?->url(),
        'rows' => $rows,
    ]);
})->name('products.comparison');
