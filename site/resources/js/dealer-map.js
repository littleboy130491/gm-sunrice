// Maps dealer — search, filter, hotspot (hover desktop / click mobile)

import landGeoJson from './data/land.json';
import markerIcon from 'leaflet/dist/images/marker-icon.png';
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png';
import markerShadow from 'leaflet/dist/images/marker-shadow.png';

document.addEventListener('DOMContentLoaded', function () {
    const mapEl = document.getElementById('dealer-map');
    if (!mapEl) return;

    L.Icon.Default.mergeOptions({
        iconRetinaUrl: markerIcon2x,
        iconUrl: markerIcon,
        shadowUrl: markerShadow,
    });

    const locations = window.dealerLocations || [];
    const categoryLabel = window.dealerCategoryLabels || {};
    const mapLabels = window.dealerMapLabels || {};
    const petaLabel = mapLabels.peta || 'Peta';
    const satelitLabel = mapLabels.satelit || 'Satelit';

    const customIcon = window.dealerMapIcon
        ? L.icon({
              iconUrl: window.dealerMapIcon,
              iconSize: [40, 40],
              iconAnchor: [20, 40],
              popupAnchor: [0, -38],
              className: 'dealer-map-marker',
          })
        : null;

    const isTabletDown = () => window.matchMedia('(max-width: 1023px)').matches;
    const canHover = () => window.matchMedia('(hover: hover) and (pointer: fine)').matches && !isTabletDown();

    const map = L.map('dealer-map', { zoomControl: false }).setView([-2.5, 118], 5);
    L.control.zoom({ position: 'bottomright' }).addTo(map);

    // Flat vector basemap: land drawn from local GeoJSON over a solid sea
    // colour. No raster tiles, so there is no terrain texture and no labels.
    const layerStreet = L.geoJSON(landGeoJson, {
        style: {
            fillColor: '#FBF8F3',
            fillOpacity: 1,
            color: '#E2E8EB',
            weight: 1,
        },
        interactive: false,
    });

    const layerSatellite = L.tileLayer(
        'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
        { attribution: '&copy; Esri' },
    );

    layerStreet.addTo(map);

    const LayerToggle = L.Control.extend({
        options: { position: isTabletDown() ? 'bottomleft' : 'topleft' },
        onAdd: function () {
            const container = L.DomUtil.create('div', 'leaflet-layer-toggle');
            container.innerHTML = `
                <button type="button" class="layer-btn active" data-layer="street">${petaLabel}</button>
                <button type="button" class="layer-btn" data-layer="satellite">${satelitLabel}</button>
            `;
            L.DomEvent.disableClickPropagation(container);
            container.querySelectorAll('.layer-btn').forEach((btn) => {
                btn.addEventListener('click', function () {
                    container.querySelectorAll('.layer-btn').forEach((b) => b.classList.remove('active'));
                    this.classList.add('active');
                    if (this.dataset.layer === 'street') {
                        map.removeLayer(layerSatellite);
                        map.addLayer(layerStreet);
                        mapEl.classList.remove('is-satellite');
                    } else {
                        map.removeLayer(layerStreet);
                        map.addLayer(layerSatellite);
                        mapEl.classList.add('is-satellite');
                    }
                });
            });
            return container;
        },
    });

    const layerToggle = new LayerToggle();
    layerToggle.addTo(map);

    let lastTabletDown = isTabletDown();
    window.addEventListener('resize', function () {
        const now = isTabletDown();
        if (now !== lastTabletDown) {
            lastTabletDown = now;
            layerToggle.setPosition(now ? 'bottomleft' : 'topleft');
            bindMarkerInteractions();
        }

        updatePopupWidths();
    });

    const iconWa = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="dealer-contact-icon"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 0C5.373 0 0 5.373 0 12c0 2.136.562 4.14 1.541 5.874L.057 23.886a.5.5 0 0 0 .606.61l6.188-1.458A11.945 11.945 0 0 0 12 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-1.886 0-3.65-.523-5.153-1.43l-.36-.214-3.733.879.941-3.618-.235-.374A9.953 9.953 0 0 1 2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/></svg>`;
    const iconPhone = `<img src="/assets/telepon-icon.svg" class="dealer-contact-icon" alt="" aria-hidden="true">`;

    const markers = [];
    const closeTimers = new WeakMap();

    function popupWidthOptions() {
        const maxWidth = Math.min(360, Math.max(220, mapEl.clientWidth - 48));

        return {
            maxWidth,
            minWidth: Math.min(250, maxWidth),
        };
    }

    function updatePopupWidths() {
        const widths = popupWidthOptions();

        markers.forEach(function ({ marker }) {
            const popup = marker.getPopup();
            if (!popup) return;

            Object.assign(popup.options, widths);
            if (popup.isOpen()) popup.update();
        });
    }

    function buildPopup(loc) {
        const label = categoryLabel[loc['dealer-category']] || loc['dealer-category'] || '';

        let waHref = '';
        if (loc.whatsapp_link) {
            waHref = loc.whatsapp_link;
        } else if (loc.whatsapp) {
            const waNumber = String(loc.whatsapp).replace(/\D/g, '').replace(/^0/, '62');
            waHref = 'https://wa.me/' + waNumber;
        }

        let contacts = '';
        if (waHref) {
            contacts += `<a href="${waHref}" target="_blank" rel="noopener">${iconWa} ${loc.whatsapp || ''}</a>`;
        }
        if (loc.phone) {
            const telHref = String(loc.phone).replace(/\D/g, '');
            contacts += `<a href="tel:${telHref}">${iconPhone} ${loc.phone}</a>`;
        }

        const mapsLink = loc.maps_url
            ? `<a href="${loc.maps_url}" target="_blank" rel="noopener" class="dealer-popup-gmaps">Temukan Lokasi di Peta</a>`
            : '';

        return `
            <div class="dealer-popup">
                <div class="popup-header">
                    <div class="dealer-popup-city">${loc.city || ''}</div>
                    ${label ? `<div class="dealer-popup-category">${label}</div>` : ''}
                </div>
                <div class="wrap-popup-dealer">
                    <div class="dealer-popup-company notranslate" translate="no">${loc.company || ''}</div>
                    <div class="dealer-popup-address notranslate" translate="no">${(loc.address || '').replace(/\n/g, '<br>')}</div>
                    ${contacts ? `<div class="dealer-popup-contacts">${contacts}</div>` : ''}
                    ${mapsLink}
                </div>
            </div>
        `;
    }

    function clearHoverBindings(marker) {
        marker.off('mouseover');
        marker.off('mouseout');
        marker.off('popupopen');
        marker.off('click');
    }

    function bindHoverPopup(marker) {
        clearHoverBindings(marker);

        marker.on('mouseover', function () {
            const pending = closeTimers.get(this);
            if (pending) {
                clearTimeout(pending);
                closeTimers.delete(this);
            }
            this.openPopup();
        });

        marker.on('mouseout', function () {
            const self = this;
            const timer = setTimeout(function () {
                const popupEl = self.getPopup()?.getElement();
                if (popupEl && popupEl.matches(':hover')) return;
                self.closePopup();
            }, 180);
            closeTimers.set(self, timer);
        });

        marker.on('popupopen', function () {
            const self = this;
            const el = this.getPopup()?.getElement();
            if (!el) return;

            const onEnter = function () {
                const pending = closeTimers.get(self);
                if (pending) {
                    clearTimeout(pending);
                    closeTimers.delete(self);
                }
            };
            const onLeave = function () {
                self.closePopup();
            };

            el.addEventListener('mouseenter', onEnter);
            el.addEventListener('mouseleave', onLeave);

            self.once('popupclose', function () {
                el.removeEventListener('mouseenter', onEnter);
                el.removeEventListener('mouseleave', onLeave);
            });
        });

        // Desktop > popup dibuka lewat hover
        marker.on('click', function (e) {
            L.DomEvent.stopPropagation(e);
            this.openPopup();
        });
    }

    function bindClickPopup(marker) {
        clearHoverBindings(marker);
        marker.on('click', function () {
            this.openPopup();
        });
    }

    function bindMarkerInteractions() {
        const hover = canHover();
        markers.forEach(function ({ marker }) {
            if (hover) {
                bindHoverPopup(marker);
            } else {
                bindClickPopup(marker);
            }
        });
    }

    locations.forEach(function (loc) {
        if (!loc.lat || !loc.lng) return;

        const marker = L.marker([loc.lat, loc.lng], customIcon ? { icon: customIcon } : {}).bindPopup(
            buildPopup(loc),
            {
                ...popupWidthOptions(),
                autoPan: true,
                closeButton: false,
                closeOnClick: true,
            },
        );

        marker.addTo(map);
        markers.push({ marker, loc });
    });

    bindMarkerInteractions();

    function fitVisibleMarkers(animate) {
        const visible = markers.filter(function ({ marker }) {
            return map.hasLayer(marker);
        });

        if (!visible.length) return;

        const bounds = L.latLngBounds(visible.map(function ({ marker }) {
            return marker.getLatLng();
        }));

        map.fitBounds(bounds, {
            padding: [48, 48],
            maxZoom: visible.length === 1 ? 12 : 8,
            animate: animate !== false,
        });
    }

    const categorySelect = document.getElementById('dealer-category-select');
    const searchInput = document.getElementById('dealer-search');
    const searchBtn = document.getElementById('dealer-search-btn');
    const emptyFeedback = document.getElementById('dealer-search-empty');
    const categoryButtons = Array.from(document.querySelectorAll('.dealer-cat-btn'));
    const categoryQueryParam = 'dealer_category';
    const availableCategories = new Set(
        categoryButtons.map(function (button) {
            return button.dataset.category;
        }),
    );
    const emptySearchLabel =
        mapLabels.emptySearch || 'Dealer tidak ditemukan. Coba kata kunci atau filter lain.';

    if (emptyFeedback && !emptyFeedback.textContent.trim()) {
        emptyFeedback.textContent = emptySearchLabel;
    }

    function setEmptyFeedback(visible) {
        if (!emptyFeedback) return;
        emptyFeedback.classList.toggle('hidden', !visible);
    }

    function setCategoryControls(category) {
        const selectedCategory = availableCategories.has(category) ? category : 'all';

        categoryButtons.forEach(function (button) {
            button.classList.toggle('active', button.dataset.category === selectedCategory);
        });

        if (categorySelect) {
            categorySelect.value = selectedCategory;
        }
    }

    function categoryFromUrl() {
        const category = new URL(window.location.href).searchParams.get(categoryQueryParam);

        return category && availableCategories.has(category) ? category : 'all';
    }

    function updateCategoryUrl(category) {
        const url = new URL(window.location.href);

        if (category === 'all') {
            url.searchParams.delete(categoryQueryParam);
        } else {
            url.searchParams.set(categoryQueryParam, category);
        }

        window.history.pushState(window.history.state, '', url);
    }

    categoryButtons.forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const wasActive = btn.classList.contains('active');
            const category = wasActive ? 'all' : btn.dataset.category || 'all';

            setCategoryControls(category);
            updateCategoryUrl(category);
            applyFilters(true);
        });
    });

    if (categorySelect) {
        categorySelect.addEventListener('change', function () {
            setCategoryControls(categorySelect.value);
            updateCategoryUrl(categorySelect.value);
            applyFilters(true);
        });
    }

    function runSearch() {
        applyFilters(true);
    }

    if (searchBtn) {
        searchBtn.addEventListener('click', function (e) {
            e.preventDefault();
            runSearch();
        });
    }

    if (searchInput) {
        searchInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                runSearch();
            }
        });

        let searchTimer;
        searchInput.addEventListener('input', function () {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(runSearch, 250);
        });
    }

    function haystack(loc) {
        return [loc.city, loc.company, loc.region, loc.address]
            .filter(Boolean)
            .join(' ')
            .toLowerCase();
    }

    function applyFilters(shouldFit, animate) {
        const activeCategory = isTabletDown()
            ? categorySelect?.value || 'all'
            : document.querySelector('.dealer-cat-btn.active')?.dataset.category ||
              categorySelect?.value ||
              'all';
        const searchQuery = (searchInput?.value || '').toLowerCase().trim();
        let visibleCount = 0;

        markers.forEach(function ({ marker, loc }) {
            const matchCategory = activeCategory === 'all' || loc['dealer-category'] === activeCategory;
            const matchSearch = !searchQuery || haystack(loc).includes(searchQuery);

            if (matchCategory && matchSearch) {
                visibleCount += 1;
                if (!map.hasLayer(marker)) marker.addTo(map);
            } else {
                marker.closePopup();
                if (map.hasLayer(marker)) marker.remove();
            }
        });

        const hasActiveFilter = Boolean(searchQuery) || activeCategory !== 'all';
        setEmptyFeedback(hasActiveFilter && visibleCount === 0);

        if (shouldFit && visibleCount > 0) {
            fitVisibleMarkers(animate !== false);
        }
    }

    setCategoryControls(categoryFromUrl());
    applyFilters(true, false);

    window.addEventListener('popstate', function () {
        setCategoryControls(categoryFromUrl());
        applyFilters(true);
    });
});
