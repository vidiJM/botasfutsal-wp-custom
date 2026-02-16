<?php
declare(strict_types=1);

namespace FS\ShortcodeSuite\Data\Services;

use WP_Query;

defined('ABSPATH') || exit;

final class Search_Service
{
    private const LIMIT = 12;

    public function search(string $query = '', array $filters = []): array
    {
        $query = trim($query);

        $has_text    = $query !== '';
        $has_filters = !empty($filters);

        // 🔹 Productos limitados
        $limited_args = $this->build_query_args($query, $filters);
        $limited_args['posts_per_page'] = self::LIMIT;

        $limited_query = new WP_Query($limited_args);
        $product_ids   = $limited_query->posts ?: [];

        // 🔹 Base completa para filtros
        $facet_args = $this->build_query_args($query, $filters);
        $facet_args['posts_per_page'] = -1;

        $facet_query = new WP_Query($facet_args);
        $facet_ids   = $facet_query->posts ?: [];

        return [
            'products' => ($has_text || $has_filters)
                ? $this->build_products($product_ids)
                : [],
            'filters'  => $this->build_filters_dataset($facet_ids),
        ];
    }

    private function build_query_args(string $query, array $filters): array
    {
        $meta_query = [
            'relation' => 'AND',
            [
                'key'     => 'fs_has_stock',
                'value'   => '1',
                'compare' => '=',
            ],
        ];

        if ($query !== '') {
            $meta_query[] = [
                'key'     => 'fs_model_signature',
                'value'   => $query,
                'compare' => 'LIKE',
            ];
        }

        if (!empty($filters['superficie'])) {
            $meta_query[] = [
                'key'     => 'fs_superficie_available',
                'value'   => (string) $filters['superficie'],
                'compare' => 'LIKE',
            ];
        }

        if (!empty($filters['talla'])) {
            $meta_query[] = [
                'key'     => 'fs_talla_available',
                'value'   => (string) $filters['talla'],
                'compare' => 'LIKE',
            ];
        }

        if (!empty($filters['color'])) {
            $meta_query[] = [
                'key'     => 'fs_color_available',
                'value'   => (string) $filters['color'],
                'compare' => 'LIKE',
            ];
        }

        $tax_query = ['relation' => 'AND'];

        if (!empty($filters['marca'])) {
            $tax_query[] = [
                'taxonomy' => 'fs_marca',
                'field'    => 'slug',
                'terms'    => sanitize_title((string) $filters['marca']),
            ];
        }

        if (!empty($filters['genero'])) {
            $tax_query[] = [
                'taxonomy' => 'fs_genero',
                'field'    => 'slug',
                'terms'    => sanitize_title((string) $filters['genero']),
            ];
        }

        $args = [
            'post_type'      => 'fs_producto',
            'post_status'    => 'publish',
            'fields'         => 'ids',
            'meta_query'     => $meta_query,
            'no_found_rows'  => true,
        ];

        if (count($tax_query) > 1) {
            $args['tax_query'] = $tax_query;
        }

        return $args;
    }

    private function build_filters_dataset(array $product_ids): array
    {
        $sizes      = [];
        $surfaces   = [];
        $colors     = [];
        $brands     = [];
        $generos    = [];
        $price_min  = null;
        $price_max  = null;
    
        foreach ($product_ids as $product_id) {
    
            $product_id = (int) $product_id;
    
            // TALLAS
            $rawSizes = get_post_meta($product_id, 'fs_talla_available', true);
            foreach ($this->normalize_list_meta($rawSizes) as $size) {
                $sizes[$size] = true;
            }
    
            // SUPERFICIES
            $rawSurfaces = get_post_meta($product_id, 'fs_superficie_available', true);
            foreach ($this->normalize_list_meta($rawSurfaces) as $surface) {
                $surfaces[$surface] = true;
            }
    
            // 🔥 COLORES (NUEVO)
            $rawColors = get_post_meta($product_id, 'fs_color_available', true);
            foreach ($this->normalize_list_meta($rawColors) as $color) {
                $colors[$color] = true;
            }
    
            // PRECIO
            $price = (float) get_post_meta($product_id, 'fs_price_min', true);
            if ($price > 0) {
                $price_min = $price_min === null ? $price : min($price_min, $price);
                $price_max = $price_max === null ? $price : max($price_max, $price);
            }
    
            // MARCA
            $terms_brand = wp_get_post_terms($product_id, 'fs_marca');
            if (!empty($terms_brand) && !is_wp_error($terms_brand)) {
                foreach ($terms_brand as $term) {
                    $brands[$term->slug] = $term->name;
                }
            }
    
            // GENERO
            $terms_gen = wp_get_post_terms($product_id, 'fs_genero');
            if (!empty($terms_gen) && !is_wp_error($terms_gen)) {
                foreach ($terms_gen as $term) {
                    $generos[$term->slug] = $term->name;
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
            'color'      => array_keys($colors), // 🔥 MUY IMPORTANTE
            'marca'      => $brands,
            'genero'     => $generos,
            'price_min'  => $price_min,
            'price_max'  => $price_max,
        ];
    }


    private function normalize_list_meta(mixed $raw): array
    {
        if (!$raw) return [];

        $raw = maybe_unserialize($raw);

        if (is_array($raw)) {
            return array_values(array_unique(array_filter(array_map('trim', $raw))));
        }

        $parts = preg_split('~[\r\n,\|]+~', (string) $raw) ?: [];
        return array_values(array_unique(array_filter(array_map('trim', $parts))));
    }

    private function build_products(array $ids): array
    {
        $out = [];
    
        foreach ($ids as $id) {
    
            $id = (int) $id;
    
            $rawColors = get_post_meta($id, 'fs_color_available', true);
            $colors    = $this->normalize_list_meta($rawColors);
            $colors_count = count($colors);
    
            $out[] = [
                'id'           => $id,
                'name'         => get_the_title($id),
                'permalink'    => get_permalink($id),
                'image'        => (string) get_post_meta($id, 'fs_image_main_url', true),
                'price_from'   => (float) get_post_meta($id, 'fs_price_min', true),
                'brand'        => $this->get_brand($id),
                'colors_count' => $colors_count,
            ];
        }
    
        return $out;
    }


    private function get_brand(int $id): ?string
    {
        $terms = wp_get_post_terms($id, 'fs_marca');
        return (!empty($terms) && !is_wp_error($terms)) ? $terms[0]->name : null;
    }
}
