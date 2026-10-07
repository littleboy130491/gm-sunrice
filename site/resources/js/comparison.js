// Produk comparison (AJAX)

const setupComparison = () => {
    const grid = document.querySelector('#comparison-grid');
    if (!grid) return;

    const endpoint = grid.dataset.endpoint;
    const selects = grid.querySelectorAll('.comparison-select');

    // Cache respons (id produk)
    const cache = new Map();

    const fetchProduct = async (id) => {
        if (cache.has(id)) return cache.get(id);

        const request = fetch(`${endpoint}/${id}/comparison`, {
            headers: { Accept: 'application/json' },
        }).then((res) => {
            if (!res.ok) throw new Error(`Request failed: ${res.status}`);
            return res.json();
        });

        cache.set(id, request);
        return request;
    };

    const placeholder = grid.dataset.emptyPlaceholder || '';
    const columnValues = new Map();
    const isEmpty = (value) => value === undefined || value === null || String(value).trim() === '';

    // Hide kolom kosong dari data-field
    const refreshRows = () => {
        grid.querySelectorAll('.comparison-row').forEach((row) => {
            const field = row.dataset.field;
            let allEmpty = true;

            columnValues.forEach((values) => {
                if (!isEmpty(values[field])) allEmpty = false;
            });

            row.classList.toggle('hidden', allEmpty);

            row.querySelectorAll('[data-comparison-cell]').forEach((cell) => {
                const col = cell.dataset.comparisonCell;
                const value = columnValues.get(col)?.[field];
                cell.textContent = isEmpty(value) ? placeholder : value;
            });
        });
    };

    // Render kolom produk
    const renderColumn = (col, data) => {
        const image = grid.querySelector(`[data-comparison-image="${col}"]`);
        if (image) {
            setColumnImage(image, data.image, data.title);
        }

        const values = {};
        (data.rows || []).forEach((row) => {
            values[row.field] = row.value;
        });
        columnValues.set(col, values);
        refreshRows();
    };

    // Gambar kolom > efek fade
    const setColumnImage = (image, url, alt) => {
        const wrap = image.closest('.comparison-image-wrap');

        image.classList.remove('opacity-100');
        image.classList.add('opacity-0');

        if (!url) {
            image.removeAttribute('src');
            wrap?.classList.remove('is-loading');
            return;
        }

        wrap?.classList.add('is-loading');

        const preload = new Image();
        preload.onload = () => {
            image.src = url;
            image.alt = alt || '';
            wrap?.classList.remove('is-loading');
            
            requestAnimationFrame(() => {
                image.classList.remove('opacity-0');
                image.classList.add('opacity-100');
            });
        };
        preload.onerror = () => {
            image.removeAttribute('src');
            wrap?.classList.remove('is-loading');
        };
        preload.src = url;
    };

    // Data produk dropdown > render kolom
    const loadColumn = async (select) => {
        const col = select.dataset.column;
        const id = select.value;
        if (!id) return;

        try {
            const data = await fetchProduct(id);
            renderColumn(col, data);
        } catch (error) {
            console.error('Comparison load failed:', error);
        }
    };

    selects.forEach((select) => {
        select.addEventListener('change', () => loadColumn(select));
        loadColumn(select);
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', setupComparison);
} else {
    setupComparison();
}
