<?php
defined("ABSPATH") || exit();

require_once WP_PLUGIN_DIR . "/fs-shortcode-suite/src/Query/ProductQuery.php";

get_header();

/* ====================================================
1️⃣ Filtros GET
==================================================== */

$current_genero      = isset($_GET["genero"]) ? sanitize_text_field($_GET["genero"]) : "";
$current_superficie  = isset($_GET["superficie"]) ? sanitize_text_field($_GET["superficie"]) : "";
$current_marca       = isset($_GET["marca"]) ? sanitize_text_field($_GET["marca"]) : "";
$current_color       = isset($_GET["color"]) ? sanitize_text_field($_GET["color"]) : "";
$current_talla       = isset($_GET["talla"]) ? sanitize_text_field($_GET["talla"]) : "";
$current_precio_max  = isset($_GET["precio_max"]) ? (float) $_GET["precio_max"] : 0;

$paged     = get_query_var("paged") ? (int) get_query_var("paged") : 1;
$per_page  = 12;

/* ====================================================
2️⃣ Query principal (UNA sola ejecución)
==================================================== */

$result = \FS\ShortcodeSuite\Query\ProductQuery::get_products([
    "genero"     => $current_genero,
    "superficie" => $current_superficie,
    "marca"      => $current_marca,
    "color"      => $current_color,
    "talla"      => $current_talla,
    "precio_max" => $current_precio_max,
    "per_page"   => $per_page,
    "paged"      => $paged,
]);

$product_ids = $result["ids"] ?? [];
$total       = $result["total"] ?? 0;
$images      = $result["images"] ?? [];
$prices      = $result["prices"] ?? [];
$available   = $result["facets"] ?? [];

/* ====================================================
3️⃣ Datos filtros estáticos
==================================================== */

$generos = [
    "hombre"   => "Hombre",
    "mujer"    => "Mujer",
    "unisex"   => "Unisex",
    "infantil" => "Infantil",
];

$superficies = [
    (object)["slug"=>"indoor","name"=>"Indoor"],
    (object)["slug"=>"mixta","name"=>"Mixta"],
    (object)["slug"=>"outdoor","name"=>"Outdoor"],
    (object)["slug"=>"turf","name"=>"Turf"],
];

$brands = get_terms([
    "taxonomy"=>"fs_marca",
    "hide_empty"=>false,
]);

$tallas = get_terms([
    "taxonomy"=>"fs_talla_eu",
    "hide_empty"=>false,
]);

function fs_render_active_pill($param, $label) {
    ?>
    <div class="fs-active-single">
        <a href="<?php echo esc_url(remove_query_arg($param)); ?>"
           class="fs-chip">
            <?php echo esc_html($label); ?>
            <span aria-hidden="true">×</span>
        </a>
    </div>
    <?php
}
?>

<style>
/* ================================
   BASE LAYOUT
================================ */

.fs-container{
  max-width:1400px;
  margin:0 auto;
  padding:0 32px;
}

.fs-archive{
  color:#111;
}

.fs-archive *{
  box-sizing:border-box;
}

.fs-archive button{
  all:unset;
  cursor:pointer;
}

/* ================================
   LAYOUT GRID
================================ */

.fs-archive__layout{
  display:grid;
  grid-template-columns:260px 1fr;
  gap:60px;
}
.fs-archive__sidebar form{
    border-right: 1px solid #84cc16!important;
}
@media(max-width:1024px){
  .fs-archive__layout{
    display:block;
  }
  .fs-archive__sidebar{
    font-size:12px;
    display:none;
    border-right: 1px solid #84cc16!important;
    margin-top: 1rem;
  }
}

.fs-grid{
  display:grid;
  grid-template-columns:repeat(2,1fr);
  gap:2rem 2rem;
}

@media(min-width:1024px){
  .fs-grid{
    grid-template-columns:repeat(4,1fr);
    gap:2rem 2rem;
  }
}

/* ================================
   PRODUCT CARD
================================ */

.fs-card{
  text-decoration:none;
  color:#111;
}

.fs-card__image {
  background:#f6f6f6;
  padding:5px;
  aspect-ratio:5/8;
  display:flex;
  align-items:center;
  justify-content:center;
  border-radius:4px;
}

.fs-card__image img {
  max-width:100%;
  height:auto;
  transition:.3s;
}

.fs-card:hover .fs-card__image img {
  transform:scale(1.05);
}

.fs-card__body {
    color: #000000!important;
    margin-top:0.5rem;
}

.fs-card__body:hover{
    color: #84cc16!important;
  margin-top:18px;
}

.fs-card__price{
  font-size:13px;
  font-weight:600;
  margin-bottom:0.4rem;
}

.fs-card__title{
  font-size:13px;
  font-weight:500;
  letter-spacing:.3px;
}

/* ================================
   FILTERS
================================ */

.fs-filter{
  border-bottom:1px solid #84cc16;
  width:90%;
  padding:10px 0;
}

.fs-filter__header{
  display:flex;
  justify-content:space-between;
  font-size:14px;
  font-weight:600;
}

.fs-filter__body{
    font-weight: 600;
    display: none;
    margin-top:1rem;
}

.fs-filter.is-open .fs-filter__body {
  display: flex;        /* o block si no necesitas flex */
  flex-wrap: wrap;      /* si quieres layout horizontal */
  gap: 12px;            /* opcional */
}

.fs-filter__option{
  display:block;
  font-size:13px;
  margin-bottom:6px;
}

.fs-filter__submit{
  margin-top:20px;
  background:#111;
  color:#fff;
  padding:10px 16px;
  font-size:13px;
}

/* ================================
   COLOR SWATCHES (FIXED)
================================ */

.fs-color-swatches{
  display:flex;
  flex-wrap:wrap;
  gap:10px;
  padding:6px 0;
}

.fs-color-choice{
  display:inline-flex;
  align-items:center;
  cursor:pointer;
}

.fs-color-input{
  position:absolute;
  opacity:0;
  pointer-events:none;
}

.fs-color-swatch{
  width:25px;
  height:25px;
  border-radius:50%;
  border:2px solid #e5e5e5;
  background-color:var(--swatch-bg, #e5e7eb); /* fallback real */
  background: var(--swatch-bg, #e5e7eb);
  position:relative;
  transition:.15s ease;
}

/* Overlay mucho más sutil */
.fs-color-swatch::after{
  content:"";
  position:absolute;
  inset:0;
  border-radius:50%;
  background:linear-gradient(
    135deg,
    rgba(255,255,255,.35) 0%,
    rgba(255,255,255,.15) 35%,
    rgba(0,0,0,.08) 100%
  );
  pointer-events:none;
}

.fs-color-choice:hover .fs-color-swatch{
  transform:scale(1.08);
  border-color:#111;
}

.fs-color-swatch.is-active{
  border-color:#000;
  box-shadow:0 0 0 2px #000 inset;
}

/* ================================
   DISABLED STATES
================================ */

.fs-filter__option.is-disabled,
.fs-color-choice.is-disabled{
  opacity:.35;
  pointer-events:none;
  filter:grayscale(1);
}

/* ================================
   PRICE
================================ */

.fs-price__input{
  width:100%;
  padding:10px 12px;
  border:1px solid #e5e5e5;
  font-size:13px;
}

.fs-price__hint{
  margin-top:10px;
  font-size:12px;
  opacity:.75;
}

.fs-price__warn{
  margin-top:10px;
  font-size:12px;
  color:#b91c1c;
}

/* ================================
   ACTIVE CHIPS
================================ */

.fs-active-filters{
  display:flex;
  flex-wrap:wrap;
  gap:10px;
  margin:20px 0 10px;
}

.fs-chip{
  display:inline-flex;
  align-items:center;
  gap:6px;
  padding:6px 12px;
  border-radius:999px;
  background:#f3f4f6;
  font-size:12px;
  text-decoration:none;
  color:#111;
  border:1px solid #e5e7eb;
  transition:.2s;
}

.fs-chip:hover{
  background:#111;
  color:#fff;
}

.fs-chip--clear{
  background:#000;
  color:#fff;
}
.fs-filter__header{
  position:relative;
  padding-right:20px;
}

.fs-filter__header::after{
  content:"+";
  position:absolute;
  right:0;
  top:50%;
  transform:translateY(-50%);
  font-weight:600;
  transition:.2s;
}

.fs-filter.is-open .fs-filter__header::after{
  content:"–";
}
</style>

<section class="fs-archive">

    <div class="fs-container">
        <h1>Zapatillas de Fútbol Sala</h1>
        <p>Busca la zapatilla que deseas.</p>
    </div>

    <div class="fs-archive__layout fs-container">

        <aside class="fs-archive__sidebar">
            <p><?php echo esc_html($total); ?> productos</p>

            <form method="get">

                <!-- =========================
                     GÉNERO
                ========================== -->
                <div class="fs-filter <?php echo $current_genero ? 'is-active' : ''; ?>">

                    <button type="button" class="fs-filter__header">Género</button>

                    <?php if ($current_genero): ?>

                        <input type="hidden"
                               name="genero"
                               value="<?php echo esc_attr($current_genero); ?>">

                        <?php
                        $label = $generos[$current_genero] ?? $current_genero;
                        fs_render_active_pill('genero', $label);
                        ?>

                    <?php else: ?>

                        <div class="fs-filter__body">
                            <?php foreach ($generos as $slug => $label): ?>

                                <?php
                                if (!empty($available['genero']) &&
                                    !in_array($slug, $available['genero'])) {
                                    continue;
                                }
                                ?>

                                <label class="fs-filter__option">
                                    <input type="radio"
                                           name="genero"
                                           value="<?php echo esc_attr($slug); ?>">
                                    <?php echo esc_html($label); ?>
                                </label>

                            <?php endforeach; ?>
                        </div>

                    <?php endif; ?>

                </div>

                <!-- =========================
                     SUPERFICIE
                ========================== -->
                <div class="fs-filter <?php echo $current_superficie ? 'is-active' : ''; ?>">

                    <button type="button" class="fs-filter__header">Superficies</button>

                    <?php if ($current_superficie): ?>

                        <input type="hidden"
                               name="superficie"
                               value="<?php echo esc_attr($current_superficie); ?>">

                        <?php
                        $term = get_term_by('slug', $current_superficie, 'fs_superficie');
                        $label = $term ? $term->name : $current_superficie;
                        fs_render_active_pill('superficie', $label);
                        ?>

                    <?php else: ?>

                        <div class="fs-filter__body">
                            <?php foreach ($superficies as $term): ?>

                                <?php
                                if (!empty($available['superficie']) &&
                                    !in_array($term->slug, $available['superficie'])) {
                                    continue;
                                }
                                ?>

                                <label class="fs-filter__option">
                                    <input type="radio"
                                           name="superficie"
                                           value="<?php echo esc_attr($term->slug); ?>">
                                    <?php echo esc_html($term->name); ?>
                                </label>

                            <?php endforeach; ?>
                        </div>

                    <?php endif; ?>

                </div>

                <!-- =========================
                     MARCA
                ========================== -->
                <div class="fs-filter <?php echo $current_marca ? 'is-active' : ''; ?>">

                    <button type="button" class="fs-filter__header">Marcas</button>

                    <?php if ($current_marca): ?>

                        <input type="hidden"
                               name="marca"
                               value="<?php echo esc_attr($current_marca); ?>">

                        <?php
                        $brand_label = $current_marca;

                        foreach ($brands as $b) {
                            if ($b->slug === $current_marca) {
                                $brand_label = ucwords(
                                    mb_strtolower(str_replace('-', ' ', $b->name),'UTF-8')
                                );
                                break;
                            }
                        }

                        fs_render_active_pill('marca', $brand_label);
                        ?>

                    <?php else: ?>

                        <div class="fs-filter__body">
                            <?php foreach ($brands as $brand): ?>

                                <?php
                                if (!empty($available['marca']) &&
                                    !in_array($brand->slug, $available['marca'])) {
                                    continue;
                                }
                                ?>

                                <label class="fs-filter__option">
                                    <input type="radio"
                                           name="marca"
                                           value="<?php echo esc_attr($brand->slug); ?>">
                                    <?php echo esc_html($brand->name); ?>
                                </label>

                            <?php endforeach; ?>
                        </div>

                    <?php endif; ?>

                </div>

                <!-- =========================
                     COLOR
                ========================== -->
                <div class="fs-filter <?php echo $current_color ? 'is-active' : ''; ?>">

                    <button type="button" class="fs-filter__header">Colores</button>

                    <?php if ($current_color): ?>

                        <input type="hidden"
                               name="color"
                               value="<?php echo esc_attr($current_color); ?>">

                        <?php
                        $term = get_term_by('slug', $current_color, 'fs_color');
                        $label = $term ? $term->name : $current_color;
                        fs_render_active_pill('color', $label);
                        ?>

                    <?php else: ?>

                        <div class="fs-filter__body">

                            <?php
                            $colores = get_terms([
                                'taxonomy'   => 'fs_color',
                                'hide_empty' => false,
                                'orderby'    => 'name',
                                'order'      => 'ASC',
                            ]);
                            ?>

                            <?php if (!empty($colores) && !is_wp_error($colores)) : ?>

                                <div class="fs-color-swatches">

                                    <?php foreach ($colores as $term): ?>

                                        <?php
                                        if (!empty($available['color']) &&
                                            !in_array($term->slug, $available['color'])) {
                                            continue;
                                        }
                                        ?>

                                        <label class="fs-color-choice">
                                            <input type="radio"
                                                   name="color"
                                                   class="fs-color-input"
                                                   value="<?php echo esc_attr($term->slug); ?>">
                                            <span class="fs-color-swatch"></span>
                                        </label>

                                    <?php endforeach; ?>

                                </div>

                            <?php endif; ?>

                        </div>

                    <?php endif; ?>

                </div>

                <!-- =========================
                     TALLA
                ========================== -->
                <div class="fs-filter <?php echo $current_talla ? 'is-active' : ''; ?>">

                    <button type="button" class="fs-filter__header">Tallas (EU)</button>

                    <?php if ($current_talla): ?>

                        <input type="hidden"
                               name="talla"
                               value="<?php echo esc_attr($current_talla); ?>">

                        <?php
                        $term = get_term_by('slug', $current_talla, 'fs_talla_eu');
                        $label = $term ? $term->name : $current_talla;
                        fs_render_active_pill('talla', $label);
                        ?>

                    <?php else: ?>

                        <div class="fs-filter__body">
                            <?php foreach ($tallas as $term): ?>

                                <?php
                                if (!empty($available['talla']) &&
                                    !in_array($term->slug, $available['talla'])) {
                                    continue;
                                }
                                ?>

                                <label class="fs-filter__option">
                                    <input type="radio"
                                           name="talla"
                                           value="<?php echo esc_attr($term->slug); ?>">
                                    <?php echo esc_html($term->name); ?>
                                </label>

                            <?php endforeach; ?>
                        </div>

                    <?php endif; ?>

                </div>

                <!-- =========================
                     PRECIO
                ========================== -->
                <div class="fs-filter <?php echo $current_precio_max > 0 ? 'is-active' : ''; ?>">

                    <button type="button" class="fs-filter__header">Precio máximo (€)</button>

                    <?php if ($current_precio_max > 0): ?>

                        <input type="hidden"
                               name="precio_max"
                               value="<?php echo esc_attr($current_precio_max); ?>">

                        <?php
                        $label = 'Hasta ' . (int)$current_precio_max . '€';
                        fs_render_active_pill('precio_max', $label);
                        ?>

                    <?php else: ?>

                        <div class="fs-filter__body">

                            <?php
                            $max_price_available = 0;

                            if (!empty($prices)) {
                                $max_price_available = (int) ceil(max(array_map('floatval', $prices)));
                            }
                            ?>

                            <input
                                type="number"
                                name="precio_max"
                                min="0"
                                max="<?php echo esc_attr($max_price_available); ?>"
                                step="1"
                                value=""
                                class="fs-price__input"
                            >

                            <?php if ($max_price_available > 0): ?>
                                <div class="fs-price__hint">
                                    Máximo disponible:
                                    <strong><?php echo esc_html($max_price_available); ?>€</strong>
                                </div>
                            <?php endif; ?>

                        </div>

                    <?php endif; ?>

                </div>

            </form>
        </aside>

        <main>

            <?php if (!empty($product_ids)): ?>

                <div class="fs-grid">

                    <?php foreach ($product_ids as $product_id):

                        $title = get_the_title($product_id);
                        $link  = get_permalink($product_id);
                        $image = $images[$product_id] ?? '';
                        $price = $prices[$product_id] ?? '';
                    ?>

                        <article class="fs-card">
                            <a href="<?php echo esc_url($link); ?>">
                                <div class="fs-card__image">
                                    <?php if ($image): ?>
                                        <img src="<?php echo esc_url($image); ?>"
                                             alt="<?php echo esc_attr($title); ?>">
                                    <?php endif; ?>
                                </div>

                                <div class="fs-card__body">
                                    <?php if ($price): ?>
                                        <div class="fs-card__price">
                                            € <?php echo number_format((float)$price, 0, ",", "."); ?>
                                        </div>
                                    <?php endif; ?>

                                    <div class="fs-card__title">
                                        <?php echo esc_html($title); ?>
                                    </div>
                                </div>
                            </a>
                        </article>

                    <?php endforeach; ?>

                </div>

            <?php else: ?>

                <p>No se encontraron productos.</p>

            <?php endif; ?>

        </main>

    </div>

</section>

<script>
document.addEventListener('DOMContentLoaded', function () {

    /* ==========================================
     TOGGLE FILTROS
    ========================================== */
    
    document.querySelectorAll('.fs-filter__header').forEach(btn => {

      btn.addEventListener('click', function () {
    
        const filter = this.closest('.fs-filter');
        if (!filter) return;
    
        const isOpen = filter.classList.contains('is-open');
    
        // cerrar todos
        document.querySelectorAll('.fs-filter').forEach(f => {
          f.classList.remove('is-open');
        });
    
        // abrir solo si estaba cerrado
        if (!isOpen) {
          filter.classList.add('is-open');
        }
    
      });
    
    });

    /* ==========================================
     AUTO SUBMIT + CERRAR FILTRO
    ========================================== */
    
    const form = document.querySelector('.fs-archive__sidebar form');
    
    if (form) {
    form.addEventListener('change', function (e) {
    
      const target = e.target;
      if (!target) return;
    
      if (target.matches('input[type="radio"]')) {
    
        const filter = target.closest('.fs-filter');
        if (filter) {
          filter.classList.remove('is-open');
        }
    
        form.submit();
      }
    
      if (target.matches('.fs-price__input')) {
        form.submit();
      }
    
    });
    }

  /* =====================================================
     CONTROL INPUT PRECIO
  ===================================================== */

  const priceInput = document.querySelector('.fs-price__input');

  if (priceInput) {
    priceInput.addEventListener('input', () => {
      const max = Number(priceInput.getAttribute('max') || 0);
      let val = Number(priceInput.value || 0);

      if (val < 0) val = 0;
      if (max > 0 && val > max) val = max;

      priceInput.value = String(val);
    });
  }

  /* =====================================================
     COLOR SWATCHES (SIMPLES + COMPUESTOS "-")
  ===================================================== */

  const COLOR_MAP = {
    negro:'#000000',
    blanco:'#FFFFFF',
    coral:'#F2ECDF',
    rojo:'#FF0000',
    azul:'#0000FF',
    verde:'#008000',
    amarillo:'#FFFF00',
    naranja:'#FFA500',
    gris:'#808080',
    rosa:'#FFC0CB',
    morado:'#800080',
    turquesa:'#40E0D0',
    oro:'#FFD700',
    plata:'#C0C0C0',
    beige:'#F5F5DC',
    marron:'#8B4513',
    marrón:'#8B4513',
    lima:'#99FF33',
    royal:'#4169E1',
    marino:'#000080',
    bordeaux:'#800000',
    fluor:'#CCFF00',
    fucsia:'#FF00FF',
    multicolor:'#999999',
    blanco_coral:'#FF7F50',
  };

  const normalize = (value) =>
    String(value || '')
      .toLowerCase()
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .trim();

  const buildBackground = (slug) => {
      const parts = String(slug || '')
        .toLowerCase()
        .split('-')
        .map(s => s.trim())
        .filter(Boolean);
    
      if (!parts.length) return '#e5e7eb';
    
      // ✅ multicolor (solo o mezclado)
      if (parts.includes('multicolor')) {
        return 'linear-gradient(45deg, red, orange, yellow, green, blue, purple)';
      }
    
      // 1 color
      if (parts.length === 1) {
        return COLOR_MAP[parts[0]] || '#e5e7eb';
      }
    
      // 2+ colores
      const hexParts = parts.map(p => COLOR_MAP[p]).filter(Boolean);
      if (!hexParts.length) return '#e5e7eb';
      if (hexParts.length === 1) return hexParts[0];
    
      const step = 100 / hexParts.length;
      const stops = hexParts.map((hex, i) => {
        const start = i * step;
        const end = start + step;
        return `${hex} ${start}%, ${hex} ${end}%`;
      });
    
      return `linear-gradient(45deg, ${stops.join(', ')})`;
    };

  const paintSwatches = () => {
    document.querySelectorAll('.fs-color-choice').forEach(label => {

      const input = label.querySelector('.fs-color-input');
      const swatch = label.querySelector('.fs-color-swatch');

      if (!input || !swatch) return;

      const slug = input.value;
      if (!slug) return;

      const bg = buildBackground(slug);
      swatch.style.setProperty('--swatch-bg', bg);
    });
  };

  paintSwatches();

});
</script>
<?php
get_footer();