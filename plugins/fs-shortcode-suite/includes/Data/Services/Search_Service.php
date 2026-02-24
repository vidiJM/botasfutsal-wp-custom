<?php
declare(strict_types=1);

namespace FS\ShortcodeSuite\Data\Services;

use WP_Query;

defined('ABSPATH') || exit;

final class Search_Service
{
    private const LIMIT = 10;

    /* ============================================================
     *  SEARCH OVERLAY (PRODUCT BASE + OFFER AGGREGATION REAL)
     * ============================================================ */
    public function search(string $query = '', array $filters = []): array
    {
        $query = trim($query);
    
        /*
         * ============================================================
         *  1️⃣ QUERY LIMITADA (LIMIT + 1 para detectar has_more)
         * ============================================================
         */
        $limited_args = $this->build_query_args($query, $filters);
        $limited_args['posts_per_page'] = self::LIMIT + 1;
    
        $limited_query = new WP_Query($limited_args);
        $product_ids   = $limited_query->posts ?: [];
    
        // Detectar si hay más resultados
        $has_more = count($product_ids) > self::LIMIT;
    
        if ($has_more) {
            $product_ids = array_slice($product_ids, 0, self::LIMIT);
        }
    
        /*
         * ============================================================
         *  2️⃣ DATASET COMPLETO PARA FACETS (sin límite)
         * ============================================================
         */
        $facet_args = $this->build_query_args($query, $filters);
        $facet_args['posts_per_page'] = -1;
    
        $facet_query = new WP_Query($facet_args);
        $facet_ids   = $facet_query->posts ?: [];
    
        /*
         * ============================================================
         *  3️⃣ RESPONSE
         * ============================================================
         */
        return [
            'products' => $this->build_products($product_ids),
            'filters'  => $this->build_filters_dataset($facet_ids),
            'has_more' => $has_more,
        ];
    }

    /* ============================================================
     *  BUILD PRODUCT QUERY
     * ============================================================ */

    private function build_query_args(string $query, array $filters): array
    {
        $meta_query = ['relation' => 'AND'];
        $tax_query  = ['relation' => 'AND'];

        if ($query !== '') {
            $meta_query[] = [
                'key'     => 'fs_model_signature',
                'value'   => $query,
                'compare' => 'LIKE',
            ];
        }

        if (!empty($filters['marca'])) {
            $tax_query[] = [
                'taxonomy' => 'fs_marca',
                'field'    => 'slug',
                'terms'    => sanitize_title($filters['marca']),
            ];
        }

        if (!empty($filters['genero'])) {
            $tax_query[] = [
                'taxonomy' => 'fs_genero',
                'field'    => 'slug',
                'terms'    => sanitize_title($filters['genero']),
            ];
        }

        return [
            'post_type'     => 'fs_producto',
            'post_status'   => 'publish',
            'fields'        => 'ids',
            'meta_query'    => count($meta_query) > 1 ? $meta_query : [],
            'tax_query'     => count($tax_query) > 1 ? $tax_query : [],
            'no_found_rows' => true,
        ];
    }

    /* ============================================================
     *  BUILD PRODUCTS (PRECIO REAL DESDE OFERTAS)
     * ============================================================ */

    private function build_products(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }
    
        $ids = array_map('intval', $ids);
    
        /*
         * ============================================================
         *  1️⃣ VARIANTES MASIVAS POR PARENT
         * ============================================================
         */
        $variant_ids = get_posts([
            'post_type'      => 'fs_variante',
            'post_status'    => 'publish',
            'fields'         => 'ids',
            'posts_per_page' => -1,
            'no_found_rows'  => true,
            'post_parent__in'=> $ids,
        ]);
    
        $variants_by_product = [];
        $variant_global_ids  = [];
    
        foreach ($variant_ids as $variant_id) {
    
            $variant_id = (int) $variant_id;
            $product_id = (int) wp_get_post_parent_id($variant_id);
    
            if (!isset($variants_by_product[$product_id])) {
                $variants_by_product[$product_id] = [];
            }
    
            $variants_by_product[$product_id][] = $variant_id;
    
            $global_id = (string) get_post_meta($variant_id, 'fs_variant_id', true);
            if ($global_id !== '') {
                $variant_global_ids[$variant_id] = $global_id;
            }
        }
    
        /*
         * ============================================================
         *  2️⃣ OFERTAS MASIVAS
         * ============================================================
         */
        $offers_by_variant = [];
    
        if (!empty($variant_global_ids)) {
    
            $offer_ids = get_posts([
                'post_type'      => 'fs_oferta',
                'post_status'    => 'publish',
                'fields'         => 'ids',
                'posts_per_page' => -1,
                'no_found_rows'  => true,
                'meta_query'     => [
                    [
                        'key'     => 'fs_variant_id',
                        'value'   => array_values($variant_global_ids),
                        'compare' => 'IN',
                    ]
                ],
            ]);
    
            foreach ($offer_ids as $offer_id) {
    
                $offer_id = (int) $offer_id;
    
                $variant_id = array_search(
                    get_post_meta($offer_id, 'fs_variant_id', true),
                    $variant_global_ids,
                    true
                );
    
                if (!$variant_id) {
                    continue;
                }
    
                $offers_by_variant[$variant_id][] = $offer_id;
            }
        }
    
        /*
         * ============================================================
         *  3️⃣ CONSTRUIR OUTPUT
         * ============================================================
         */
        $out = [];
    
        foreach ($ids as $product_id) {
    
            $product_id = (int) $product_id;
    
            $min_price   = null;
            $colors      = [];
    
            $variant_list = $variants_by_product[$product_id] ?? [];
    
            foreach ($variant_list as $variant_id) {
    
                // Colores
                $color_terms = wp_get_post_terms($variant_id, 'fs_color');
                if (!empty($color_terms) && !is_wp_error($color_terms)) {
                    foreach ($color_terms as $term) {
                        $colors[$term->slug] = true;
                    }
                }
    
                $offer_list = $offers_by_variant[$variant_id] ?? [];
    
                foreach ($offer_list as $offer_id) {
    
                    $in_stock = get_post_meta($offer_id, 'fs_in_stock', true);
    
                    if (in_array($in_stock, ['0', 0, false, 'false', '', null], true)) {
                        continue;
                    }
    
                    $price_raw      = (string) get_post_meta($offer_id, 'fs_price', true);
                    $price_sale_raw = (string) get_post_meta($offer_id, 'fs_price_sale', true);
    
                    $price      = (float) str_replace(',', '.', $price_raw);
                    $price_sale = (float) str_replace(',', '.', $price_sale_raw);
    
                    $final = ($price_sale > 0) ? $price_sale : $price;
    
                    if ($final > 0) {
                        $min_price = ($min_price === null)
                            ? $final
                            : min($min_price, $final);
                    }
                }
            }
    
            $out[] = [
                'id'           => $product_id,
                'name'         => get_the_title($product_id),
                'permalink'    => get_permalink($product_id),
                'image'        => (string) get_post_meta($product_id, 'fs_image_main_url', true),
                'price_from'   => $min_price,
                'brand'        => $this->get_brand($product_id),
                'colors_count' => count($colors),
            ];
        }
    
        return $out;
    }

    /* ============================================================
     *  BUILD FILTER DATASET (REAL: VARIANTE + OFERTA)
     * ============================================================ */

    private function build_filters_dataset(array $product_ids): array
    {
        if (empty($product_ids)) {
            return [
                'talla'      => [],
                'superficie' => [],
                'color'      => [],
                'marca'      => [],
                'genero'     => [],
                'price_min'  => null,
                'price_max'  => null,
            ];
        }
    
        $sizes     = [];
        $surfaces  = [];
        $colors    = [];
        $brands    = [];
        $generos   = [];
        $price_min = null;
        $price_max = null;
    
        /*
         * ============================================================
         *  1️⃣ MARCAS Y GÉNEROS (PRODUCTO)
         * ============================================================
         */
        foreach ($product_ids as $product_id) {
    
            $product_id = (int) $product_id;
    
            $brand_terms = wp_get_post_terms($product_id, 'fs_marca');
            if (!empty($brand_terms) && !is_wp_error($brand_terms)) {
                foreach ($brand_terms as $term) {
                    $brands[$term->slug] = $term->name;
                }
            }
    
            $gender_terms = wp_get_post_terms($product_id, 'fs_genero');
            if (!empty($gender_terms) && !is_wp_error($gender_terms)) {
                foreach ($gender_terms as $term) {
                    $generos[$term->slug] = $term->name;
                }
            }
        }
    
        /*
         * ============================================================
         *  2️⃣ OBTENER TODOS LOS PRODUCT_HASH
         * ============================================================
         */
        $product_hashes = [];
    
        foreach ($product_ids as $product_id) {
            $hash = get_post_meta((int) $product_id, 'fs_product_id', true);
            if (!empty($hash)) {
                $product_hashes[] = $hash;
            }
        }
    
        $product_hashes = array_unique($product_hashes);
    
        if (empty($product_hashes)) {
            return [
                'talla'      => [],
                'superficie' => [],
                'color'      => [],
                'marca'      => $brands,
                'genero'     => $generos,
                'price_min'  => null,
                'price_max'  => null,
            ];
        }
    
        /*
         * ============================================================
         *  3️⃣ QUERY MASIVA DE VARIANTES
         * ============================================================
         */
        $variant_ids = get_posts([
            'post_type'      => 'fs_variante',
            'post_status'    => 'publish',
            'fields'         => 'ids',
            'posts_per_page' => -1,
            'no_found_rows'  => true,
            'meta_query'     => [
                [
                    'key'     => 'fs_product_id',
                    'value'   => $product_hashes,
                    'compare' => 'IN',
                ]
            ]
        ]);
    
        if (empty($variant_ids)) {
            return [
                'talla'      => [],
                'superficie' => [],
                'color'      => [],
                'marca'      => $brands,
                'genero'     => $generos,
                'price_min'  => null,
                'price_max'  => null,
            ];
        }
    
        /*
         * ============================================================
         *  4️⃣ SUPERFICIE Y COLOR (VARIANTE)
         * ============================================================
         */
        foreach ($variant_ids as $variant_id) {
    
            $variant_id = (int) $variant_id;
    
            $surface_terms = wp_get_post_terms($variant_id, 'fs_superficie');
            if (!empty($surface_terms) && !is_wp_error($surface_terms)) {
                foreach ($surface_terms as $term) {
                    $surfaces[$term->slug] = true;
                }
            }
    
            $color_terms = wp_get_post_terms($variant_id, 'fs_color');
            if (!empty($color_terms) && !is_wp_error($color_terms)) {
                foreach ($color_terms as $term) {
                    $colors[$term->slug] = true;
                }
            }
        }
    
        /*
         * ============================================================
         *  5️⃣ OBTENER TODOS LOS VARIANT_GLOBAL_ID
         * ============================================================
         */
        $variant_global_ids = [];
    
        foreach ($variant_ids as $variant_id) {
            $global_id = (string) get_post_meta((int)$variant_id, 'fs_variant_id', true);
            if ($global_id !== '') {
                $variant_global_ids[] = $global_id;
            }
        }
    
        $variant_global_ids = array_unique($variant_global_ids);
    
        if (empty($variant_global_ids)) {
            return [
                'talla'      => [],
                'superficie' => array_keys($surfaces),
                'color'      => array_keys($colors),
                'marca'      => $brands,
                'genero'     => $generos,
                'price_min'  => null,
                'price_max'  => null,
            ];
        }
    
        /*
         * ============================================================
         *  6️⃣ QUERY MASIVA DE OFERTAS
         * ============================================================
         */
        $offer_ids = get_posts([
            'post_type'      => 'fs_oferta',
            'post_status'    => 'publish',
            'fields'         => 'ids',
            'posts_per_page' => -1,
            'no_found_rows'  => true,
            'meta_query'     => [
                [
                    'key'     => 'fs_variant_id',
                    'value'   => $variant_global_ids,
                    'compare' => 'IN',
                ],
            ],
        ]);
    
        if (empty($offer_ids)) {
            return [
                'talla'      => [],
                'superficie' => array_keys($surfaces),
                'color'      => array_keys($colors),
                'marca'      => $brands,
                'genero'     => $generos,
                'price_min'  => null,
                'price_max'  => null,
            ];
        }
    
        /*
         * ============================================================
         *  7️⃣ TALLA + PRECIO DESDE OFERTAS
         * ============================================================
         */
        foreach ($offer_ids as $offer_id) {
    
            $offer_id = (int) $offer_id;
    
            $in_stock = get_post_meta($offer_id, 'fs_in_stock', true);
    
            if (in_array($in_stock, ['0', 0, false, 'false', '', null], true)) {
                continue;
            }
    
            // TALLAS
            $size_terms = wp_get_post_terms($offer_id, 'fs_talla_eu');
            if (!empty($size_terms) && !is_wp_error($size_terms)) {
                foreach ($size_terms as $term) {
                    $sizes[$term->slug] = true;
                }
            }
    
            // PRECIO
            $price_raw      = (string) get_post_meta($offer_id, 'fs_price', true);
            $price_sale_raw = (string) get_post_meta($offer_id, 'fs_price_sale', true);
    
            $price      = (float) str_replace(',', '.', $price_raw);
            $price_sale = (float) str_replace(',', '.', $price_sale_raw);
    
            $final = ($price_sale > 0) ? $price_sale : $price;
    
            if ($final > 0) {
                $price_min = ($price_min === null) ? $final : min($price_min, $final);
                $price_max = ($price_max === null) ? $final : max($price_max, $final);
            }
        }
    
        /*
         * ============================================================
         *  8️⃣ ORDENACIÓN FINAL
         * ============================================================
         */
        ksort($brands);
        ksort($generos);
        ksort($surfaces);
        ksort($colors);
    
        $sizes = array_keys($sizes);
        sort($sizes, SORT_NATURAL);
    
        return [
            'talla'      => $sizes,
            'superficie' => array_keys($surfaces),
            'color'      => array_keys($colors),
            'marca'      => $brands,
            'genero'     => $generos,
            'price_min'  => $price_min,
            'price_max'  => $price_max,
        ];
    }

    /* ============================================================
     *  PRODUCT AGGREGATES
     * ============================================================ */
    private function get_product_aggregates(int $product_id): array
    {
        $min_price = null;
        $colors    = [];
    
        /*
         * ============================================================
         *  1️⃣ OBTENER VARIANTES POR PARENT (NO MÁS LIKE)
         * ============================================================
         */
        $variant_ids = get_posts([
            'post_type'      => 'fs_variante',
            'post_status'    => 'publish',
            'fields'         => 'ids',
            'posts_per_page' => -1,
            'no_found_rows'  => true,
            'post_parent'    => $product_id,
        ]);
    
        if (empty($variant_ids)) {
            return [null, 0];
        }
    
        /*
         * ============================================================
         *  2️⃣ COLORES DESDE VARIANTES
         * ============================================================
         */
        foreach ($variant_ids as $variant_id) {
    
            $variant_id = (int) $variant_id;
    
            $color_terms = wp_get_post_terms($variant_id, 'fs_color');
            if (!empty($color_terms) && !is_wp_error($color_terms)) {
                foreach ($color_terms as $term) {
                    if (!empty($term->slug)) {
                        $colors[$term->slug] = true;
                    }
                }
            }
        }
    
        /*
         * ============================================================
         *  3️⃣ OBTENER TODOS LOS VARIANT_GLOBAL_ID
         * ============================================================
         */
        $variant_global_ids = [];
    
        foreach ($variant_ids as $variant_id) {
            $global_id = (string) get_post_meta((int)$variant_id, 'fs_variant_id', true);
            if ($global_id !== '') {
                $variant_global_ids[] = $global_id;
            }
        }
    
        $variant_global_ids = array_unique($variant_global_ids);
    
        if (empty($variant_global_ids)) {
            return [null, count($colors)];
        }
    
        /*
         * ============================================================
         *  4️⃣ QUERY MASIVA DE OFERTAS
         * ============================================================
         */
        $offer_ids = get_posts([
            'post_type'      => 'fs_oferta',
            'post_status'    => 'publish',
            'fields'         => 'ids',
            'posts_per_page' => -1,
            'no_found_rows'  => true,
            'meta_query'     => [
                [
                    'key'     => 'fs_variant_id',
                    'value'   => $variant_global_ids,
                    'compare' => 'IN',
                ]
            ],
        ]);
    
        if (empty($offer_ids)) {
            return [null, count($colors)];
        }
    
        /*
         * ============================================================
         *  5️⃣ CALCULAR PRECIO MÍNIMO
         * ============================================================
         */
        foreach ($offer_ids as $offer_id) {
    
            $offer_id = (int) $offer_id;
    
            $in_stock = get_post_meta($offer_id, 'fs_in_stock', true);
            if (in_array($in_stock, ['0', 0, false, 'false', '', null], true)) {
                continue;
            }
    
            $price_raw      = (string) get_post_meta($offer_id, 'fs_price', true);
            $price_sale_raw = (string) get_post_meta($offer_id, 'fs_price_sale', true);
    
            $price      = (float) str_replace(',', '.', $price_raw);
            $price_sale = (float) str_replace(',', '.', $price_sale_raw);
    
            $final = ($price_sale > 0) ? $price_sale : $price;
    
            if ($final > 0) {
                $min_price = ($min_price === null)
                    ? $final
                    : min($min_price, $final);
            }
        }
    
        return [$min_price, count($colors)];
    }

    private function get_brand(int $id): ?string
    {
        $terms = wp_get_post_terms($id, 'fs_marca');
        return (!empty($terms) && !is_wp_error($terms))
            ? $terms[0]->name
            : null;
    }


    /* ============================================================
     *  WIZARD (VARIANTE + OFERTA DRIVEN)
     * ============================================================ */

    public function wizard_search(array $filters): array
    {
        $tax_query = ['relation' => 'AND'];

        // GÉNERO
        if (!empty($filters['gender'])) {

            $gender = sanitize_title($filters['gender']);

            if ($gender === 'infantil') {
                $tax_query[] = [
                    'taxonomy' => 'fs_age_group',
                    'field'    => 'slug',
                    'terms'    => 'kids',
                ];
            } else {
                $tax_query[] = [
                    'taxonomy' => 'fs_genero',
                    'field'    => 'slug',
                    'terms'    => $gender,
                ];
            }
        }

        // SUPERFICIE
        if (!empty($filters['surface'])) {

            $surface = sanitize_title($filters['surface']);

            if ($surface === 'turf') {

                $tax_query[] = [
                    'taxonomy' => 'fs_superficie',
                    'field'    => 'slug',
                    'terms'    => ['turf'],
                ];

            } elseif ($surface === 'indoor') {

                $tax_query[] = [
                    'taxonomy' => 'fs_superficie',
                    'field'    => 'slug',
                    'terms'    => ['indoor', 'mixta'],
                    'operator' => 'IN',
                ];

            } elseif ($surface === 'outdoor') {

                $tax_query[] = [
                    'taxonomy' => 'fs_superficie',
                    'field'    => 'slug',
                    'terms'    => ['outdoor', 'mixta'],
                    'operator' => 'IN',
                ];
            }
        }

        // CIERRE
        if (!empty($filters['closure'])) {
            $tax_query[] = [
                'taxonomy' => 'fs_cierre',
                'field'    => 'slug',
                'terms'    => sanitize_title($filters['closure']),
            ];
        }

        $variant_args = [
            'post_type'      => 'fs_variante',
            'post_status'    => 'publish',
            'posts_per_page' => 300,
            'fields'         => 'ids',
            'no_found_rows'  => true,
        ];

        if (count($tax_query) > 1) {
            $variant_args['tax_query'] = $tax_query;
        }

        $variant_query = new WP_Query($variant_args);

        if (empty($variant_query->posts)) {
            return [];
        }

        [$minBudget, $maxBudget] = $this->wizard_budget_to_range($filters['budget'] ?? '');

        $products = [];

        foreach ($variant_query->posts as $variant_id) {

            $product_id = (int) wp_get_post_parent_id($variant_id);
            if ($product_id <= 0) continue;

            $variant_global_id = get_post_meta($variant_id, 'fs_variant_id', true);
            if (empty($variant_global_id)) continue;

            $offer_query = new WP_Query([
                'post_type'      => 'fs_oferta',
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                'fields'         => 'ids',
                'no_found_rows'  => true,
                'meta_query'     => [
                    [
                        'key'     => 'fs_variant_id',
                        'value'   => $variant_global_id,
                        'compare' => '=',
                    ],
                ],
            ]);

            if (empty($offer_query->posts)) continue;

            foreach ($offer_query->posts as $offer_id) {

                $in_stock = get_post_meta($offer_id, 'fs_in_stock', true);
                if (!$in_stock || $in_stock === '0' || $in_stock === 'false') continue;

                $price      = (float) str_replace(',', '.', (string) get_post_meta($offer_id, 'fs_price', true));
                $price_sale = (float) str_replace(',', '.', (string) get_post_meta($offer_id, 'fs_price_sale', true));

                $final_price = $price_sale > 0 ? $price_sale : $price;
                if ($final_price <= 0) continue;

                if ($minBudget !== null && $final_price < $minBudget) continue;
                if ($maxBudget !== null && $final_price > $maxBudget) continue;

                if (!isset($products[$product_id]) || $final_price < $products[$product_id]['price']) {
                    $products[$product_id] = [
                        'price'      => $final_price,
                        'variant_id' => $variant_id,
                        'offer_id'   => $offer_id,
                    ];
                }
            }
        }

        if (empty($products)) return [];

        uasort($products, fn($a, $b) => $a['price'] <=> $b['price']);
        $products = array_slice($products, 0, 3, true);

        $out = [];

        foreach ($products as $product_id => $data) {

            $variant_id = $data['variant_id'];
            $variant_image = null;

            $images_raw = get_post_meta($variant_id, 'fs_images', true);

            if (!empty($images_raw)) {
                $images_array = preg_split('/(?=https:\/\/)/', $images_raw, -1, PREG_SPLIT_NO_EMPTY);
                if (!empty($images_array[0])) {
                    $variant_image = trim($images_array[0]);
                }
            }

            $out[] = [
                'id'         => $product_id,
                'variant_id' => $variant_id,
                'offer_id'   => $data['offer_id'],
                'title'      => get_the_title($product_id),
                'link'       => get_permalink($product_id) . '?variant=' . $variant_id,
                'image'      => $variant_image,
                'price'      => $data['price'],
            ];
        }

        return $out;
    }

    private function wizard_budget_to_range(string $budget): array
    {
        $budget = strtolower(trim($budget));

        return match ($budget) {
            'low'  => [null, 60],
            'mid'  => [60, 100],
            'high' => [100, null],
            default => [null, null],
        };
    }
}