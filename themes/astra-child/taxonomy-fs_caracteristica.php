<?php
/**
 * Template for taxonomy: fs_caracteristica
 */

defined('ABSPATH') || exit;

get_header();

$term = get_queried_object();

if (!$term || is_wp_error($term)) {
    get_footer();
    return;
}

$title = single_term_title('', false);
$description = term_description($term->term_id, 'fs_caracteristica');
?>

<main class="fs-caracteristica">

    <!-- HERO -->
    <section class="fs-caracteristica__hero">
        <h1><?php echo esc_html($title); ?></h1>

        <?php if ($description) : ?>
            <div class="fs-caracteristica__description">
                <?php echo wp_kses_post($description); ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- SELECTOR -->
    <section class="fs-caracteristica__selector">
        <?php
        $terms = get_terms([
            'taxonomy'   => 'fs_caracteristica',
            'hide_empty' => true,
        ]);

        if (!empty($terms) && !is_wp_error($terms)) :
            foreach ($terms as $t) :
                $active = ($t->term_id === $term->term_id) ? 'is-active' : '';
                ?>
                <a href="<?php echo esc_url(get_term_link($t)); ?>"
                   class="fs-caracteristica__pill <?php echo esc_attr($active); ?>">
                    <?php echo esc_html($t->name); ?>
                </a>
            <?php
            endforeach;
        endif;
        ?>
    </section>

    <!-- GRID -->
    <section class="fs-caracteristica__grid">
        <div class="fs-grid">

        <?php
        $products = new WP_Query([
            'post_type'      => 'fs_producto',
            'posts_per_page' => 12,
            'tax_query'      => [
                [
                    'taxonomy' => 'fs_caracteristica',
                    'field'    => 'term_id',
                    'terms'    => $term->term_id,
                ]
            ]
        ]);

        if ($products->have_posts()) :

            while ($products->have_posts()) :
                $products->the_post();

                $product_id = get_the_ID();
                $product_code = get_field('fs_product_id', $product_id);
                
                
                           
                if (!$product_code) continue;

                /* ==============================
                   VARIANTES
                ============================== */

                $variant_ids = get_posts([
                    'post_type'      => 'fs_variante',
                    'posts_per_page' => -1,
                    'meta_key'       => 'fs_product_id',
                    'meta_value'     => $product_code,
                    'fields'         => 'ids',
                ]);

                if (empty($variant_ids)) continue;

                $colors = [];
                $primary_image = '';
                $secondary_image = '';
                $sizes = [];
                $min_price = null;

                foreach ($variant_ids as $variant_id) {

                    // COLORES
                    $color_terms = wp_get_post_terms($variant_id, 'fs_color');
                    foreach ($color_terms as $color_term) {
                        $colors[$color_term->slug] = $color_term->name;
                    }

                    /* ==============================
                       IMÁGENES (desde producto)
                    ============================== */
                    
                    $images_raw = get_field('fs_images_raw', $product_id);
                    
                    $primary_image = '';
                    $secondary_image = '';
                    
                    if ($images_raw) {
                    
                        $images = preg_split('/[\r\n\s,]+/', trim($images_raw));
                    
                        if (!empty($images[0])) {
                            $primary_image = esc_url($images[0]);
                        }
                    
                        if (!empty($images[1])) {
                            $secondary_image = esc_url($images[1]);
                        }
                    }
                    
                    /* Fallback */
                    if (!$primary_image) {
                        $primary_image = get_the_post_thumbnail_url($product_id, 'large');
                    }

                    /* ==============================
                       OFERTAS
                    ============================== */

                    $offer_ids = get_posts([
                        'post_type' => 'fs_oferta',
                        'meta_query' => [
                            [
                                'key'   => 'fs_variant_id',
                                'value' => get_field('fs_variant_id', $variant_id)
                            ],
                            [
                                'key'   => 'fs_in_stock',
                                'value' => '1'
                            ]
                        ],
                        'fields' => 'ids'
                    ]);

                    foreach ($offer_ids as $offer_id) {

                        $size = get_field('fs_size_eu', $offer_id);
                        if ($size) {
                            $sizes[$size] = true;
                        }

                        $price = (float) get_field('fs_price', $offer_id);
                        if ($price > 0 && (is_null($min_price) || $price < $min_price)) {
                            $min_price = $price;
                        }
                        
                    }
                }

                if (!$primary_image) continue;
                ?>

                <div class="fs-card">

                    <a href="<?php the_permalink(); ?>" class="fs-card__image">
                        <img 
                            src="<?php echo esc_url($primary_image); ?>"
                            data-hover="<?php echo esc_url($secondary_image); ?>"
                            alt="<?php the_title_attribute(); ?>"
                            loading="lazy"
                            decoding="async"
                            width="500"
                            height="500"
                        >
                    </a>

                    <div class="fs-card__body">

                        <h3 class="fs-card__title">
                            <?php the_title(); ?>
                        </h3>

                        <div class="fs-card__meta">
                            <?php if (!empty($colors)) : ?>
                                <?php $count_colors = count($colors);

                                    if ($count_colors === 1) {
                                        echo '<span>1 color</span>';
                                    } else {
                                        echo '<span>'.$count_colors.' colores</span>';
                                    } ?>
                            <?php endif; ?>
                        
                            <?php if (!empty($sizes)) : ?>
                                <span class="fs-card__sizes-count">
                                    <?php echo count($sizes); ?> tallas
                                </span>
                            <?php endif; ?>
                        </div>

                        <?php if ($min_price) : ?>
                            <div class="fs-card__price">
                                Desde <?php echo number_format($min_price, 2, ',', '.'); ?> €
                            </div>
                        <?php endif; ?>

                    </div>

                </div>

                <?php
            endwhile;

            wp_reset_postdata();

        else :
            echo '<p>No hay productos disponibles.</p>';
        endif;
        ?>

        </div>
    </section>

</main>

<script>
document.addEventListener('DOMContentLoaded', function(){

    document.querySelectorAll('.fs-card__image img').forEach(img => {

        const hoverSrc = img.dataset.hover;
        if (!hoverSrc) return;

        // 🔥 PRELOAD
        const preload = new Image();
        preload.src = hoverSrc;

        const original = img.src;

        img.parentElement.addEventListener('mouseenter', () => {
            img.src = hoverSrc;
        });

        img.parentElement.addEventListener('mouseleave', () => {
            img.src = original;
        });

    });

});
</script>

<?php get_footer(); ?>