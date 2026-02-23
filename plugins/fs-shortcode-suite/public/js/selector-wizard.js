(function () {

    if (typeof FS_WIZARD === 'undefined') {
        return;
    }

    let current = 0;

    const state = {
        gender: null,
        surface: null,
        closure: null,
        budget: null
    };

    const getSlides = () => document.querySelectorAll('.fs-wizard__slide');
    const getModal = () => document.querySelector('[data-fs-modal]');
    const getProgress = () => document.querySelector('[data-fs-progress]');
    const getBack = () => document.querySelector('[data-fs-back]');
    const getResultsContainer = () => document.querySelector('[data-fs-results]');

    const showSlide = (index) => {

        const slides = getSlides();
        const progress = getProgress();
        const backBtn = getBack();

        if (!slides.length) return;

        slides.forEach(s => s.classList.remove('active'));

        if (slides[index]) {
            slides[index].classList.add('active');
        }

        if (backBtn) {
            backBtn.hidden = index === 0;
        }

        if (progress) {
            const percent = (index / (slides.length - 1)) * 100;
            progress.style.width = percent + '%';
        }
    };

    const buildPayload = () => ({
        gender: state.gender,
        surface: state.surface,
        closure: state.closure,
        budget: state.budget
    });

    const formatPrice = (price) => {
        if (!price) return '';
        return parseFloat(price).toFixed(2).replace('.', ',') + '€';
    };

    const renderResults = (products) => {

        const container = getResultsContainer();
        if (!container) return;

        container.innerHTML = '';

        if (!Array.isArray(products) || products.length === 0) {
            container.innerHTML = `
                <p style="color:#6b7280; font-size:18px;">
                    No hemos encontrado resultados.
                </p>
            `;
            return;
        }

        const html = products.map(product => `
            <div class="fs-result-card">
                <a href="${product.link}">
                    ${product.image ? `<img src="${product.image}" alt="${product.title}" />` : ''}
                    <h3>${product.title}</h3>
                    ${product.price ? `<p>${formatPrice(product.price)}</p>` : ''}
                </a>
            </div>
        `).join('');

        container.innerHTML = html;
    };

    document.addEventListener('click', function (e) {

        // OPEN
        if (e.target.matches('[data-fs-open]')) {

            const modal = getModal();
            if (!modal) return;

            modal.removeAttribute('hidden');

            current = 0;

            state.gender = null;
            state.surface = null;
            state.closure = null;
            state.budget = null;

            showSlide(0);
        }

        // CLOSE
        if (e.target.matches('[data-fs-close]')) {

            const modal = getModal();
            if (!modal) return;

            modal.setAttribute('hidden', true);
        }

        // BACK
        if (e.target.matches('[data-fs-back]')) {
            if (current > 0) {
                current--;
                showSlide(current);
            }
        }

        const optionButton = e.target.closest('.fs-wizard__options button');
        if (!optionButton) return;

        const value = optionButton.dataset.value;

        switch (current) {
            case 0:
                state.gender = value;
                break;
            case 1:
                state.surface = value;
                break;
            case 2:
                state.closure = value;
                break;
            case 3:
                state.budget = value;
                break;
        }

        const slides = getSlides();
        const lastQuestionIndex = slides.length - 2;
        const resultsIndex = slides.length - 1;

        // Último paso antes de resultados
        if (current === lastQuestionIndex) {

            fetch(FS_WIZARD.rest_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': FS_WIZARD.nonce
                },
                body: JSON.stringify(buildPayload())
            })
            .then(res => {
                if (!res.ok) {
                    throw new Error('HTTP error');
                }
                return res.json();
            })
            .then(products => {

                renderResults(products);

                current = resultsIndex;
                showSlide(current);
            })
            .catch(() => {

                renderResults([]);

                current = resultsIndex;
                showSlide(current);
            });

        } else {

            current++;
            showSlide(current);
        }

    });

})();