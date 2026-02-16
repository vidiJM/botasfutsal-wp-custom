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

    const normalize = (str) => str.trim().toLowerCase();
    const resolveHex = (colorName) => {
        const key = normalize(colorName);
        return COLOR_MAP[key] || '#CCCCCC';
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
        input.value = '';
        results.innerHTML = '';
        lastQuery = '';
        activeFilters = {};
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

    /* =============================
       ACCORDION
    ============================= */

    overlay.addEventListener('click',(e)=>{
        const header = e.target.closest('.fs-filter-header');
        if (!header) return;

        const section = header.closest('.fs-filter-section');
        if (!section) return;

        const isActive = section.classList.contains('active');

        overlay.querySelectorAll('.fs-filter-section')
            .forEach(s=>s.classList.remove('active'));

        if (!isActive) section.classList.add('active');
    });

    /* =============================
       FILTER CLICK
    ============================= */

    overlay.addEventListener('click',(e)=>{
        const button = e.target.closest('[data-filter]');
        if (!button) return;

        const key = button.dataset.filter;
        const val = button.dataset.value;
        if (!key || !val) return;

        if (activeFilters[key] === val){
            delete activeFilters[key];
        } else {
            activeFilters[key] = val;
        }

        performSearch(lastQuery);
    });

    /* =============================
       SEARCH
    ============================= */

    const buildQueryString = (query)=>{
        const params = new URLSearchParams();
        if (query) params.append('q',query);

        Object.entries(activeFilters).forEach(([k,v])=>{
            params.append(k,v);
        });

        return params.toString();
    };

    const loadFiltersOnly = async ()=>{
        try{
            const response = await fetch(FSSearchConfig.restUrl);
            const data = await response.json();
            renderDynamicFilters(data.filters || {});
        }catch(e){ console.error(e); }
    };

    const performSearch = async (query)=>{

        lastQuery = query.trim();

        if (!lastQuery && Object.keys(activeFilters).length === 0){
            results.innerHTML = '';
            return;
        }

        if (controller) controller.abort();
        controller = new AbortController();

        try{
            const response = await fetch(
                `${FSSearchConfig.restUrl}?${buildQueryString(lastQuery)}`,
                { signal: controller.signal }
            );
            const data = await response.json();
            renderResults(data.products || []);
            renderDynamicFilters(data.filters || {});
        }catch(e){
            if (e.name !== 'AbortError') console.error(e);
        }
    };

    input?.addEventListener('input',(e)=>{
        clearTimeout(debounceTimer);
        const value = e.target.value.trim();

        debounceTimer = setTimeout(()=>{
            if (!value){
                resetAll();
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
            
            /* =============================
               PRICE SLIDER PROFESIONAL
            ============================= */
            
            if (key === 'price') {
            
                const min = Number(filters.price_min ?? 0);
                const max = Number(filters.price_max ?? 0);
            
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
                            minInput.value = minVal;
                        } else {
                            maxVal = minVal;
                            maxInput.value = maxVal;
                        }
                    }
            
                    activeFilters.price_min = minVal;
                    activeFilters.price_max = maxVal;
            
                    minLabel.textContent = `${minVal}€`;
                    maxLabel.textContent = `${maxVal}€`;
            
                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(() => {
                        performSearch(lastQuery);
                    }, 200);
                };
            
                minInput.addEventListener('input', updateSlider);
                maxInput.addEventListener('input', updateSlider);
            
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

                const unique = new Map();

                values.forEach(slug => {

                    const normalizedSlug = normalize(slug);
                    const parts = normalizedSlug.split('-');
                    const hexParts = parts.map(resolveHex);
                    const hexKey = [...hexParts].sort().join('|');

                    if (!unique.has(hexKey)) {
                        unique.set(hexKey, {
                            slug: normalizedSlug,
                            hexParts
                        });
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

                    return `
                        <button type="button"
                            data-filter="color"
                            data-value="${entry.slug}"
                            title="${entry.slug.replace(/[-_]/g,' ')}"
                            class="fs-color-dot ${activeFilters[key] === entry.slug ? 'active' : ''}"
                            style="background:${background}">
                        </button>
                    `;
                }).join('');

                return;
            }

            /* =============================
               OTHER FILTERS
            ============================= */

            if (Array.isArray(values)) {

                container.innerHTML = values.map(val => `
                    <button type="button"
                        data-filter="${key}"
                        data-value="${val}"
                        class="${activeFilters[key] === val ? 'active' : ''}">
                        ${val}
                    </button>
                `).join('');

            } else {

                container.innerHTML = Object.entries(values).map(([slug, label]) => `
                    <button type="button"
                        data-filter="${key}"
                        data-value="${slug}"
                        class="${activeFilters[key] === slug ? 'active' : ''}">
                        ${label}
                    </button>
                `).join('');
            }

        });
    };

    /* =============================
       RESULTS - ADIDAS STYLE
    ============================= */
    
    const renderResults = (items) => {
    
        if (!items.length) {
            results.innerHTML = `
                <div class="fs-search-empty">
                    No hemos encontrado productos
                </div>
            `;
            return;
        }
    
        results.innerHTML = items.map(item => {
    
            const colorsCount = item.colors_count ?? 0;
            const priceFrom = item.price_from ?? item.price ?? '';
    
            return `
                <a href="${item.permalink}" class="fs-product-card">
    
                    <div class="fs-product-card__image">
                        ${
                            item.image
                                ? `<img src="${item.image}" alt="${item.name}" loading="lazy">`
                                : ''
                        }
                    </div>
    
                    <div class="fs-product-card__content">
    
                        ${item.brand 
                            ? `<div class="fs-product-card__brand">${item.brand}</div>` 
                            : ''
                        }
    
                        <h3 class="fs-product-card__title">
                            ${item.name}
                        </h3>
    
                        ${
                            priceFrom
                                ? `<div class="fs-product-card__price">
                                       Desde ${priceFrom} €
                                   </div>`
                                : ''
                        }
    
                        ${
                            colorsCount > 0
                                ? `<div class="fs-product-card__colors">
                                       ${colorsCount === 1 ? '1 color' : `${colorsCount} colores`}
                                   </div>`
                                : ''
                        }
    
                    </div>
                </a>
            `;
        }).join('');
    };


    });
})();
