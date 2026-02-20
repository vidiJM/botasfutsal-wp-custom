<?php
declare(strict_types=1);

namespace FS\ShortcodeSuite\Shortcodes;

use FS\ShortcodeSuite\Core\Assets;
use WP_Query;

if (!defined('ABSPATH')) {
    exit;
}

final class Product_Detail
{
    public function __construct()
    {
        add_shortcode('fs_product_detail', [$this, 'render']);
    }

    public function render(): string
    {
        if (!is_singular('fs_producto')) {
            return '';
        }

        global $post;

        if (!$post instanceof \WP_Post) {
            return '';
        }

        $product_id = (int) $post->ID;

        $data = $this->build_product_data($product_id);

        if (empty($data['colors'])) {
            return '';
        }

        Assets::enqueue_product_detail();

        wp_add_inline_script(
            'fs-product-detail',
            'window.FS_PRODUCT_DATA = ' . wp_json_encode($data) . ';',
            'before'
        );

        ob_start();
        ?>

        <section class="fs-product-detail">

            <div class="fs-product-detail__gallery">

                <div class="fs-product-detail__thumbs"></div>

                <div class="fs-product-detail__main-wrapper">
                    <button type="button" class="fs-product-detail__nav fs-product-detail__nav--prev">‹</button>

                    <img src="" class="fs-product-detail__main-image" alt="">

                    <button type="button" class="fs-product-detail__nav fs-product-detail__nav--next">›</button>
                </div>

            </div>

            <div class="fs-product-detail__info">

                <h1 class="fs-product-detail__title">
                    <?php echo esc_html(get_the_title($product_id)); ?>
                </h1>

                <div class="fs-product-detail__price"></div>

                <div class="fs-product-detail__colors"></div>

                <div class="fs-product-detail__sizes"></div>

                <a href="#"
                   target="_blank"
                   rel="noopener"
                   class="fs-product-detail__cta">
                    Ir a tienda
                </a>

            </div>
                 <?php $this->render_description($product_id); ?>
        </section>

        <?php
        return (string) ob_get_clean();
    }

    /**
     * Renderiza la descripción normalizada desde ACF
     */
    private function render_description(int $product_id): void
    {
        $raw_description = get_field('fs_description_raw', $product_id);

        if (empty($raw_description)) {
            return;
        }

        $description = trim((string) $raw_description);

        // Detectar HTML escapado (&lt;p&gt;)
        if (strpos($description, '&lt;') !== false) {
            $description = html_entity_decode($description, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        // Eliminar clases del feed
        $description = preg_replace('/\sclass="[^"]*"/i', '', $description);

        // Permitir solo HTML seguro
        $allowed_tags = [
            'p' => [],
            'ul' => [],
            'ol' => [],
            'li' => [],
            'br' => [],
            'strong' => [],
            'b' => [],
            'em' => [],
        ];

        $description = wp_kses($description, $allowed_tags);

        // Si no contiene HTML estructural, convertir a párrafos
        if (strip_tags($description) === $description) {
            $description = wpautop($description);
        }

        ?>
        <div class="fs-product-detail__description">
            <?php echo $description; ?>
        </div>
        <?php
    }

    /**
     * Construye la estructura de colores, imágenes, tallas y precios
     */
    private function build_product_data(int $product_id): array
    {
        $product_code = get_field('fs_product_id', $product_id);

        if (!$product_code) {
            return [];
        }

        $variants_query = new WP_Query([
            'post_type'      => 'fs_variante',
            'posts_per_page' => -1,
            'meta_key'       => 'fs_product_id',
            'meta_value'     => $product_code,
            'fields'         => 'ids',
            'no_found_rows'  => true,
        ]);

        if (empty($variants_query->posts)) {
            return [];
        }

        $variant_map = [];

        foreach ($variants_query->posts as $variant_post_id) {
            $external_variant_id = get_field('fs_variant_id', $variant_post_id);
            if ($external_variant_id) {
                $variant_map[$external_variant_id] = $variant_post_id;
            }
        }

        if (empty($variant_map)) {
            return [];
        }

        $offers_query = new WP_Query([
            'post_type'      => 'fs_oferta',
            'posts_per_page' => -1,
            'meta_query'     => [
                [
                    'key'     => 'fs_variant_id',
                    'value'   => array_keys($variant_map),
                    'compare' => 'IN'
                ],
                [
                    'key'   => 'fs_in_stock',
                    'value' => '1'
                ]
            ],
            'fields'        => 'ids',
            'no_found_rows' => true,
        ]);

        $colors = [];

        foreach ($offers_query->posts as $offer_id) {

            $variant_external_id = get_field('fs_variant_id', $offer_id);

            if (!isset($variant_map[$variant_external_id])) {
                continue;
            }

            $variant_post_id = $variant_map[$variant_external_id];

            $color_terms = wp_get_post_terms($variant_post_id, 'fs_color');

            if (empty($color_terms)) {
                continue;
            }

            $color_slug = $color_terms[0]->slug;

            if (!isset($colors[$color_slug])) {
                $colors[$color_slug] = [
                    'images'   => [],
                    'sizes'    => [],
                    'price'    => null,
                    'shop_url' => null,
                ];
            }

            /*
             * ============================
             * IMAGE PARSER ROBUSTO
             * ============================
             */

            $images_raw = (string) get_field('fs_images', $variant_post_id);

            if ($images_raw !== '') {

                $parts = preg_split('/[\r\n,]+/', $images_raw);

                foreach ($parts as $url) {

                    $url = trim($url);

                    if ($url !== '' && filter_var($url, FILTER_VALIDATE_URL)) {
                        $colors[$color_slug]['images'][] = esc_url_raw($url);
                    }
                }
            }

            /*
             * ============================
             * SIZES + PRICE
             * ============================
             */

            $size  = get_field('fs_size_eu', $offer_id);
            $price = (float) get_field('fs_price', $offer_id);
            $url   = get_field('fs_url', $offer_id);

            if ($size) {
                $colors[$color_slug]['sizes'][] = $size;
            }

            if (
                is_null($colors[$color_slug]['price']) ||
                $price < $colors[$color_slug]['price']
            ) {
                $colors[$color_slug]['price']    = $price;
                $colors[$color_slug]['shop_url'] = $url;
            }
        }

        /*
         * ============================
         * LIMPIEZA FINAL
         * ============================
         */

        foreach ($colors as $slug => $data) {
            $colors[$slug]['images'] = array_values(array_unique($data['images']));
            $colors[$slug]['sizes']  = array_values(array_unique($data['sizes']));
        }

        return [
            'colors' => $colors
        ];
    }
}
