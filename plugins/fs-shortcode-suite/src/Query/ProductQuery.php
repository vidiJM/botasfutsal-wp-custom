<?php
namespace FS\ShortcodeSuite\Query;

use WP_Query;

defined('ABSPATH') || exit;

final class ProductQuery
{
    public static function get_products(array $args = []): array
    {
        static $cache = [];

        $defaults = [
            'genero'     => '',
            'superficie' => '',
            'marca'      => '',
            'color'      => '',
            'talla'      => '',
            'precio_max' => 0,
            'per_page'   => 12,
            'paged'      => 1,
        ];

        $args = wp_parse_args($args, $defaults);

        $cache_key = md5(wp_json_encode($args));
        if (isset($cache[$cache_key])) {
            return $cache[$cache_key];
        }

        /* ====================================================
        1️⃣ Query OFERTAS
        ==================================================== */

        $meta_query = [
            [
                'key'   => 'fs_in_stock',
                'value' => '1',
            ]
        ];

        if (!empty($args['precio_max'])) {
            $meta_query[] = [
                'key'     => 'fs_price',
                'value'   => (float) $args['precio_max'],
                'compare' => '<=',
                'type'    => 'NUMERIC',
            ];
        }

        $offers = new WP_Query([
            'post_type'      => 'fs_oferta',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'no_found_rows'  => true,
            'meta_query'     => $meta_query,
        ]);

        if (empty($offers->posts)) {
            return self::cache($cache, $cache_key, self::empty());
        }

        /* ====================================================
        2️⃣ Mapear ofertas → variantes
        ==================================================== */

        $variant_external_ids = [];
        $variant_prices = [];

        foreach ($offers->posts as $offer_id) {

            $external_id = get_field('fs_variant_id', $offer_id);
            $price       = (float) get_field('fs_price', $offer_id);

            if (!$external_id || !$price) continue;

            $variant_external_ids[] = $external_id;

            if (!isset($variant_prices[$external_id]) || $price < $variant_prices[$external_id]) {
                $variant_prices[$external_id] = $price;
            }
        }

        $variant_external_ids = array_unique($variant_external_ids);

        if (!$variant_external_ids) {
            return self::cache($cache, $cache_key, self::empty());
        }

        /* ====================================================
        3️⃣ Query VARIANTES
        ==================================================== */

        $tax_query = ['relation' => 'AND'];

        foreach (['superficie' => 'fs_superficie', 'color' => 'fs_color', 'talla' => 'fs_talla_eu'] as $key => $tax) {
            if (!empty($args[$key])) {
                $tax_query[] = [
                    'taxonomy' => $tax,
                    'field'    => 'slug',
                    'terms'    => sanitize_title($args[$key]),
                ];
            }
        }

        $variant_ids = get_posts([
            'post_type'      => 'fs_variante',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'no_found_rows'  => true,
            'meta_query'     => [
                [
                    'key'     => 'fs_variant_id',
                    'value'   => $variant_external_ids,
                    'compare' => 'IN'
                ]
            ],
            'tax_query' => count($tax_query) > 1 ? $tax_query : [],
        ]);

        if (!$variant_ids) {
            return self::cache($cache, $cache_key, self::empty());
        }

        /* ====================================================
        4️⃣ Construir universo válido
        ==================================================== */

        $facets = [
            'genero'     => [],
            'marca'      => [],
            'superficie' => [],
            'color'      => [],
            'talla'      => [],
        ];

        $product_map = [];
        $images = [];
        $prices = [];

        foreach ($variant_ids as $variant_id) {

            $product_code = get_field('fs_product_id', $variant_id);
            if (!$product_code) continue;

            $product = get_posts([
                'post_type'   => 'fs_producto',
                'meta_key'    => 'fs_product_id',
                'meta_value'  => $product_code,
                'fields'      => 'ids',
                'numberposts' => 1,
            ]);

            if (empty($product[0])) continue;

            $product_id = $product[0];

            // filtros producto
            if ($args['marca'] && !has_term($args['marca'], 'fs_marca', $product_id)) continue;
            if ($args['genero'] && !has_term($args['genero'], 'fs_genero', $product_id)) continue;

            $product_map[$product_id] = true;

            // FACETS producto
            foreach (['fs_genero' => 'genero', 'fs_marca' => 'marca'] as $tax => $key) {
                $terms = wp_get_post_terms($product_id, $tax, ['fields' => 'slugs']);
                foreach ($terms as $slug) {
                    $facets[$key][$slug] = true;
                }
            }

            // FACETS variante
            foreach (['fs_superficie' => 'superficie', 'fs_color' => 'color', 'fs_talla_eu' => 'talla'] as $tax => $key) {
                $terms = wp_get_post_terms($variant_id, $tax, ['fields' => 'slugs']);
                foreach ($terms as $slug) {
                    $facets[$key][$slug] = true;
                }
            }

            // imagen
            if (!isset($images[$product_id])) {
                $images_raw = get_field('fs_images', $variant_id);
                if ($images_raw) {
                    $parts = preg_split('/[\r\n,]+/', $images_raw);
                    foreach ($parts as $url) {
                        $url = trim($url);
                        if (filter_var($url, FILTER_VALIDATE_URL)) {
                            $images[$product_id] = esc_url($url);
                            break;
                        }
                    }
                }
            }

            // precio mínimo
            $external_id = get_field('fs_variant_id', $variant_id);
            if ($external_id && isset($variant_prices[$external_id])) {
                $price = $variant_prices[$external_id];
                if (!isset($prices[$product_id]) || $price < $prices[$product_id]) {
                    $prices[$product_id] = $price;
                }
            }
        }

        $product_ids = array_keys($product_map);
        $total = count($product_ids);

        $offset = ($args['paged'] - 1) * $args['per_page'];
        $paged_ids = array_slice($product_ids, $offset, $args['per_page']);

        // limpiar facets a arrays simples
        foreach ($facets as $key => $values) {
            $facets[$key] = array_keys($values);
        }

        $result = [
            'ids'     => $paged_ids,
            'total'   => $total,
            'images'  => $images,
            'prices'  => $prices,
            'facets'  => $facets,
        ];

        return self::cache($cache, $cache_key, $result);
    }

    private static function cache(&$cache, string $key, array $result): array
    {
        $cache[$key] = $result;
        return $result;
    }

    private static function empty(): array
    {
        return [
            'ids'    => [],
            'total'  => 0,
            'images' => [],
            'prices' => [],
            'facets' => [],
        ];
    }
}