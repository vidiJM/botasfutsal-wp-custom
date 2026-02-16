(() => {
    'use strict';

    document.addEventListener('DOMContentLoaded', () => {

        if (typeof FSSearchConfig === 'undefined') return;

        const overlay  = document.querySelector('[data-fs-search-overlay]');
        const input    = overlay?.querySelector('.fs-search-input');
        const results  = overlay?.querySelector('[data-fs-search-results]');

        if (!overlay) return;

        const dynamicContainers = {
            talla: overlay.querySelector('[data-dynamic-filter="talla"]'),
            superficie: overlay.querySelector('[data-dynamic-filter="superficie"]'),
            marca: overlay.querySelector('[data-dynamic-filter="marca"]'),
            genero: overlay.querySelector('[data-dynamic-filter="genero"]'),
            color: overlay.querySelector('[data-dynamic-filter="color"]'),
            price: overlay.querySelector('[data-dynamic-filter="price"]'),
        };

        let controller = null;
        let debounceTimer = null;
        let lastQuery = '';
        let activeFilters = {};

        /* =============================
           OPEN / CLOSE
        ============================= */

        const openOverlay = () => {
            overlay.classList.add('is-active');
            overlay.setAttribute('aria-hidden', 'false');
            document.body.classList.add('fs-search-open');

            // 🔥 Cargar SOLO filtros iniciales
            loadFiltersOnly();
        };

        const closeOverlay = () => {
            overlay.classList.remove('is-active');
            overlay.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('fs-search-open');

            input.value = '';
            results.innerHTML = '';
            lastQuery = '';
            activeFilters = {};
        };

        document.addEventListener('click', (e) => {
            if (e.target.closest('.fs-search-trigger')) {
                e.preventDefault();
                openOverlay();
            }
            if (e.target.closest('.fs-search-close') || e.target === overlay) {
                closeOverlay();
            }
        });

        /* =============================
           ACCORDION
        ============================= */

        overlay.addEventListener('click', (e) => {

            const header = e.target.closest('.fs-filter-header');
            if (!header) return;

            const section = header.closest('.fs-filter-section');
            if (!section) return;

            const isActive = section.classList.contains('active');

            overlay.querySelectorAll('.fs-filter-section')
                .forEach(s => s.classList.remove('active'));

            if (!isActive) section.classList.add('active');
        });

        /* =============================
           FILTER CLICK
        ============================= */

        overlay.addEventListener('click', (e) => {

            const button = e.target.closest('[data-filter]');
            if (!button) return;

            const key = button.dataset.filter;
            const val = button.dataset.value;

            if (!key || !val) return;

            if (activeFilters[key] === val) {
                delete activeFilters[key];
            } else {
                activeFilters[key] = val;
            }

            performSearch(lastQuery);
        });

        /* =============================
           SEARCH
        ============================= */

        const buildQueryString = (query) => {

            const params = new URLSearchParams();

            if (query) params.append('q', query);

            Object.entries(activeFilters).forEach(([k,v]) => {
                params.append(k, v);
            });

            return params.toString();
        };

        const loadFiltersOnly = async () => {

            try {

                const response = await fetch(FSSearchConfig.restUrl);

                const data = await response.json();

                renderDynamicFilters(data.filters || {});

            } catch (e) {
                console.error(e);
            }
        };

        const performSearch = async (query) => {

            lastQuery = query.trim();

            if (!lastQuery && Object.keys(activeFilters).length === 0) {
                results.innerHTML = '';
                return;
            }

            if (controller) controller.abort();
            controller = new AbortController();

            try {

                const response = await fetch(
                    `${FSSearchConfig.restUrl}?${buildQueryString(lastQuery)}`,
                    { signal: controller.signal }
                );

                const data = await response.json();

                renderResults(data.products || []);
                renderDynamicFilters(data.filters || {});

            } catch (e) {
                if (e.name !== 'AbortError') console.error(e);
            }
        };

        input?.addEventListener('input', (e) => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                performSearch(e.target.value);
            }, 300);
        });

        /* =============================
           RENDER FILTERS
        ============================= */

        const renderDynamicFilters = (filters = {}) => {

            Object.entries(dynamicContainers).forEach(([key, container]) => {

                if (!container) return;

                const values = filters[key];

                if (!values || (Array.isArray(values) && !values.length)) {
                    container.innerHTML = '';
                    return;
                }

                if (Array.isArray(values)) {

                    container.innerHTML = values.map(val => `
                        <button type="button"
                            data-filter="${key}"
                            data-value="${val}">
                            ${val}
                        </button>
                    `).join('');

                } else {

                    container.innerHTML = Object.entries(values).map(([slug,label]) => `
                        <button type="button"
                            data-filter="${key}"
                            data-value="${slug}">
                            ${label}
                        </button>
                    `).join('');
                }
            });

            if (filters.price_min !== undefined && filters.price_max !== undefined) {
                renderPrice(filters.price_min, filters.price_max);
            }
        };

        const renderPrice = (min,max) => {

            const container = dynamicContainers.price;
            if (!container) return;

            container.innerHTML = `
                <div class="fs-price-range">
                    <input type="number" data-price="min" min="${min}" max="${max}" placeholder="Min">
                    <input type="number" data-price="max" min="${min}" max="${max}" placeholder="Max">
                </div>
            `;
        };

        /* =============================
           RENDER RESULTS
        ============================= */

        const renderResults = (items) => {

            if (!items.length) {
                results.innerHTML = '<div class="fs-search-empty">Sin resultados</div>';
                return;
            }

            results.innerHTML = items.map(item => `
                <a href="${item.permalink}" class="fs-search-result-card">
                    <div class="fs-search-result-image">
                        ${item.image ? `<img src="${item.image}" loading="lazy">` : ''}
                    </div>
                    <div class="fs-search-result-content">
                        ${item.brand ? `<span>${item.brand}</span>` : ''}
                        <div>${item.name}</div>
                        ${item.price ? `<div>${item.price} €</div>` : ''}
                    </div>
                </a>
            `).join('');
        };

    });

})();
