(function () {

    let current = 0;
    let state = {};

    const getSlides = () => document.querySelectorAll('.fs-wizard__slide');
    const getModal = () => document.querySelector('[data-fs-modal]');
    const getProgress = () => document.querySelector('[data-fs-progress]');
    const getBack = () => document.querySelector('[data-fs-back]');

    const showSlide = (index) => {

        const slides = getSlides();
        const progress = getProgress();
        const backBtn = getBack();

        if (!slides.length) return;

        slides.forEach(s => s.classList.remove('active'));
        slides[index].classList.add('active');

        if (backBtn) {
            backBtn.hidden = index === 0;
        }

        if (progress) {
            const percent = (index / (slides.length - 1)) * 100;
            progress.style.width = percent + '%';
        }
    };

    document.addEventListener('click', function (e) {

        // OPEN
        if (e.target.matches('[data-fs-open]')) {

            const modal = getModal();
            if (!modal) return;

            modal.removeAttribute('hidden');
            current = 0;
            state = {};
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

        // OPTION CLICK
        const optionButton = e.target.closest('.fs-wizard__options button');

        if (optionButton) {
        
            const slides = getSlides();
            state[current] = optionButton.dataset.value;
        
            const isLastQuestion = slides[current + 1] &&
                slides[current + 1].querySelector('[data-fs-results]');
        
            const lastQuestionIndex = slides.length - 2;
            const resultsIndex = slides.length - 1;
            
            if (current === lastQuestionIndex) {
            
                fetch('/wp-json/fs/v1/wizard', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(state)
                })
                .then(res => res.json())
                .then(products => {
            
                    const container = document.querySelector('[data-fs-results]');
                    if (!container) {
                        console.error('No se encuentra data-fs-results');
                        return;
                    }
            
                    console.log('Productos recibidos:', products);
            
                    container.innerHTML = '';
            
                    if (!Array.isArray(products) || products.length === 0) {
            
                        container.innerHTML = `
                            <p style="color:#6b7280; font-size:18px;">
                                No hemos encontrado resultados. Mostrando alternativas.
                            </p>
                        `;
            
                        current = resultsIndex;
                        showSlide(current);
                        return;
                    }
            
                    const html = products.map(product => `
                        <div class="fs-result-card">
                            <a href="${product.link}">
                                ${product.image ? `<img src="${product.image}" alt="${product.title}" />` : ''}
                                <h3>${product.title}</h3>
                                ${product.price ? `<p>${product.price}€</p>` : ''}
                            </a>
                        </div>
                    `).join('');
            
                    container.innerHTML = html;
            
                    current = resultsIndex;
                    showSlide(current);
                });
            
            } else {
            
                current++;
                showSlide(current);
            
            }

        }

    });

})();
