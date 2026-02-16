(() => {
    'use strict';

    if (typeof FSGridConfig === 'undefined') return;

    const gridWrapper = document.querySelector('.fs-grid-wrapper');
    const loadMoreBtn = document.querySelector('.fs-grid-load-more');

    if (!gridWrapper) return;

    const state = {
        page: FSGridConfig.page || 1,
        perPage: FSGridConfig.perPage || 12,
        filters: FSGridConfig.filters || {},
        hasMore: FSGridConfig.hasMore || false,
        loading: false
    };

    /* ==========================
       SAFE CLOSEST
    ========================== */

    const safeClosest = (target, selector) => {
        return target instanceof Element ? target.closest(selector) : null;
    };

    /* ==========================
       COLOR MAP
    ========================== */

    const colorMap = {
        negro: '#000000',
        blanco: '#ffffff',
        azul: '#1976d2',
        rojo: '#d32f2f',
        amarillo: '#fbc02d',
        verde: '#388e3c',
        gris: '#9e9e9e',
        multicolor: 'linear-gradient(45deg, red, yellow, blue)'
    };

    /* ==========================
       INITIALIZE CARDS
    ========================== */

    const initializeCards = (scope = document) => {

        scope.querySelectorAll('.fs-card').forEach(card => {

            if (card.dataset.initialized) return;

            try {
                card._data = JSON.parse(card.dataset.product || '{}');
            } catch {
                return;
            }

            card._activeColor = null;
            renderColorDots(card);
            card.dataset.initialized = 'true';
        });
    };

    /* ==========================
       COLOR DOTS
    ========================== */

    const renderColorDots = (card) => {

        const container = card.querySelector('.fs-card__colors');
        if (!container || !card._data?.colors) return;

        container.innerHTML = '';

        const colors = Object.keys(card._data.colors);
        if (!colors.length) return;

        colors.forEach((color, index) => {

            const dot = document.createElement('button');
            dot.type = 'button';
            dot.className = 'fs-color-dot';
            dot.dataset.color = color;
            dot.style.background =
                colorMap[color.toLowerCase()] || '#ccc';

            container.appendChild(dot);

            if (index === 0) {
                dot.classList.add('is-active');
                updateCard(card, color);
            }
        });
    };

    /* ==========================
       UPDATE CARD
    ========================== */

    const updateCard = (card, color) => {

        const colorData = card._data?.colors?.[color];
        if (!colorData) return;

        card._activeColor = color;

        const images = colorData.images || [];
        const sizes  = colorData.sizes || {};

        const imageEl  = card.querySelector('.fs-card__image');
        const priceEl  = card.querySelector('.fs-card__price');
        const sizesEl  = card.querySelector('.fs-card__sizes-count');

        if (imageEl && images[0]) {
            imageEl.src = images[0];
        }

        const prices = Object.values(sizes)
            .map(s => s.price)
            .filter(p => typeof p === 'number' && p > 0);

        const minPrice = prices.length ? Math.min(...prices) : 0;

        if (priceEl) {
            priceEl.textContent = minPrice
                ? minPrice.toFixed(2).replace('.', ',') + ' €'
                : '';
        }

        if (sizesEl) {
            const totalSizes = Object.keys(sizes).length;
            sizesEl.textContent = totalSizes
                ? `${totalSizes} ${totalSizes === 1 ? 'talla' : 'tallas'}`
                : '';
        }
    };

    /* ==========================
       EVENTS
    ========================== */

    document.addEventListener('click', (e) => {

        const dot = safeClosest(e.target, '.fs-color-dot');
        if (!dot) return;

        e.preventDefault();
        e.stopPropagation();

        const card = dot.closest('.fs-card');
        if (!card) return;

        const color = dot.dataset.color;
        if (!color) return;

        card.querySelectorAll('.fs-color-dot')
            .forEach(d => d.classList.remove('is-active'));

        dot.classList.add('is-active');
        updateCard(card, color);
    });

    document.addEventListener('mouseover', (e) => {

        const dot = safeClosest(e.target, '.fs-color-dot');
        if (!dot) return;

        const card = dot.closest('.fs-card');
        if (!card) return;

        const color = dot.dataset.color;
        if (!color) return;

        updateCard(card, color);
    });

    /* ==========================
       LOAD MORE
    ========================== */

    const loadMore = async () => {

        if (state.loading || !state.hasMore) return;

        state.loading = true;

        try {

            const nextPage = state.page + 1;

            const params = new URLSearchParams({
                ...state.filters,
                page: nextPage,
                per_page: state.perPage
            });

            const response = await fetch(
                `${FSGridConfig.restUrl}?${params.toString()}`
            );

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const data = await response.json();

            if (!data || !Array.isArray(data.items)) {
                throw new Error('Invalid JSON structure');
            }

            appendProducts(data.items);

            state.page = nextPage;
            state.hasMore = Boolean(data.has_more);

            if (!state.hasMore && loadMoreBtn) {
                loadMoreBtn.remove();
            }

        } catch (error) {
            console.error('Grid load error:', error);
        }

        state.loading = false;
    };

    if (loadMoreBtn) {
        loadMoreBtn.addEventListener('click', e => {
            e.preventDefault();
            loadMore();
        });
    }

    /* ==========================
       APPEND PRODUCTS
    ========================== */

    const appendProducts = (products) => {

        products.forEach(product => {

            if (!product?.permalink) return;

            const card = document.createElement('a');
            card.className = 'fs-card';
            card.href = product.permalink;
            card.dataset.product = JSON.stringify(product);

            card.innerHTML = `
                <div class="fs-card__image-wrapper">
                    <img class="fs-card__image" src="" alt="">
                </div>
                <div class="fs-card__content">
                    <h3 class="fs-card__title">${product.name || ''}</h3>
                    <div class="fs-card__sizes-count"></div>
                    <div class="fs-card__price"></div>
                    <div class="fs-card__colors"></div>
                </div>
            `;

            gridWrapper.appendChild(card);
        });

        initializeCards(gridWrapper);
    };

    initializeCards();

})();
