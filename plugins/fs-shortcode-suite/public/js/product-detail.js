(() => {
  "use strict";

  /* =====================================================
     COLOR MAP
  ===================================================== */

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

  /* =====================================================
     HELPERS
  ===================================================== */

  const normalizeColorKey = (value) => {
    return String(value || '')
      .toLowerCase()
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .replace(/\s+/g, '-')
      .replace(/_/g, '-')
      .trim();
  };

  const resolveHex = (slugPart) => {
    return COLOR_MAP[slugPart] || '#e5e7eb';
  };

  const buildColorBackground = (slug) => {
    const normalized = normalizeColorKey(slug);
    const parts = normalized.split('-');
    const hexParts = parts.map(resolveHex);

    if (normalized === 'multicolor') {
      return 'linear-gradient(45deg, red, orange, yellow, green, blue, purple)';
    }

    if (hexParts.length === 1) {
      return hexParts[0];
    }

    return `linear-gradient(45deg, ${hexParts.join(',')})`;
  };

  const sortSizesEU = (sizes) => {
    const toNum = (v) => {
      const s = String(v ?? "").replace(",", ".").trim();
      const n = Number(s);
      return Number.isFinite(n) ? n : null;
    };

    return [...new Set(sizes)]
      .map((v) => ({ raw: String(v).trim(), num: toNum(v) }))
      .filter((x) => x.raw.length > 0)
      .sort((a, b) => {
        if (a.num === null && b.num === null) return a.raw.localeCompare(b.raw);
        if (a.num === null) return 1;
        if (b.num === null) return -1;
        return a.num - b.num;
      })
      .map((x) => x.raw);
  };

  const formatPrice = (value) => {
    const n = Number(value);
    if (!Number.isFinite(n)) return "";
    return new Intl.NumberFormat("es-ES", {
      style: "currency",
      currency: "EUR"
    }).format(n);
  };

  /* =====================================================
     MAIN INIT
  ===================================================== */

  const init = () => {

    const root = document.querySelector(".fs-product-detail");
    if (!root) return;

    const data = window.FS_PRODUCT_DATA;
    if (!data || !data.colors) return;

    const colorsContainer = root.querySelector(".fs-product-detail__colors");
    const sizesContainer = root.querySelector(".fs-product-detail__sizes");
    const priceContainer = root.querySelector(".fs-product-detail__price");
    const mainImage = root.querySelector(".fs-product-detail__main-image");
    const cta = root.querySelector(".fs-product-detail__cta");
    const thumbsContainer = root.querySelector(".fs-product-detail__thumbs");
    const prevBtn = root.querySelector(".fs-product-detail__nav--prev");
    const nextBtn = root.querySelector(".fs-product-detail__nav--next");

    if (!colorsContainer || !sizesContainer || !priceContainer || !mainImage) {
      return;
    }

    const colorKeys = Object.keys(data.colors);
    if (!colorKeys.length) return;

    const state = {
      activeColor: colorKeys[0],
      activeImageIndex: 0
    };

    /* ===========================
       RENDER THUMBS
    ============================ */

    const renderThumbs = (images) => {

      if (!thumbsContainer) return;

      thumbsContainer.innerHTML = "";

      images.forEach((src, idx) => {

        const btn = document.createElement("button");
        btn.type = "button";
        btn.className = "fs-product-detail__thumb";
        if (idx === state.activeImageIndex) {
          btn.classList.add("is-active");
        }

        const img = document.createElement("img");
        img.src = src;
        img.loading = "lazy";
        img.decoding = "async";

        btn.appendChild(img);

        btn.addEventListener("click", () => {
          state.activeImageIndex = idx;
          updateMainImage(images);
        });

        thumbsContainer.appendChild(btn);
      });
    };

    const updateMainImage = (images) => {

      if (!images.length) return;

      const src = images[state.activeImageIndex];
      mainImage.src = src;

      if (thumbsContainer) {
        thumbsContainer.querySelectorAll(".fs-product-detail__thumb")
          .forEach((el, index) => {
            el.classList.toggle("is-active", index === state.activeImageIndex);
          });
      }
    };

    const navigate = (direction) => {

      const images = data.colors[state.activeColor].images || [];
      if (!images.length) return;

      state.activeImageIndex += direction;

      if (state.activeImageIndex < 0) {
        state.activeImageIndex = images.length - 1;
      }

      if (state.activeImageIndex >= images.length) {
        state.activeImageIndex = 0;
      }

      updateMainImage(images);
    };

    if (prevBtn) prevBtn.addEventListener("click", () => navigate(-1));
    if (nextBtn) nextBtn.addEventListener("click", () => navigate(1));

    /* ===========================
       RENDER SIZES
    ============================ */

    const renderSizes = (sizes) => {

      const sorted = sortSizesEU(sizes || []);

      sizesContainer.innerHTML = sorted
        .map(size => `<button type="button" class="fs-product-detail__size">${size}</button>`)
        .join("");
    };

    /* ===========================
       PRICE + CTA
    ============================ */

    const renderPriceAndCta = (price, url) => {
      priceContainer.textContent = formatPrice(price);
      if (cta) cta.href = url || "#";
    };

    /* ===========================
       COLOR DOTS
    ============================ */

    const renderColorDots = () => {

      colorsContainer.innerHTML = "";

      colorKeys.forEach((key) => {

        const dot = document.createElement("button");
        dot.type = "button";
        dot.className = "fs-product-detail__dot";
        dot.dataset.color = key;
        dot.setAttribute("aria-label", `Color ${key}`);

        dot.style.background = buildColorBackground(key);

        if (key === state.activeColor) {
          dot.classList.add("is-active");
        }

        dot.addEventListener("click", () => {

          if (state.activeColor === key) return;

          state.activeColor = key;
          state.activeImageIndex = 0;

          colorsContainer.querySelectorAll(".fs-product-detail__dot")
            .forEach(el => el.classList.remove("is-active"));

          dot.classList.add("is-active");

          renderActive();
        });

        colorsContainer.appendChild(dot);
      });
    };

    /* ===========================
       RENDER ACTIVE COLOR
    ============================ */

    const renderActive = () => {

      const c = data.colors[state.activeColor];
      if (!c) return;

      const images = Array.isArray(c.images) ? c.images : [];

      updateMainImage(images);
      renderThumbs(images);
      renderSizes(c.sizes || []);
      renderPriceAndCta(c.price, c.shop_url);
    };

    renderColorDots();
    renderActive();
  };

  document.addEventListener("DOMContentLoaded", init);

})();
