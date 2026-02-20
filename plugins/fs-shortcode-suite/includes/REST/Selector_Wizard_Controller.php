<?php
declare(strict_types=1);

namespace FS\ShortcodeSuite\REST;

use WP_REST_Request;
use WP_REST_Response;

if (!defined('ABSPATH')) {
    exit;
}

final class Selector_Wizard_Controller
{
    public function register_routes(): void
    {
        register_rest_route(
            'fs/v1',
            '/wizard',
            [
                'methods'  => 'POST',
                'callback' => [$this, 'handle'],
                'permission_callback' => '__return_true',
            ]
        );
    }
    
    public function handle(WP_REST_Request $request): WP_REST_Response
    {
        $params = $request->get_json_params();
    
        $gender  = $params[0] ?? null;   // hombre | mujer | infantil
        $surface = $params[1] ?? null;   // indoor | cesped | exterior
        $style   = $params[2] ?? null;
    
        if (!$gender || !$surface) {
            return new WP_REST_Response([], 200);
        }
    
        /*
        |--------------------------------------------------------------------------
        | 1️⃣ Buscar variantes por tax_query
        |--------------------------------------------------------------------------
        */
    
        $tax_query = [
            'relation' => 'AND',
        ];
    
        if ($gender === 'infantil') {
    
            $tax_query[] = [
                'taxonomy' => 'fs_age_group',
                'field'    => 'slug',
                'terms'    => ['kids', 'junior', 'toddler'],
            ];
    
        } else {
    
            $tax_query[] = [
                'taxonomy' => 'fs_age_group',
                'field'    => 'slug',
                'terms'    => ['adult'],
            ];
    
            $tax_query[] = [
                'taxonomy' => 'fs_genero',
                'field'    => 'slug',
                'terms'    => [$gender, 'unisex'],
            ];
        }
    
        $variant_query = new \WP_Query([
            'post_type'      => 'fs_variante',
            'posts_per_page' => -1,
            'tax_query'      => $tax_query,
            'fields'         => 'ids'
        ]);
    
        if (!$variant_query->posts) {
            return new WP_REST_Response([], 200);
        }
    
        /*
        |--------------------------------------------------------------------------
        | 2️⃣ Extraer internal product_id
        |--------------------------------------------------------------------------
        */
    
        $internal_ids = [];
    
        foreach ($variant_query->posts as $variant_id) {
    
            $internal_id = get_field('fs_product_id', $variant_id);
    
            if ($internal_id) {
                $internal_ids[] = $internal_id;
            }
        }
    
        $internal_ids = array_unique($internal_ids);
    
        if (empty($internal_ids)) {
            return new WP_REST_Response([], 200);
        }
    
        /*
        |--------------------------------------------------------------------------
        | 3️⃣ Buscar productos por:
        |    - fs_product_id
        |    - fs_superficie_available
        |--------------------------------------------------------------------------
        */
    
        $meta_query = [
            'relation' => 'AND',
    
            [
                'key'     => 'fs_product_id',
                'value'   => $internal_ids,
                'compare' => 'IN'
            ],
    
            [
                'key'     => 'fs_superficie_available',
                'value'   => $surface,
                'compare' => 'LIKE'
            ]
        ];
    
        $product_query = new \WP_Query([
            'post_type'      => 'fs_producto',
            'posts_per_page' => 3,
            'meta_query'     => $meta_query
        ]);
    
        if (!$product_query->posts) {
            return new WP_REST_Response([], 200);
        }
    
        /*
        |--------------------------------------------------------------------------
        | 4️⃣ Formatear respuesta
        |--------------------------------------------------------------------------
        */
    
        $results = [];
    
        foreach ($product_query->posts as $product) {
    
            $results[] = [
                'id'    => $product->ID,
                'title' => get_the_title($product->ID),
                'link'  => get_permalink($product->ID),
                'image' => get_field('fs_image_main_url', $product->ID),
                'price' => get_field('fs_price_min', $product->ID),
            ];
        }
    
        return new WP_REST_Response($results, 200);
    }


}
