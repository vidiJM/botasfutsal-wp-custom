<?php
declare(strict_types=1);

namespace FS\ImporterCore\Aggregator;

defined('ABSPATH') || exit;

final class ProductAggregator
{
    public static function aggregateProduct(int $productPostId): void
    {
        if ($productPostId <= 0) {
            return;
        }

        $variantIds = get_posts([
            'post_type'      => 'fs_variante',
            'post_status'    => 'publish',
            'post_parent'    => $productPostId,
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ]);

        if (empty($variantIds)) {
            self::resetProduct($productPostId);
            return;
        }

        $offerIds = get_posts([
            'post_type'      => 'fs_oferta',
            'post_status'    => 'publish',
            'post_parent__in'=> $variantIds,
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ]);

        if (empty($offerIds)) {
            self::resetProduct($productPostId);
            return;
        }

        $minPrice    = null;
        $maxPrice    = null;
        $hasStock    = false;
        $bestOfferId = null;
        $merchants   = [];

        $sizes     = [];
        $surfaces  = [];
        $colors    = [];

        foreach ($offerIds as $offerId) {

            $price = (float) get_post_meta($offerId, 'fs_price', true);
            $inStock = filter_var(
                get_post_meta($offerId, 'fs_in_stock', true),
                FILTER_VALIDATE_BOOL
            );

            if ($price <= 0 || !$inStock) {
                continue;
            }

            $hasStock = true;

            // TALLA
            $size = trim((string) get_post_meta($offerId, 'fs_size_eu', true));
            if ($size !== '') {
                $sizes[$size] = true;
            }

            // MERCHANT
            $merchant = trim((string) get_post_meta($offerId, 'fs_merchant_name', true));
            if ($merchant !== '') {
                $merchants[$merchant] = true;
            }

            // PRECIO
            if ($minPrice === null || $price < $minPrice) {
                $minPrice    = $price;
                $bestOfferId = $offerId;
            }

            if ($maxPrice === null || $price > $maxPrice) {
                $maxPrice = $price;
            }

            // VARIANTE PADRE
            $variantId = (int) wp_get_post_parent_id($offerId);

            // SUPERFICIE
            $termsSurface = wp_get_post_terms($variantId, 'fs_superficie');
            if (!is_wp_error($termsSurface)) {
                foreach ($termsSurface as $term) {
                    $surfaces[$term->slug] = true;
                }
            }

            // COLOR
            $termsColor = wp_get_post_terms($variantId, 'fs_color');
            if (!is_wp_error($termsColor)) {
                foreach ($termsColor as $term) {
                    $colors[$term->slug] = true;
                }
            }
        }

        $hasStock = $hasStock && $minPrice !== null && $minPrice > 0;

        $sizes    = array_keys($sizes);
        $surfaces = array_keys($surfaces);
        $colors   = array_keys($colors);

        sort($sizes, SORT_NATURAL);
        sort($surfaces);
        sort($colors);

        /*
        |--------------------------------------------------------------------------
        | 🔥 GUARDAR COMO STRING (ACF TEXTAREA SAFE)
        |--------------------------------------------------------------------------
        */

        update_post_meta($productPostId, 'fs_talla_available', implode("\n", $sizes));
        update_post_meta($productPostId, 'fs_superficie_available', implode("\n", $surfaces));
        update_post_meta($productPostId, 'fs_color_available', implode("\n", $colors));

        update_post_meta($productPostId, 'fs_price_min', $hasStock ? (float) $minPrice : 0);
        update_post_meta($productPostId, 'fs_price_max', $hasStock ? (float) ($maxPrice ?? 0) : 0);
        update_post_meta($productPostId, 'fs_has_stock', $hasStock ? 1 : 0);
        update_post_meta($productPostId, 'fs_best_offer_post_id', $hasStock ? (int) $bestOfferId : '');
        update_post_meta($productPostId, '_fs_merchants', array_keys($merchants));
        update_post_meta($productPostId, 'fs_last_aggregated_at', current_time('mysql'));
    }

    public static function aggregateAll(int $limit = 100): void
    {
        $paged = 1;
    
        do {
            $query = new \WP_Query([
                'post_type'      => 'fs_producto',
                'post_status'    => 'publish',
                'posts_per_page' => $limit,
                'paged'          => $paged,
                'fields'         => 'ids',
                'no_found_rows'  => true,
            ]);
    
            if (!$query->have_posts()) {
                break;
            }
    
            foreach ($query->posts as $productId) {
                self::aggregateProduct((int) $productId);
            }
    
            $paged++;
    
        } while ($paged <= $query->max_num_pages);
    }


    private static function resetProduct(int $productPostId): void
    {
        update_post_meta($productPostId, 'fs_price_min', 0);
        update_post_meta($productPostId, 'fs_price_max', 0);
        update_post_meta($productPostId, 'fs_has_stock', 0);

        update_post_meta($productPostId, 'fs_talla_available', '');
        update_post_meta($productPostId, 'fs_superficie_available', '');
        update_post_meta($productPostId, 'fs_color_available', '');

        update_post_meta($productPostId, 'fs_best_offer_post_id', '');
        update_post_meta($productPostId, '_fs_merchants', []);
        update_post_meta($productPostId, 'fs_last_aggregated_at', current_time('mysql'));
    }
}
