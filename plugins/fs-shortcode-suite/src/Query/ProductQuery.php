<?php
namespace FS\ShortcodeSuite\Query;

use WP_Query;

defined('ABSPATH') || exit;

final class ProductQuery
{
    public static function get_products(array $args = []): array
    {
        $defaults = [
            'genero'     => '',
            'superficie' => '',
            'marca'      => '',
            'color'      => '',
            'precio_max' => 0,
            'per_page'   => 12,
            'paged'      => 1,
        ];
    
        $args = wp_parse_args($args, $defaults);
    
        /*
        ====================================================
        1️⃣ Query OFERTAS (solo stock)
        ====================================================
        */
    
        $meta_query = [
            [
                'key'   => 'fs_in_stock',
                'value' => '1',
            ]
        ];
    
        if (!empty($args['precio_max'])) {
            $meta_query[] = [
                'key'     => 'fs_price',
                'value'   => (float)$args['precio_max'],
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
            return self::empty_result();
        }
    
        /*
        ====================================================
        2️⃣ Mapear ofertas → variantes + precios
        ====================================================
        */
    
        $variant_external_ids = [];
        $variant_prices = []; // external_variant_id => min price
    
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
    
        if (empty($variant_external_ids)) {
            return self::empty_result();
        }
    
        /*
        ====================================================
        3️⃣ Obtener variantes reales
        ====================================================
        */
    
        $variants = get_posts([
            'post_type'      => 'fs_variante',
            'meta_query'     => [
                [
                    'key'     => 'fs_variant_id',
                    'value'   => $variant_external_ids,
                    'compare' => 'IN'
                ]
            ],
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ]);
    
        if (empty($variants)) {
            return self::empty_result();
        }
    
        /*
        ====================================================
        4️⃣ Aplicar filtros (igual que antes)
        ====================================================
        */
    
        if (!empty($args['genero'])) {
    
            $meta_genero = ['relation' => 'OR'];
    
            switch ($args['genero']) {
    
                case 'infantil':
                    $meta_genero[] = [
                        'key'     => 'age_group',
                        'value'   => ['kids', 'junior', 'toddler'],
                        'compare' => 'IN',
                    ];
                    break;
    
                case 'unisex':
                    $meta_genero[] = [
                        'key'   => 'gender',
                        'value' => 'unisex',
                    ];
                    break;
    
                case 'hombre':
                    $meta_genero[] = [
                        'key'   => 'gender',
                        'value' => 'male',
                    ];
                    $meta_genero[] = [
                        'key'   => 'gender',
                        'value' => 'unisex',
                    ];
                    break;
    
                case 'mujer':
                    $meta_genero[] = [
                        'key'   => 'gender',
                        'value' => 'female',
                    ];
                    $meta_genero[] = [
                        'key'   => 'gender',
                        'value' => 'unisex',
                    ];
                    break;
            }
    
            $variants = get_posts([
                'post_type'      => 'fs_variante',
                'post__in'       => $variants,
                'meta_query'     => $meta_genero,
                'posts_per_page' => -1,
                'fields'         => 'ids',
            ]);
        }
    
        if (empty($variants)) {
            return self::empty_result();
        }
    
        /*
        ====================================================
        5️⃣ Mapear variantes → productos (sin query por variante)
        ====================================================
        */
    
        $product_ids = [];
        $images = [];
        $prices = [];
    
        foreach ($variants as $variant_id) {
    
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
            $product_ids[] = $product_id;
    
            /*
            Imagen solo primera vez
            */
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
    
            /*
            Precio mínimo desde variant_prices ya calculado
            */
            $external_id = get_field('fs_variant_id', $variant_id);
    
            if ($external_id && isset($variant_prices[$external_id])) {
    
                $price = $variant_prices[$external_id];
    
                if (!isset($prices[$product_id]) || $price < $prices[$product_id]) {
                    $prices[$product_id] = $price;
                }
            }
        }
    
        $product_ids = array_unique($product_ids);
        $total = count($product_ids);
    
        /*
        ====================================================
        6️⃣ Paginación manual
        ====================================================
        */
    
        $offset = ($args['paged'] - 1) * $args['per_page'];
        $paged_ids = array_slice($product_ids, $offset, $args['per_page']);
    
        return [
            'ids'    => $paged_ids,
            'total'  => $total,
            'images' => $images,
            'prices' => $prices,
        ];
    }

    private static function empty_result(): array
    {
        return [
            'ids'    => [],
            'total'  => 0,
            'images' => [],
            'prices' => [],
        ];
    }
}