(() => {
    'use strict';

    document.addEventListener('DOMContentLoaded', () => {

        if (typeof FSSearchConfig === 'undefined') return;

        const overlays = document.querySelectorAll('[data-fs-search-overlay]');
        const wrappers = document.querySelectorAll('[data-fs-search]');

        if (!overlays.length || !wrappers.length) return;

        const overlay = overlays[0]; // overlay único global
        const input   = overlay.querySelector('.fs-search-input');
        const results = overlay.querySelector('[data-fs-search-results]');
        const closeBtn = overlay.querySelector('.fs-search-close');

        if (!input || !results) return;

        let controller = null;
        let debounceTimer = null;

        /* =====================================
           UTILITIES
        ===================================== */

        const escapeHTML = (str = '') =>
            str.replace(/[&<>"']/g, m => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;'
            })[m]);

        /* =====================================
           OPEN / CLOSE
        ===================================== */

        const openOverlay = (wrapper) => {
            overlay.classList.add('is-active');
            overlay.setAttribute('aria-hidden', 'false');

            wrapper.querySelector('.fs-search-trigger')
                   ?.setAttribute('aria-expanded', 'true');

            document.body.classList.add('fs-search-open');

            setTimeout(() => input.focus(), 50);
        };

        const closeOverlay = () => {
            overlay.classList.remove('is-active');
            overlay.setAttribute('aria-hidden', 'true');

            wrappers.forEach(w =>
                w.querySelector('.fs-search-trigger')
                 ?.setAttribute('aria-expanded', 'false')
            );

            document.body.classList.remove('fs-search-open');

            input.value = '';
            results.innerHTML = '';
        };

        wrappers.forEach(wrapper => {

            const trigger = wrapper.querySelector('.fs-search-bar, .fs-search-trigger');

            trigger?.addEventListener('click', (e) => {
                e.preventDefault();
                openOverlay(wrapper);
            });
        });

        closeBtn?.addEventListener('click', closeOverlay);

        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) closeOverlay();
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && overlay.classList.contains('is-active')) {
                closeOverlay();
            }
        });

        /* =====================================
           SEARCH LOGIC
        ===================================== */

        const performSearch = async (query) => {

            query = query.trim();

            if (query.length < FSSearchConfig.minLength) {
                results.innerHTML = '';
                return;
            }

            if (controller) controller.abort();
            controller = new AbortController();

            results.innerHTML = `
                <div class="fs-search-loading">
                    Buscando...
                </div>
            `;

            try {

                const response = await fetch(
                    `${FSSearchConfig.restUrl}?q=${encodeURIComponent(query)}`,
                    { signal: controller.signal }
                );

                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }

                const data = await response.json();
                renderResults(data);

            } catch (error) {
                if (error.name !== 'AbortError') {
                    console.error('Search error:', error);
                }
            }
        };

        input.addEventListener('input', (e) => {

            clearTimeout(debounceTimer);

            debounceTimer = setTimeout(() => {
                performSearch(e.target.value);
            }, 250);
        });

        /* =====================================
           RENDER RESULTS
        ===================================== */

        const renderResults = (items) => {

            if (!Array.isArray(items) || !items.length) {
                results.innerHTML = `
                    <div class="fs-search-empty">
                        No se encontraron resultados
                    </div>
                `;
                return;
            }

            results.innerHTML = items.map(item => `
                <a href="${item.permalink}" class="fs-search-result-item">
                    ${
                        item.image
                        ? `<img src="${escapeHTML(item.image)}"
                                alt="${escapeHTML(item.name)}"
                                loading="lazy"
                                decoding="async">`
                        : ''
                    }
                    <div>
                        <div class="fs-search-result-title">
                            ${escapeHTML(item.name)}
                        </div>
                        ${
                            item.price
                            ? `<div class="fs-search-result-price">
                                ${Number(item.price).toFixed(2).replace('.', ',')} €
                               </div>`
                            : ''
                        }
                    </div>
                </a>
            `).join('');
        };

    });

})();
