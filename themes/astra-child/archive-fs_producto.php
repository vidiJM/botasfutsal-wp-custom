<?php
defined("ABSPATH") || exit();

require_once WP_PLUGIN_DIR . "/fs-shortcode-suite/src/Query/ProductQuery.php";

get_header();

/* ====================================================
1️⃣ Filtros GET
==================================================== */

$current_genero = isset($_GET["genero"])
    ? sanitize_text_field($_GET["genero"])
    : "";
$current_superficie = isset($_GET["superficie"])
    ? sanitize_text_field($_GET["superficie"])
    : "";
$current_marca = isset($_GET["marca"])
    ? sanitize_text_field($_GET["marca"])
    : "";
$current_color = isset($_GET["color"])
    ? sanitize_text_field($_GET["color"])
    : "";
$current_precio_max = isset($_GET["precio_max"])
    ? (float) $_GET["precio_max"]
    : 0;

$paged = get_query_var("paged") ? (int) get_query_var("paged") : 1;
$per_page = 12;

/* ====================================================
2️⃣ Query Offer-driven
==================================================== */

$result = \FS\ShortcodeSuite\Query\ProductQuery::get_products([
    "genero" => $current_genero,
    "superficie" => $current_superficie,
    "marca" => $current_marca,
    "color" => $current_color,
    "precio_max" => $current_precio_max,
    "per_page" => $per_page,
    "paged" => $paged,
]);

$product_ids = $result["ids"] ?? [];
$total = $result["total"] ?? 0;
$images = $result["images"] ?? [];
$prices = $result["prices"] ?? [];

/* ====================================================
3️⃣ Datos filtros
==================================================== */

$generos = [
    "hombre" => "Hombre",
    "mujer" => "Mujer",
    "unisex" => "Unisex",
    "infantil" => "Infantil",
];

$superficies = [
    (object) ["slug" => "indoor", "name" => "Indoor"],
    (object) ["slug" => "mixta", "name" => "Mixta"],
    (object) ["slug" => "outdoor", "name" => "Outdoor"],
    (object) ["slug" => "turf", "name" => "Turf"],
];

$brands = get_terms([
    "taxonomy" => "fs_marca",
    "hide_empty" => false,
    "orderby" => "name",
    "order" => "ASC",
]);

if (is_wp_error($brands)) {
    $brands = [];
}

const COLOR_MAP = [
        'negro'=>'#000000',
        'blanco'=>'#FFFFFF',
        'blanco_coral'=>'#F2ECDF',
        'rojo'=>'#FF0000',
        'azul'=>'#0000FF',
        'azul_marino' =>'#000080',
        'azul_claro' =>'#90D5FF',
        'azul_fucsia'=>'#6A00FF',
        'azul_royal'=>'#4169E1',
        'verde'=>'#008000',
        'verde_fluor'=>'#39FF14',
        'amarillo'=>'#FFFF00',
        'amarillo_fluor'=>'#CCFF00',
        'naranja'=>'#FFA500',
        'gris'=>'#808080',
        'gris_claro'=>'#CDCDCD',
        'rosa'=>'#FFC0CB',
        'morado'=>'#800080',
        'turquesa'=>'#40E0D0',
        'oro'=>'#FFD700',
        'plata'=>'#C0C0C0',
        'beige'=>'#F5F5DC',
        'marron'=>'#8B4513',
        'marrón'=>'#8B4513',
        'cuero'=>'#AC7434',
        'lima'=>'#99FF33',
        'royal'=>'#4169E1',
        'marino'=>'#000080',
        'bordeaux'=>'#800000',
        'neon'=>'#39FF14',
        'fucsia'=>'#FF00FF',
        'multicolor'=>'#999999'
    ];

?>

<style>
    .fs-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 0 32px
    }

    .fs-archive {
        color: #111
    }

    .fs-archive * {
        box-sizing: border-box
    }

    .fs-archive button {
        all: unset;
        cursor: pointer
    }

    /* Layout */
    .fs-archive__layout {
        display: grid;
        grid-template-columns: 260px 1fr;
        gap: 60px
    }

    @media(max-width:1024px) {
        .fs-archive__layout {
            display: block
        }

        .fs-archive__sidebar {
            display: none
        }
    }

    /* Grid */
    .fs-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 90px 40px;
        margin-top: 40px
    }

    @media(min-width:1024px) {
        .fs-grid {
            grid-template-columns: repeat(4, 1fr);
            gap: 110px 48px
        }
    }

    .fs-card {
        text-decoration: none;
        color: #111
    }

    .fs-card__image {
        background: #f6f6f6;
        padding: 28px;
        aspect-ratio: 1/1;
        display: flex;
        align-items: center;
        justify-content: center
    }

    .fs-card__image img {
        max-width: 75%;
        height: auto;
        transition: .3s
    }

    .fs-card:hover .fs-card__image img {
        transform: scale(1.05)
    }

    .fs-card__body {
        margin-top: 18px
    }

    .fs-card__price {
        font-size: 13px;
        font-weight: 600;
        margin-bottom: 6px
    }

    .fs-card__title {
        font-size: 13px;
        font-weight: 500;
        letter-spacing: .3px
    }

    /* Filters */
    .fs-filter {
        border-bottom: 1px solid #e5e5e5;
        padding: 16px 0
    }

    .fs-filter__header {
        display: flex;
        justify-content: space-between;
        font-size: 14px;
        font-weight: 600
    }

    .fs-filter__body {
        display: none;
        margin-top: 12px
    }

    .fs-filter.is-open .fs-filter__body {
        display: block
    }

    .fs-filter__option {
        display: block;
        font-size: 13px;
        margin-bottom: 6px
    }

    .fs-filter__submit {
        margin-top: 20px;
        background: #111;
        color: #fff;
        padding: 10px 16px;
        font-size: 13px
    }

    /* Colors */
    .fs-color-swatches {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.fs-color-swatch {
    width: 26px;
    height: 26px;
    border-radius: 50%;
    border: 1px solid #ddd;
    position: relative;
    cursor: pointer;
    transition: all .2s ease;
}

.fs-color-swatch:hover {
    transform: scale(1.1);
    border-color: #000;
}

.fs-color-swatch.is-active {
    border: 2px solid #000;
}
</style>

<section class="fs-archive">

    <div class="fs-container">
        <h1><?php post_type_archive_title(); ?></h1>
        <p><?php echo esc_html($total); ?> productos</p>
    </div>

    <div class="fs-archive__layout fs-container">

        <aside class="fs-archive__sidebar">
            <form method="get">

                <!-- Género -->
                <div class="fs-filter">
                    <button type="button" class="fs-filter__header">Género <span>+</span></button>
                    <div class="fs-filter__body">
                        <?php foreach ($generos as $slug => $label): ?>
                            <label class="fs-filter__option">
                                <input type="radio" name="genero" value="<?php echo esc_attr(
                                    $slug
                                ); ?>" <?php checked(
    $current_genero,
    $slug
); ?>>
                                <?php echo esc_html($label); ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Superficie -->
                <div class="fs-filter">
                    <button type="button" class="fs-filter__header">Superficie <span>+</span></button>
                    <div class="fs-filter__body">
                        <?php foreach ($superficies as $term): ?>
                            <label class="fs-filter__option">
                                <input type="radio" name="superficie" value="<?php echo esc_attr(
                                    $term->slug
                                ); ?>" <?php checked(
    $current_superficie,
    $term->slug
); ?>>
                                <?php echo esc_html($term->name); ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Marca -->
                <div class="fs-filter">
                    <button type="button" class="fs-filter__header">Marca <span>+</span></button>
                    <div class="fs-filter__body">
                        <?php foreach ($brands as $brand): ?>
                            <label class="fs-filter__option">
                                <input type="radio" name="marca" value="<?php echo esc_attr(
                                    $brand->slug
                                ); ?>" <?php checked(
    $current_marca,
    $brand->slug
); ?>>
                                <?php echo esc_html($brand->name); ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Color -->
                <div class="fs-filter">
                    <button type="button" class="fs-filter__header">Color <span>+</span></button>
                    <?php $colores = get_terms([
                        'taxonomy'   => 'fs_color',
                        'hide_empty' => false,
                    ]);
                    
                    if (!empty($colores) && !is_wp_error($colores)) :
                    
                        echo '<div class="fs-color-swatches">';
                    
                        foreach ($colores as $term) :
                    
                            $slug = $term->slug;
                            $active = ($current_color === $slug) ? 'is-active' : '';
                    
                            echo '<a href="' . esc_url(add_query_arg('color', $slug)) . '" 
                                     class="fs-color-swatch ' . esc_attr($active) . '" 
                                     title="' . esc_attr($term->name) . '"
                                     data-color="' . esc_attr($slug) . '">
                                  </a>';
                    
                        endforeach;
                    
                        echo '</div>';
                    
                    endif; ?>
                </div>

                <!-- Precio -->
                <div class="fs-filter">
                    <button type="button" class="fs-filter__header">Precio máximo (€) <span>+</span></button>
                    <div class="fs-filter__body">
                        <input type="number" name="precio_max" value="<?php echo esc_attr(
                            $current_precio_max
                        ); ?>">
                    </div>
                </div>

                <button type="submit" class="fs-filter__submit">Aplicar filtros</button>

            </form>
        </aside>

        <main>

            <?php if (!empty($product_ids)): ?>
                <div class="fs-grid">
                    <?php foreach ($product_ids as $product_id):

                        $title = get_the_title($product_id);
                        $link = get_permalink($product_id);
                        $image = $images[$product_id] ?? "";
                        $price = $prices[$product_id] ?? "";
                        ?>
                        <article class="fs-card">
                            <a href="<?php echo esc_url($link); ?>">
                                <div class="fs-card__image">
                                    <?php if ($image): ?>
                                        <img src="<?php echo esc_url(
                                            $image
                                        ); ?>" alt="<?php echo esc_attr(
    $title
); ?>">
                                    <?php endif; ?>
                                </div>
                                <div class="fs-card__body">
                                    <?php if ($price): ?>
                                        <div class="fs-card__price">€ <?php echo number_format(
                                            (float) $price,
                                            0,
                                            ",",
                                            "."
                                        ); ?></div>
                                    <?php endif; ?>
                                    <div class="fs-card__title"><?php echo esc_html(
                                        $title
                                    ); ?></div>
                                </div>
                            </a>
                        </article>
                    <?php
                    endforeach; ?>
                </div>
            <?php else: ?>
                <p>No se encontraron productos.</p>
            <?php endif; ?>

        </main>
    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function () {

        document.querySelectorAll('.fs-filter__header').forEach(btn => {
            btn.addEventListener('click', () => btn.parentElement.classList.toggle('is-open'));
        });

    });
    
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
    
    document.querySelectorAll('.fs-color-swatch').forEach(el => {

    const slug = el.dataset.color;
    if (!slug) return;

    const parts = slug.split(/[-_]/);

    if (parts.length === 1) {

        const color = COLOR_MAP[parts[0]] || '#ccc';
        el.style.background = color;

    } else {

        const color1 = COLOR_MAP[parts[0]] || '#ccc';
        const color2 = COLOR_MAP[parts[1]] || '#ccc';

        el.style.background = `linear-gradient(135deg, ${color1} 50%, ${color2} 50%)`;
    }
});
</script>
<?php
get_footer();