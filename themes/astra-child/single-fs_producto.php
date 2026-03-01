<?php
/**
 * Single template for CPT: fs_producto
 * Child theme override
 */

defined('ABSPATH') || exit;

get_header();

/* =====================================================
   SISTEMA AUTOMÁTICO DE VALORACIÓN
===================================================== */

function fs_calculate_product_rating($post_id)
{
    $price = (float) get_field('fs_price_min', $post_id);

    $brand_terms = wp_get_post_terms($post_id, 'fs_marca');
    $brand = !empty($brand_terms) ? strtolower($brand_terms[0]->name) : '';

    $caracteristicas = wp_get_post_terms($post_id, 'fs_caracteristica');
    $is_resistencia = false;

    foreach ($caracteristicas as $term) {
        if ($term->slug === 'resistencia') {
            $is_resistencia = true;
        }
    }

    /* ===== GAMA ===== */

    if ($price < 45) {
        $gama = 'basica';
    } elseif ($price <= 80) {
        $gama = 'media';
    } else {
        $gama = 'alta';
    }

    /* ===== AJUSTE ===== */

    $fit_map = [
        'nike'   => 4.0,
        'mizuno' => 4.5,
        'joma'   => 4.5,
        'kelme'  => 4.0,
        'umbro'  => 3.8,
        'puma'   => 4.0,
        'adidas' => 4.2,
    ];

    $rating_fit = $fit_map[$brand] ?? 4.0;

    /* ===== COMODIDAD ===== */

    $comfort_map = [
        'basica' => 3.5,
        'media'  => 4.0,
        'alta'   => 4.5,
    ];

    $rating_comfort = $comfort_map[$gama];

    /* ===== DURABILIDAD ===== */

    $durability_map = [
        'basica' => 3.0,
        'media'  => 3.8,
        'alta'   => 4.5,
    ];

    $rating_durability = $durability_map[$gama];

    if ($is_resistencia) {
        $rating_durability += 0.5;
    }

    /* ===== CALIDAD-PRECIO ===== */

    if ($price < 45) {
        $rating_value = 4.5;
    } elseif ($price <= 80) {
        $rating_value = 4.0;
    } else {
        $rating_value = 3.8;
    }

    /* ===== GLOBAL ===== */

    $rating_global = round(
        ($rating_fit + $rating_comfort + $rating_durability + $rating_value) / 4,
        1
    );

    return [
        'fit'        => $rating_fit,
        'comfort'    => $rating_comfort,
        'durability' => $rating_durability,
        'value'      => $rating_value,
        'global'     => $rating_global,
    ];
}

/* =====================================================
   RENDER ESTRELLAS
===================================================== */

function fs_render_stars_row($label, $rating)
{
    if (!$rating) return '';

    $full = floor($rating);
    $half = ($rating - $full) >= 0.5 ? 1 : 0;

    ob_start(); ?>
    <div class="fs-product-rating__row">
        <span class="fs-product-rating__label"><?php echo esc_html($label); ?></span>
        <span class="fs-product-rating__stars">
            <?php for ($i = 0; $i < $full; $i++) echo '★'; ?>
            <?php if ($half) echo '☆'; ?>
        </span>
    </div>
    <?php
    return ob_get_clean();
}
?>

<main id="primary" class="site-main fs-single fs-single-product" role="main">

<?php if (have_posts()) : ?>
<?php while (have_posts()) : the_post(); ?>

<?php $ratings = fs_calculate_product_rating(get_the_ID()); ?>

<article id="post-<?php the_ID(); ?>" <?php post_class('fs-product'); ?>>

    <div class="fs-product__body">
        <?php echo do_shortcode('[fs_product_detail]'); ?>
    </div>


</article>

<!-- ===============================
     SCHEMA REVIEW AUTOMÁTICO
================================ -->

<script type="application/ld+json">
{
  "@context": "https://schema.org/",
  "@type": "Product",
  "name": "<?php echo esc_js(get_the_title()); ?>",
  "aggregateRating": {
    "@type": "AggregateRating",
    "ratingValue": "<?php echo esc_js($ratings['global']); ?>",
    "reviewCount": "1"
  }
}
</script>

<?php endwhile; ?>
<?php else : ?>

<section class="fs-empty">
    <h1><?php esc_html_e('Producto no encontrado', 'tu-textdomain'); ?></h1>
    <p><?php esc_html_e('No hay contenido disponible para este producto.', 'tu-textdomain'); ?></p>
</section>

<?php endif; ?>

</main>

<?php get_footer(); ?>