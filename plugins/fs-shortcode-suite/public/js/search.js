(() => {
'use strict';

document.addEventListener('DOMContentLoaded', () => {

    if (typeof FSSearchConfig === 'undefined') return;

    const overlay  = document.querySelector('[data-fs-search-overlay]');
    const input    = overlay?.querySelector('.fs-search-input');
    const results  = overlay?.querySelector('[data-fs-search-results]');
    const pillsContainer = overlay?.querySelector('[data-fs-active-filters]');

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
    const cache = new Map();

    /* =============================
       COLOR MAP
    ============================= */

    const COLOR_MAP = {
        negro:'#000000',
        blanco:'#FFFFFF',
        blanco_coral:'#F2ECDF',
        rojo:'#FF0000',
        azul:'#0000FF',
        azul_marino:'#000080',
        azul_claro:'#90D5FF',
        azul_fucsia:'#6A00FF',
        azul_royal:'#4169E1',
        verde:'#008000',
        verde_fluor:'#39FF14',
        amarillo:'#FFFF00',
        amarillo_fluor:'#CCFF00',
        naranja:'#FFA500',
        gris:'#808080',
        gris_claro:'#CDCDCD',
        rosa:'#FFC0CB',
        morado:'#800080',
        turquesa:'#40E0D0',
        oro:'#FFD700',
        plata:'#C0C0C0',
        beige:'#F5F5DC',
        marron:'#8B4513',
        marrón:'#8B4513',
        cuero:'#AC7434',
        lima:'#99FF33',
        royal:'#4169E1',
        marino:'#000080',
        bordeaux:'#800000',
        neon:'#39FF14',
        fucsia:'#FF00FF',
        multicolor:'#999999'
    };

    const normalize = (str) => {
        if (typeof str !== "string") return "";
        return str.trim().toLowerCase();
    };

    const resolveHex = (colorName) => {
        const key = normalize(colorName);
        return COLOR_MAP[key] || '#CCCCCC';
    };

    const isTermObjectArray = (arr) => {
        return Array.isArray(arr) &&
            arr.length > 0 &&
            typeof arr[0] === "object" &&
            arr[0] !== null &&
            ("slug" in arr[0]);
    };

    const escapeHtml = (str) => {
        // Defensive: evita inyección si el endpoint algún día devuelve algo raro.
        if (str === null || str === undefined) return '';
        return String(str).replace(/[&<>"']/g, (m) => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        }[m]));
    };

    /* =============================
       ACTIVE PILLS
    ============================= */

    const humanizeKey = (key) => {
        // Labels base; puedes ajustarlos
        const map = {
            talla: 'Talla',
            superficie: 'Superficie',
            marca: 'Marca',
            genero: 'Género',
            color: 'Color',
            price_min: 'Precio mín.',
            price_max: 'Precio máx.'
        };
        return map[key] || key;
    };

    const renderActivePills = () => {
        if (!pillsContainer) return;

        const entries = Object.entries(activeFilters);
        if (!entries.length) {
            pillsContainer.innerHTML = '';
            return;
        }

        const pills = [];

        // Normal pills
        for (const [key, value] of entries) {
            if (key === 'price_min' || key === 'price_max') continue;

            pills.push(`
                <button type="button" class="fs-filter-pill" data-pill="${escapeHtml(key)}">
                    <span class="fs-filter-pill__label">${escapeHtml(humanizeKey(key))}:</span>
                    <span class="fs-filter-pill__value">${escapeHtml(value)}</span>
                    <span class="fs-pill-remove" aria-hidden="true">&times;</span>
                </button>
            `);
        }

        // Price pill (si están ambos)
        if (activeFilters.price_min !== undefined && activeFilters.price_max !== undefined) {
            pills.push(`
                <button type="button" class="fs-filter-pill" data-pill="price">
                    <span class="fs-filter-pill__label">${escapeHtml(humanizeKey('price_min'))}/${escapeHtml(humanizeKey('price_max'))}:</span>
                    <span class="fs-filter-pill__value">${escapeHtml(activeFilters.price_min)}€ - ${escapeHtml(activeFilters.price_max)}€</span>
                    <span class="fs-pill-remove" aria-hidden="true">&times;</span>
                </button>
            `);
        }

        // Clear all (opcional, pero muy útil)
        pills.push(`
            <button type="button" class="fs-filter-pill fs-filter-pill--clear" data-fs-clear-filters>
                Limpiar todo
            </button>
        `);

        pillsContainer.innerHTML = pills.join('');
    };

    /* =============================
       OPEN / CLOSE
    ============================= */

    const openOverlay = () => {
        overlay.classList.add('is-active');
        overlay.setAttribute('aria-hidden','false');
        document.body.classList.add('fs-search-open');
        loadFiltersOnly();
    };

    const closeOverlay = () => {
        overlay.classList.remove('is-active');
        overlay.setAttribute('aria-hidden','true');
        document.body.classList.remove('fs-search-open');
        resetAll();
    };

    const resetAll = () => {
        if (input) input.value = '';
        if (results) results.innerHTML = '';
        lastQuery = '';
        activeFilters = {};
        cache.clear();
        renderActivePills();
        loadFiltersOnly();
    };

    document.addEventListener('click',(e)=>{
        if (e.target.closest('.fs-search-trigger')){
            e.preventDefault();
            openOverlay();
        }
        if (e.target.closest('.fs-search-close') || e.target === overlay){
            closeOverlay();
        }
    });

    document.querySelectorAll('.fs-color-item').forEach(el => {

        const slug = el.dataset.color;
        if (!slug) return;
    
        const parts = slug.split(/[-_]/);
    
        if (parts.length === 1) {
    
            const color = COLOR_MAP[parts[0]] || '#ccc';
            el.style.background = color;
    
        } else {
    
            const color1 = COLOR_MAP[parts[0]] || '#ccc';
            const color2 = COLOR_MAP[parts[1]] || '#ccc';
    
            el.style.background =
                `linear-gradient(135deg, ${color1} 50%, ${color2} 50%)`;
        }
    });
    
    overlay.addEventListener('click', (e) => {

        /* =============================
           CLEAR ALL
        ============================= */
        const clearBtn = e.target.closest('[data-fs-clear-filters]');
        if (clearBtn) {
            activeFilters = {};
            performSearch(lastQuery);
            return;
        }
    
        /* =============================
           REMOVE PILL
        ============================= */
        const pill = e.target.closest('[data-pill]');
        if (pill) {
    
            const key = pill.dataset.pill;
    
            if (key === 'price') {
                delete activeFilters.price_min;
                delete activeFilters.price_max;
            } else {
                delete activeFilters[key];
            }
    
            performSearch(lastQuery);
            return;
        }
    
        /* =============================
           FILTER BUTTON CLICK
        ============================= */
        const button = e.target.closest('[data-filter]');
        if (button && !button.disabled) {
    
            const key = button.dataset.filter;
            const val = button.dataset.value;
    
            if (!key || !val) return;
    
            if (activeFilters[key] === val) {
                delete activeFilters[key];
            } else {
                activeFilters[key] = val;
            }
    
            performSearch(lastQuery);
            return;
        }
    
        /* =============================
           ACCORDION
        ============================= */
        const header = e.target.closest('.fs-filter-header');
        if (header) {
    
            const section = header.closest('.fs-filter-section');
            if (!section) return;
    
            const isActive = section.classList.contains('active');
    
            overlay.querySelectorAll('.fs-filter-section')
                .forEach(s => s.classList.remove('active'));
    
            if (!isActive) section.classList.add('active');
    
            return;
        }
    
    });

    /* =============================
       SEARCH
    ============================= */

    const buildQueryString = (query)=>{
        const params = new URLSearchParams();
        if (query) params.append('q',query);

        Object.entries(activeFilters).forEach(([k,v])=>{
            // Solo mandamos si tiene valor válido
            if (v === undefined || v === null || v === '') return;
            params.append(k, String(v));
        });

        return params.toString();
    };

    const loadFiltersOnly = async ()=>{
        try{
            overlay.classList.add('is-loading');
            const response = await fetch(FSSearchConfig.restUrl, { credentials: 'same-origin' });
            const data = await response.json();
            renderDynamicFilters(data.filters || {});
            renderActivePills();
        }catch(e){
            console.error(e);
        }finally{
            overlay.classList.remove('is-loading');
        }
    };

    const syncFiltersWithResponse = (filters = {}) => {
        // Elimina filtros activos que ya no existen en el nuevo set
        // (evita estados “fantasma” cuando encadenas filtros)
        for (const key of Object.keys(activeFilters)) {

            if (key === 'price_min' || key === 'price_max') continue;

            const values = filters[key];
            if (!values) {
                delete activeFilters[key];
                continue;
            }

            if (Array.isArray(values)) {
                const available = values.map(v => {
                    if (typeof v === 'object' && v !== null && 'slug' in v) return normalize(String(v.slug));
                    return normalize(String(v));
                });
                if (!available.includes(activeFilters[key])) {
                    delete activeFilters[key];
                }
            } else if (typeof values === 'object' && values !== null) {
                const available = Object.keys(values).map(s => normalize(String(s)));
                if (!available.includes(activeFilters[key])) {
                    delete activeFilters[key];
                }
            }
        }

        // Price: si backend devuelve min/max y están fuera, reseteamos a rango válido.
        if (filters.price_min !== undefined && filters.price_max !== undefined) {
            const min = Number(filters.price_min);
            const max = Number(filters.price_max);
            if (Number.isFinite(min) && Number.isFinite(max) && min < max) {
                const curMin = Number(activeFilters.price_min ?? min);
                const curMax = Number(activeFilters.price_max ?? max);
                if (curMin < min || curMin > max || curMax < min || curMax > max || curMin > curMax) {
                    activeFilters.price_min = min;
                    activeFilters.price_max = max;
                }
            }
        }
    };

    const performSearch = async (query)=>{

        lastQuery = (query || '').trim();

        // Si no hay query ni filtros -> limpia resultados pero mantiene filtros base.
        if (!lastQuery && Object.keys(activeFilters).length === 0){
            if (results) results.innerHTML = '';
            renderActivePills();
            loadFiltersOnly();
            return;
        }

        const qs = buildQueryString(lastQuery);

        // Cache hit
        if (cache.has(qs)) {
            const cached = cache.get(qs);
            renderResults(
                cached.products || [],
                cached.has_more || false
            );
            renderDynamicFilters(cached.filters || {});
            renderActivePills();
            return;
        }

        if (controller) controller.abort();
        controller = new AbortController();

        overlay.classList.add('is-loading');

        try{
            const response = await fetch(
                `${FSSearchConfig.restUrl}?${qs}`,
                {
                    signal: controller.signal,
                    credentials: 'same-origin'
                }
            );

            const data = await response.json();

            syncFiltersWithResponse(data.filters || {});
            cache.set(qs, data);

            renderResults(
                data.products || [],
                data.has_more || false
            );
            renderDynamicFilters(data.filters || {});
            renderActivePills();

        }catch(e){
            if (e.name !== 'AbortError') console.error(e);
        }finally{
            overlay.classList.remove('is-loading');
        }
    };

    input?.addEventListener('input',(e)=>{
        clearTimeout(debounceTimer);
        const value = (e.target.value || '').trim();

        debounceTimer = setTimeout(()=>{
            if (!value){
                // Si borra texto, no reseteamos filtros activos: solo limpiamos query
                lastQuery = '';
                performSearch('');
                return;
            }
            performSearch(value);
        },300);
    });

    /* =============================
       RENDER FILTERS
    ============================= */

    const renderDynamicFilters = (filters = {}) => {

        Object.entries(dynamicContainers).forEach(([key, container]) => {

            if (!container) return;

            /* =============================
               PRICE SLIDER PROFESIONAL
            ============================= */

            if (key === 'price') {

                // Permitimos dos formatos:
                // A) { price_min, price_max }
                // B) { price: { min, max } } (por si lo cambias)
                const min = Number(filters.price_min ?? filters.price?.min ?? 0);
                const max = Number(filters.price_max ?? filters.price?.max ?? 0);

                // Validación fuerte
                if (!Number.isFinite(min) || !Number.isFinite(max) || min >= max) {
                    container.innerHTML = '';
                    return;
                }

                const currentMin = Number(activeFilters.price_min ?? min);
                const currentMax = Number(activeFilters.price_max ?? max);

                container.innerHTML = `
                    <div class="fs-price-slider">
                        <div class="fs-slider-track"></div>

                        <input type="range"
                            class="fs-range fs-range-min"
                            min="${min}"
                            max="${max}"
                            step="1"
                            value="${currentMin}"
                            data-price="min">

                        <input type="range"
                            class="fs-range fs-range-max"
                            min="${min}"
                            max="${max}"
                            step="1"
                            value="${currentMax}"
                            data-price="max">

                        <div class="fs-price-values">
                            <span data-price-label="min">${currentMin}€</span>
                            <span> - </span>
                            <span data-price-label="max">${currentMax}€</span>
                        </div>
                    </div>
                `;

                const minInput = container.querySelector('[data-price="min"]');
                const maxInput = container.querySelector('[data-price="max"]');
                const minLabel = container.querySelector('[data-price-label="min"]');
                const maxLabel = container.querySelector('[data-price-label="max"]');

                const updateSlider = (e) => {

                    let minVal = Number(minInput.value);
                    let maxVal = Number(maxInput.value);

                    // Evitar cruce
                    if (minVal > maxVal) {
                        if (e.target === minInput) {
                            minVal = maxVal;
                            minInput.value = String(minVal);
                        } else {
                            maxVal = minVal;
                            maxInput.value = String(maxVal);
                        }
                    }

                    activeFilters.price_min = minVal;
                    activeFilters.price_max = maxVal;

                    minLabel.textContent = `${minVal}€`;
                    maxLabel.textContent = `${maxVal}€`;

                    // Pills live
                    renderActivePills();

                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(() => {
                        performSearch(lastQuery);
                    }, 200);
                };

                minInput.addEventListener('input', updateSlider, { passive: true });
                maxInput.addEventListener('input', updateSlider, { passive: true });

                return;
            }

            const values = filters[key];

            if (!values || (Array.isArray(values) && !values.length)) {
                container.innerHTML = '';
                return;
            }

            /* =============================
               COLORS
            ============================= */

            if (Array.isArray(values) && key === 'color') {

                // Puede venir como ["rojo", "azul"] o como [{slug,name,count}, ...]
                const colorTerms = isTermObjectArray(values)
                    ? values.map(v => ({ slug: normalize(v.slug), count: Number(v.count ?? 0) }))
                    : values.map(v => ({ slug: normalize(String(v)), count: 1 }));

                // Normalizamos + evitamos duplicados por combinaciones de color
                const unique = new Map();

                colorTerms.forEach(({ slug, count }) => {

                    const parts = slug.split('-');
                    const hexParts = parts.map(resolveHex);
                    const hexKey = [...hexParts].sort().join('|');

                    // guardamos el "mejor" (si hay count, mantener el mayor)
                    if (!unique.has(hexKey)) {
                        unique.set(hexKey, { slug, hexParts, count });
                    } else {
                        const existing = unique.get(hexKey);
                        if (count > (existing.count ?? 0)) unique.set(hexKey, { slug, hexParts, count });
                    }
                });

                const sorted = [...unique.values()]
                    .sort((a, b) => a.slug.localeCompare(b.slug));

                container.innerHTML = sorted.map(entry => {

                    let background;

                    if (entry.slug === 'multicolor') {
                        background = 'linear-gradient(45deg, red, orange, yellow, green, blue, purple)';
                    } else {
                        background = entry.hexParts.length === 1
                            ? entry.hexParts[0]
                            : `linear-gradient(45deg, ${entry.hexParts.join(',')})`;
                    }

                    // Si el backend devuelve count=0, deshabilitamos
                    const isAvailable = Number.isFinite(entry.count) ? entry.count > 0 : true;

                    return `
                        <button type="button"
                            data-filter="color"
                            data-value="${escapeHtml(entry.slug)}"
                            title="${escapeHtml(entry.slug.replace(/[-_]/g,' '))}"
                            class="fs-color-dot ${activeFilters[key] === entry.slug ? 'active' : ''} ${!isAvailable ? 'is-disabled' : ''}"
                            style="background:${background}"
                            ${!isAvailable ? 'disabled' : ''}>
                        </button>
                    `;
                }).join('');

                return;
            }

            /* =============================
               OTHER FILTERS
            ============================= */

            if (Array.isArray(values)) {

                // Arrays pueden venir como ["adidas", ...] o como [{slug,name,count}, ...]
                if (isTermObjectArray(values)) {
                    container.innerHTML = values.map(term => {
                        const slug = normalize(String(term.slug));
                        const label = term.name ?? term.slug;
                        const count = Number(term.count ?? 0);
                        const isAvailable = Number.isFinite(count) ? count > 0 : true;
                        const text = Number.isFinite(count) && count > 0 ? `${label} (${count})` : label;

                        return `
                            <button type="button"
                                data-filter="${escapeHtml(key)}"
                                data-value="${escapeHtml(slug)}"
                                class="${activeFilters[key] === slug ? 'active' : ''} ${!isAvailable ? 'is-disabled' : ''}"
                                ${!isAvailable ? 'disabled' : ''}>
                                ${escapeHtml(text)}
                            </button>
                        `;
                    }).join('');
                } else {
                    container.innerHTML = values.map(val => {
                        const v = normalize(String(val));
                        // aquí no tenemos count, asumimos disponible
                        return `
                            <button type="button"
                                data-filter="${escapeHtml(key)}"
                                data-value="${escapeHtml(v)}"
                                class="${activeFilters[key] === v ? 'active' : ''}">
                                ${escapeHtml(val)}
                            </button>
                        `;
                    }).join('');
                }

            } else {

                // Objeto mapa {slug: "Label"}
                container.innerHTML = Object.entries(values).map(([slug, label]) => {
                    const s = normalize(String(slug));
                    return `
                        <button type="button"
                            data-filter="${escapeHtml(key)}"
                            data-value="${escapeHtml(s)}"
                            class="${activeFilters[key] === s ? 'active' : ''}">
                            ${escapeHtml(label)}
                        </button>
                    `;
                }).join('');
            }

        });
    };

    /* =============================
       RESULTS - ADIDAS STYLE
    ============================= */
    const renderResults = (items, hasMore = false) => {
        
        if (!results) return;
    
        if (!items.length) {
            results.innerHTML = `
                <div class="fs-search-empty">
                    No hemos encontrado productos
                </div>
            `;
            return;
        }
    
        const cards = items.map(item => {
    
            const colorsCount = item.colors_count ?? 0;
            const priceFrom = item.price_from ?? item.price ?? '';
    
            const permalink = item.permalink ? String(item.permalink) : '#';
            const img = item.image ? String(item.image) : '';
            const name = item.name ? String(item.name) : '';
            const brand = item.brand ? String(item.brand) : '';
    
            return `
                <a href="${escapeHtml(permalink)}" class="fs-product-card">
    
                    <div class="fs-product-card__image">
                        ${
                            img
                                ? `<img src="${escapeHtml(img)}" alt="${escapeHtml(name)}" loading="lazy">`
                                : ''
                        }
                    </div>
    
                    <div class="fs-product-card__content">
    
                        ${brand
                            ? `<div class="fs-product-card__brand">${escapeHtml(brand)}</div>`
                            : ''
                        }
    
                        <h3 class="fs-product-card__title">
                            ${escapeHtml(name)}
                        </h3>
    
                        ${
                            priceFrom !== ''
                                ? `<div class="fs-product-card__price">
                                       Desde ${escapeHtml(priceFrom)} €
                                   </div>`
                                : ''
                        }
    
                        ${
                            colorsCount > 0
                                ? `<div class="fs-product-card__colors">
                                       ${colorsCount === 1 ? '1 color' : `${escapeHtml(colorsCount)} colores`}
                                   </div>`
                                : ''
                        }
    
                    </div>
                </a>
            `;
        }).join('');
    
        let moreButton = '';
    
        if (hasMore) {
    
            const baseUrl = 'https://botasfutsal.com/zapatillas-futbol-sala/';
    
            const params = new URLSearchParams();
    
            if (lastQuery) {
                params.append('q', lastQuery);
            }
    
            Object.entries(activeFilters).forEach(([key, value]) => {
                if (value !== undefined && value !== null && value !== '') {
                    params.append(key, value);
                }
            });
    
            const finalUrl = `${baseUrl}?${params.toString()}`;
    
            moreButton = `
                <div class="fs-search-more-wrapper">
                    <a href="${escapeHtml(finalUrl)}" class="fs-search-more">
                        Ver más resultados
                    </a>
                </div>
            `;
        }
    
        results.innerHTML = cards + moreButton;
    };

});
})();