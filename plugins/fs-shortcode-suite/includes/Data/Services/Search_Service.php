<?php
declare(strict_types=1);

namespace FS\ShortcodeSuite\Data\Services;

use WP_Query;

defined('ABSPATH') || exit;

final class Search_Service
{
    private const LIMIT = 12;

    /* ============================================================
     *  SEARCH OVERLAY (PRODUCT BASE + OFFER AGGREGATION REAL)
     * ============================================================ */

    public function search(string $query = '', array $filters = []): array
    {
        $query = trim($query);

        $limited_args = $this->build_query_args($query, $filters);
        $limited_args['posts_per_page'] = self::LIMIT;

        $limited_query = new WP_Query($limited_args);
        $product_ids   = $limited_query->posts ?: [];

        $facet_args = $this->build_query_args($query, $filters);
        $facet_args['posts_per_page'] = -1;

        $facet_query = new WP_Query($facet_args);
        $facet_ids   = $facet_query->posts ?: [];

        return [
            'products' => $this->build_products($product_ids),
            'filters'  => $this->build_filters_dataset($facet_ids),
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
        $out = [];

        foreach ($ids as $product_id) {

            [$price_min, $colors_count] = $this->get_product_aggregates((int)$product_id);

            $out[] = [
                'id'           => (int) $product_id,
                'name'         => get_the_title($product_id),
                'permalink'    => get_permalink($product_id),
                'image'        => (string) get_post_meta($product_id, 'fs_image_main_url', true),
                'price_from'   => $price_min,
                'brand'        => $this->get_brand((int)$product_id),
                'colors_count' => $colors_count,
            ];
        }

        return $out;
    }

    /* ============================================================
     *  BUILD FILTER DATASET (REAL: VARIANTE + OFERTA)
     * ============================================================ */

    private function build_filters_dataset(array $product_ids): array
    {
        $sizes     = [];
        $surfaces  = [];
        $colors    = [];
        $brands    = [];
        $generos   = [];
        $price_min = null;
        $price_max = null;
    
        foreach ($product_ids as $product_id) {
    
            $product_id = (int) $product_id;
    
            // =========================
            // MARCA / GENERO (producto)
            // =========================
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
    
            // ==========================================
            // VARIANTES DEL PRODUCTO (relación real: parent)
            // ==========================================
            $product_hash = get_post_meta($product_id, 'fs_product_id', true);

            if (empty($product_hash)) {
                continue;
            }
            
            $variant_ids = get_posts([
                'post_type'      => 'fs_variante',
                'post_status'    => 'publish',
                'fields'         => 'ids',
                'posts_per_page' => -1,
                'no_found_rows'  => true,
                'meta_query'     => [
                    [
                        'key'     => 'fs_product_id',
                        'value'   => $product_hash,
                        'compare' => '=',
                    ]
                ]
            ]);
    
            if (empty($variant_ids)) {
                // si un producto no tiene variantes, no aporta a talla/color/superficie/precio
                continue;
            }
    
            foreach ($variant_ids as $variant_id) {
    
                $variant_id = (int) $variant_id;
    
                // =========================
                // SUPERFICIE (tax en variante)
                // =========================
                $surface_terms = wp_get_post_terms($variant_id, 'fs_superficie');
                if (!empty($surface_terms) && !is_wp_error($surface_terms)) {
                    foreach ($surface_terms as $term) {
                        $surfaces[$term->slug] = true;
                    }
                }
    
                // =========================
                // COLOR (tax en variante)
                // =========================
                $color_terms = wp_get_post_terms($variant_id, 'fs_color');
                if (!empty($color_terms) && !is_wp_error($color_terms)) {
                    foreach ($color_terms as $term) {
                        $colors[$term->slug] = true;
                    }
                }
    
                // =========================
                // OFERTAS (por fs_variant_id)
                // =========================
                $variant_global_id = (string) get_post_meta($variant_id, 'fs_variant_id', true);
                if ($variant_global_id === '') {
                    continue;
                }
    
                $offer_ids = get_posts([
                    'post_type'      => 'fs_oferta',
                    'post_status'    => 'publish',
                    'fields'         => 'ids',
                    'posts_per_page' => -1,
                    'no_found_rows'  => true,
                    'meta_query'     => [
                        [
                            'key'     => 'fs_variant_id',
                            'value'   => $variant_global_id,
                            'compare' => '=',
                        ],
                    ],
                ]);
    
                if (empty($offer_ids)) continue;
    
                foreach ($offer_ids as $offer_id) {
    
                    $offer_id = (int) $offer_id;
    
                    // Si sigues usando fs_in_stock en tu importador, cambia a:
                    // $in_stock = get_post_meta($offer_id, 'fs_in_stock', true);
                    $in_stock = get_post_meta($offer_id, 'fs_in_stock', true);

                    if (in_array($in_stock, ['0', 0, false, 'false', '', null], true)) {
                        continue;
                    }
                    
                    // =========================
                    // TALLA (tax en oferta)
                    // =========================
                    $size_terms = wp_get_post_terms($offer_id, 'fs_talla_eu');
                    
                    if (!empty($size_terms) && !is_wp_error($size_terms)) {
                        foreach ($size_terms as $term) {
                            $sizes[$term->slug] = true;
                        }
                    }
    
                    // =========================
                    // PRECIO (meta en oferta)
                    // =========================
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
            }
        }
    
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
    
        // ============================
        // VARIANTES DEL PRODUCTO
        // ============================
        $variant_ids = get_posts([
            'post_type'      => 'fs_variante',
            'post_status'    => 'publish',
            'fields'         => 'ids',
            'posts_per_page' => -1,
            'no_found_rows'  => true,
            'meta_query'     => [
                [
                    'key'     => 'fs_product_id',
                    'value'   => (string) $product_id,
                    'compare' => 'LIKE', // importante
                ]
            ],
        ]);
    
        if (empty($variant_ids)) {
            return [null, 0];
        }
    
        foreach ($variant_ids as $variant_id) {
    
            $variant_id = (int) $variant_id;
    
            // ============================
            // COLORES (tax en variante)
            // ============================
            $color_terms = wp_get_post_terms($variant_id, 'fs_color');
            if (!empty($color_terms) && !is_wp_error($color_terms)) {
                foreach ($color_terms as $term) {
                    if (!empty($term->slug)) {
                        $colors[$term->slug] = true;
                    }
                }
            }
    
            // ============================
            // OFERTAS DE LA VARIANTE
            // ============================
            $variant_global_id = (string) get_post_meta($variant_id, 'fs_variant_id', true);
            if ($variant_global_id === '') {
                continue;
            }
    
            $offer_ids = get_posts([
                'post_type'      => 'fs_oferta',
                'post_status'    => 'publish',
                'fields'         => 'ids',
                'posts_per_page' => -1,
                'no_found_rows'  => true,
                'meta_query'     => [
                    [
                        'key'     => 'fs_variant_id',
                        'value'   => $variant_global_id,
                        'compare' => '=',
                    ]
                ],
            ]);
    
            if (empty($offer_ids)) {
                continue;
            }
    
            foreach ($offer_ids as $offer_id) {
    
                $offer_id = (int) $offer_id;
    
                $in_stock = get_post_meta($offer_id, 'fs_in_stock', true);
                if (!$in_stock || $in_stock === '0' || $in_stock === 'false') {
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