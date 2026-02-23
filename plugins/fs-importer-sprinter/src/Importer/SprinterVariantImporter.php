<?php
declare(strict_types=1);

namespace FS\ImporterSprinter\Importer;

use WP_Error;
use WP_Query;

final class SprinterVariantImporter
{
    private const POST_TYPE = 'fs_variante';
    private const META_KEY  = 'fs_variant_id';

    public static function upsert(array $data): ?int
    {
        $variantId = isset($data['variant_id']) ? trim((string) $data['variant_id']) : '';
        if ($variantId === '') {
            error_log('[VARIANT IMPORTER] variant_id missing');
            return null;
        }

        $postId = self::findByVariantId($variantId);

        if (!$postId) {
            $parentId = isset($data['product_wp_post_id']) ? (int) $data['product_wp_post_id'] : 0;

            $postId = wp_insert_post([
                'post_type'   => self::POST_TYPE,
                'post_status' => 'publish',
                'post_title'  => strtoupper((string) ($data['color'] ?? 'VARIANTE')),
                'post_parent' => $parentId,
            ], true);

            if ($postId instanceof WP_Error) {
                error_log('[VARIANT IMPORTER] insert failed: ' . $postId->get_error_message());
                return null;
            }
        }

        $postId = (int) $postId;
        if ($postId <= 0) {
            return null;
        }

        self::updateAcf($postId, $data, $variantId);
        self::updateTaxonomies($postId, $data);

        return $postId;
    }

    private static function findByVariantId(string $variantId): ?int
    {
        $q = new WP_Query([
            'post_type'      => self::POST_TYPE,
            'post_status'    => 'any',
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'meta_key'       => self::META_KEY,
            'meta_value'     => $variantId,
        ]);

        return $q->have_posts() ? (int) $q->posts[0] : null;
    }

    private static function updateAcf(int $postId, array $data, string $variantId): void
    {
        $writer = static function (string $key, mixed $value) use ($postId): void {
            if (function_exists('update_field')) {
                update_field($key, $value, $postId);
                return;
            }
            update_post_meta($postId, $key, $value);
        };

        $writer('fs_variant_id', $variantId);
        $writer('fs_product_id', (string) ($data['product_id'] ?? ''));
        $writer('fs_gtin', (string) ($data['gtin'] ?? ''));
        $writer('fs_colour_raw', is_scalar($data['color_raw'] ?? null) ? (string) ($data['color_raw'] ?? '') : '');
        $writer('fs_surface_raw', (string) ($data['surface'] ?? ''));
        $writer('fs_mpn', (string) ($data['mpn'] ?? ''));
        $writer('fs_size_eu', (string) ($data['size'] ?? ''));

        $price = isset($data['price']) ? (float) $data['price'] : null;
        $priceSale = isset($data['price_sale']) ? (float) $data['price_sale'] : null;

        $writer('fs_price', $price);
        $writer('fs_price_sale', $priceSale);

        $genderRaw = $data['gender'] ?? '';
        if (is_array($genderRaw)) {
            $genderRaw = implode('|', array_map('strval', $genderRaw));
        } elseif (!is_scalar($genderRaw)) {
            $genderRaw = '';
        }

        $writer('fs_gender_raw', (string) $genderRaw);
        $writer('fs_last_seen_at', current_time('mysql'));

        // Imágenes
        $imageMain = !empty($data['image_main']) ? trim((string) $data['image_main']) : '';
        $imagesRaw = !empty($data['images_raw']) ? trim((string) $data['images_raw']) : '';

        $images = [];
        if ($imageMain !== '') {
            $images[] = $imageMain;
        }

        if ($imagesRaw !== '') {
            foreach (explode('|', $imagesRaw) as $img) {
                $img = trim((string) $img);
                if ($img !== '') {
                    $images[] = $img;
                }
            }
        }

        if ($images) {
            $images = array_values(array_unique($images));
            $writer('fs_images', implode("\n", $images));
        }
    }

    private static function updateTaxonomies(int $postId, array $data): void
    {
        // COLOR
        if (!empty($data['color'])) {
            wp_set_object_terms(
                $postId,
                sanitize_text_field((string) $data['color']),
                'fs_color',
                false
            );
        }

        // AGE GROUP
        if (!empty($data['age_group'])) {
            wp_set_object_terms(
                $postId,
                sanitize_text_field((string) $data['age_group']),
                'fs_age_group',
                false
            );
        }

        // SUPERFICIE
        $normalizedSurface = self::normalizeSurface(
            (string) ($data['surface'] ?? ''),
            (string) ($data['title'] ?? '')
        );

        if ($normalizedSurface) {
            wp_set_object_terms($postId, $normalizedSurface, 'fs_superficie', false);
        }

        // ==========================================
        // CIERRE (desde título del feed)
        // ==========================================
        error_log('TITLE RAW: ' . print_r($data['title'] ?? 'NO TITLE', true));
        if (!empty($data['title'])) {
            $title = strtoupper((string) $data['title']);
            $closures = [];
            if (str_contains($title, 'VCO') || str_contains($title, 'VELCRO')) {
                $closures[] = 'velcro';
            }

            if (str_contains($title, 'LACE') || str_contains($title, 'LACES')) {
                $closures[] = 'cordones';
            }

            if (str_contains($title, 'ELASTIC')) {
                error_log('TITLE UPPER: ' . $title);
                error_log('HAS ELASTIC: ' . (str_contains($title, 'ELASTIC') ? 'YES' : 'NO'));
                $closures[] = 'elastic';
            }

            if (str_contains($title, 'SLIP ON') || str_contains($title, 'SLIP-ON')) {
                $closures[] = 'sin-cordones';
            }

            if (!empty($closures)) {
                wp_set_object_terms(
                    $postId,
                    array_unique($closures),
                    'fs_cierre',
                    false
                );
            }
        }

        // GÉNERO
        if (!empty($data['gender']) && is_array($data['gender'])) {
            wp_set_object_terms(
                $postId,
                array_map('sanitize_text_field', array_map('strval', $data['gender'])),
                'fs_genero',
                false
            );
        } elseif (!empty($data['gender'])) {
            wp_set_object_terms(
                $postId,
                sanitize_text_field((string) $data['gender']),
                'fs_genero',
                false
            );
        }

        // TIENDA
        if (!empty($data['merchant_name'])) {
            wp_set_object_terms(
                $postId,
                sanitize_text_field((string) $data['merchant_name']),
                'fs_tienda',
                false
            );
        }

        // TALLA
        if (!empty($data['size'])) {
            wp_set_object_terms(
                $postId,
                sanitize_text_field((string) $data['size']),
                'fs_talla_eu',
                false
            );
        }
    }

    private static function normalizeSurface(string $rawSurface, string $title): ?string
    {
        $raw = strtoupper($rawSurface);
        $t   = strtoupper($title);

        $hayIN  = str_contains($t, 'IN')  || str_contains($raw, 'IN');
        $hayOUT = str_contains($t, 'OUT') || str_contains($raw, 'OUT');
        $hayIC  = str_contains($t, 'IC')  || str_contains($raw, 'IC');
        $hayFG  = str_contains($raw, 'FG');
        $hayTurf = str_contains($t, 'TURF') || str_contains($raw, 'TURF');
        
        if ($hayTurf) {
            return 'turf';
        }

        if (($hayIN && $hayOUT) || str_contains($t, 'IN/OUT')) {
            return 'mixed';
        }

        if ($hayOUT || $hayFG) {
            return 'outdoor';
        }

        if ($hayIN || $hayIC) {
            return 'indoor';
        }

        return null;
    }
}