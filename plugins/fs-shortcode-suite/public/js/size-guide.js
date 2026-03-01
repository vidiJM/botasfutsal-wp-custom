(() => {

  const playerButtons = document.querySelectorAll('.fs-player-btn');
  const footButtons   = document.querySelectorAll('.fs-foot-btn');
  const brandButtons  = document.querySelectorAll('.fs-brand-btn');

  const recommendationBox = document.getElementById('fs-player-recommendation');
  const finalResultBox    = document.getElementById('fs-final-result');

  const cmInput   = document.getElementById('fs-foot-cm');
  const calcBtn   = document.getElementById('fs-calc-btn');

  if (!playerButtons.length) return;

  let selectedPlayer = null;
  let selectedFoot   = null;
  let selectedBrand  = null;
  let selectedSize   = null;

  /* =========================================
     UTIL
  ========================================= */

  function activate(buttons, btn) {
    buttons.forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
  }

  function capitalize(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
  }

  /* =========================================
     SELECCIONES
  ========================================= */

  playerButtons.forEach(btn => {
    btn.addEventListener('click', () => {
      activate(playerButtons, btn);
      selectedPlayer = btn.dataset.player;
      updateRecommendation();
    });
  });

  footButtons.forEach(btn => {
    btn.addEventListener('click', () => {
      activate(footButtons, btn);
      selectedFoot = btn.dataset.foot;
      updateRecommendation();
    });
  });

  if (brandButtons.length) {
    brandButtons.forEach(btn => {
      btn.addEventListener('click', () => {
        activate(brandButtons, btn);
        selectedBrand = btn.dataset.brand;
      });
    });
  }

  /* =========================================
     CALCULO BASE EU
  ========================================= */

  function calculateEU(cm) {
    // Fórmula profesional aproximada
    return (cm * 1.5) + 1;
  }

  function adjustByBrand(eu) {

    if (!selectedBrand) return eu;

    const adjustments = {
      nike: -0.5,
      mizuno: -0.5,
      adidas: 0,
      puma: 0,
      joma: 0.5,
      kelme: 0.5
    };

    return eu + (adjustments[selectedBrand] || 0);
  }

  function adjustByFoot(eu) {

    if (!selectedFoot) return eu;

    if (selectedFoot === 'ancho') return eu + 0.5;
    if (selectedFoot === 'estrecho') return eu - 0.5;

    return eu;
  }

  /* =========================================
     CALCULO FINAL
  ========================================= */

  if (cmInput && calcBtn) {

    calcBtn.addEventListener('click', () => {

      const cm = parseFloat(cmInput.value);

      if (!cm || cm < 19 || cm > 30) return;

      let eu = calculateEU(cm);
      eu = adjustByBrand(eu);
      eu = adjustByFoot(eu);

      // Permitir medias tallas
      eu = Math.round(eu * 2) / 2;

      selectedSize = eu;

      const category = eu <= 35 ? 'Infantil' : 'Adulto';

      finalResultBox.style.display = 'block';
      finalResultBox.innerHTML = `
        ✅ <strong>Talla recomendada: EU ${eu}</strong><br>
        Categoría: ${category}<br>
        ${selectedBrand ? 'Marca: ' + capitalize(selectedBrand) + '<br>' : ''}
        ${selectedPlayer ? 'Perfil: ' + capitalize(selectedPlayer) + '<br>' : ''}
        ${selectedFoot ? 'Tipo de pie: ' + capitalize(selectedFoot) : ''}
      `;

      // Scroll suave en móvil
      finalResultBox.scrollIntoView({
        behavior: 'smooth',
        block: 'center'
      });

    });
  }

  /* =========================================
     RECOMENDACION TEXTO
  ========================================= */

  function updateRecommendation() {

    if (!recommendationBox) return;
    if (!selectedPlayer || !selectedFoot) return;

    let text = `<strong>Recomendación personalizada:</strong><br>`;

    if (selectedFoot === 'ancho')
      text += 'Busca hormas amplias (Joma, Kelme).<br>';

    if (selectedFoot === 'estrecho')
      text += 'Modelos estrechos (Nike, Mizuno).<br>';

    if (selectedFoot === 'normal')
      text += 'Ajuste estándar recomendado.<br>';

    if (selectedPlayer === 'explosivo')
      text += 'Ajuste ceñido para máxima precisión.';

    if (selectedPlayer === 'tecnico')
      text += 'Prioriza sensibilidad y toque de balón.';

    if (selectedPlayer === 'defensivo')
      text += 'Comodidad y estabilidad primero.';

    recommendationBox.innerHTML = text;
  }

})();