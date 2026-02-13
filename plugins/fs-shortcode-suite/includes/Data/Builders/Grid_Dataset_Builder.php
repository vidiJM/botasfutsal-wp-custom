<?php
declare(strict_types=1);

namespace FS\ShortcodeSuite\Data\Builders;

use FS\ShortcodeSuite\Data\Repository\Product_Repository;

defined('ABSPATH') || exit;

final class Grid_Dataset_Builder
{
    private Product_Repository $repository;

    public function __construct(Product_Repository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Construye dataset respetando filtros activos.
     *
     * @param array<int> $product_ids
     * @param array<string, mixed> $filters
     * @return array<int, array<string, mixed>>
     */
    public function build(array $product_ids, array $filters = []): array
    {
        if (empty($product_ids)) {
            return [];
        }

        $filter_color = '';
        if (!empty($filters['color']) && is_string($filters['color'])) {
            $filter_color = sanitize_title($filters['color']);
        }

        $products = [];

        foreach ($product_ids as $product_id) {

            $product_id = (int) $product_id;
            if ($product_id <= 0) {
                continue;
            }

            $variants = $this->get_variants($product_id);
            if (!$variants) {
                continue;
            }

            $title     = get_the_title($product_id);
            $permalink = get_permalink($product_id);

            if (!is_string($title) || $title === '') {
                continue;
            }

            if (!is_string($permalink) || $permalink === '') {
                continue;
            }

            $product_data = [
                'id'        => $product_id,
                'name'      => $title,
                'permalink' => $permalink,
                'colors'    => [],
            ];

            foreach ($variants as $variant) {

                $variant_id = (int) $variant->ID;
                if ($variant_id <= 0) {
                    continue;
                }

                $color = $this->get_variant_color($variant_id);
                if (!$color) {
                    continue;
                }

                // Si hay filtro de color → solo ese color
                if ($filter_color !== '' && $color !== $filter_color) {
                    continue;
                }

                $offers = $this->get_valid_offers($variant_id);
                if (!$offers) {
                    continue;
                }

                if (!isset($product_data['colors'][$color])) {
                    $product_data['colors'][$color] = [
                        'images' => $this->get_variant_images($variant_id),
                        'sizes'  => [],
                    ];
                }

                foreach ($offers as $offer) {

                    $size  = (string) $offer['size'];
                    $price = (float)  $offer['price'];

                    if ($size === '' || $price <= 0) {
                        continue;
                    }

                    if (
                        !isset($product_data['colors'][$color]['sizes'][$size]) ||
                        $price < (float) $product_data['colors'][$color]['sizes'][$size]['price']
                    ) {
                        $product_data['colors'][$color]['sizes'][$size] = [
                            'price' => $price,
                            'url'   => (string) $offer['url'],
                        ];
                    }
                }
            }

            if (!empty($product_data['colors'])) {
                $products[] = $product_data;
            }
        }

        return $products;
    }

    /**
     * @return array<int, \WP_Post>
     */
    private function get_variants(int $product_id): array
    {
        return get_posts([
            'post_type'      => 'fs_variante',
            'post_status'    => 'publish',
            'post_parent'    => $product_id,
            'numberposts'    => -1,
            'no_found_rows'  => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        ]);
    }

    private function get_variant_color(int $variant_id): ?string
    {
        $terms = get_the_terms($variant_id, 'fs_color');

        if (empty($terms) || is_wp_error($terms)) {
            return null;
        }

        $slug = $terms[0]->slug ?? null;

        if (!is_string($slug) || $slug === '') {
            return null;
        }

        return sanitize_title($slug);
    }

    /**
     * @return array<int, string>
     */
    private function get_variant_images(int $variant_id): array
    {
        $images = [];

        $main = get_post_meta($variant_id, 'fs_image_main_url', true);
        if (is_string($main) && $main !== '') {
            $images[] = esc_url_raw($main);
        }

        $raw = get_post_meta($variant_id, 'fs_images', true);
        if (is_string($raw) && $raw !== '') {
            $split = preg_split('/[\r\n,]+/', $raw);
            if ($split) {
                foreach ($split as $img) {
                    $img = trim((string) $img);
                    if ($img !== '') {
                        $images[] = esc_url_raw($img);
                    }
                }
            }
        }

        $raw2 = get_post_meta($variant_id, 'fs_images_raw', true);
        if (is_string($raw2) && $raw2 !== '') {
            foreach (explode(',', $raw2) as $img) {
                $img = trim((string) $img);
                if ($img !== '') {
                    $images[] = esc_url_raw($img);
                }
            }
        }

        return array_values(array_unique($images));
    }

    /**
     * ⚠️ fs_variant_id en ofertas es HASH externo.
     *
     * @return array<int, array{size:string, price:float, url:string}>
     */
    private function get_valid_offers(int $variant_id): array
    {
        $variant_hash = get_post_meta($variant_id, 'fs_variant_id', true);

        if (!is_string($variant_hash) || $variant_hash === '') {
            return [];
        }

        $offers = get_posts([
            'post_type'      => 'fs_oferta',
            'post_status'    => 'publish',
            'numberposts'    => -1,
            'no_found_rows'  => true,
            'meta_query'     => [
                [
                    'key'     => 'fs_variant_id',
                    'value'   => $variant_hash,
                    'compare' => '=',
                ],
                [
                    'key'     => 'fs_in_stock',
                    'value'   => '1',
                    'compare' => '=',
                ],
                [
                    'key'     => 'fs_price',
                    'value'   => 0,
                    'compare' => '>',
                    'type'    => 'NUMERIC',
                ],
                [
                    'key'     => 'fs_url',
                    'compare' => 'EXISTS',
                ],
            ],
        ]);

        if (!$offers) {
            return [];
        }

        $result = [];

        foreach ($offers as $offer) {

            $price = (float) get_post_meta($offer->ID, 'fs_price', true);
            $size  = get_post_meta($offer->ID, 'fs_size_eu', true);
            $url   = get_post_meta($offer->ID, 'fs_url', true);

            if (!is_string($size) || $size === '') {
                continue;
            }

            if ($price <= 0) {
                continue;
            }

            if (!is_string($url) || $url === '') {
                continue;
            }

            $result[] = [
                'size'  => sanitize_text_field($size),
                'price' => $price,
                'url'   => esc_url_raw($url),
            ];
        }

        return $result;
    }
}
