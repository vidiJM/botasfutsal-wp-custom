<?php
declare(strict_types=1);

namespace FS\ShortcodeSuite\Tools;

defined('ABSPATH') || exit;

final class Caracteristica_Mapper
{
    
    public static function map_single(int $product_id): void
{
    $rules = [
        'velocidad' => ['liger','reactiv','explosiv','rapidez','veloc','agil'],
        'control' => ['piel','tacto','precision','control','balon','toque'],
        'resistencia' => ['durabil','resistent','refuerz','proteccion','estabilidad','amortigu','impacto','traccion','agarre'],
        'calidad-precio' => ['econom','oferta','precio competitivo']
    ];

    $description = (string) get_field('fs_description_raw', $product_id);

    if (!$description) {
        return;
    }

    $description = html_entity_decode($description, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $description = wp_strip_all_tags($description);
    $description = mb_strtolower($description);
    $description = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $description);

    $scores = [];

    foreach ($rules as $term => $keywords) {
        $scores[$term] = 0;

        foreach ($keywords as $word) {
            if (str_contains($description, $word)) {
                $scores[$term]++;
            }
        }
    }

    arsort($scores);
    $top = array_key_first($scores);

    if ($scores[$top] > 0) {
        wp_set_post_terms(
            $product_id,
            [$top],
            'fs_caracteristica',
            false
        );
    }
}
    public static function run(): void
    {
        $products = get_posts([
            'post_type'      => 'fs_producto',
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ]);

        if (!$products) {
            return;
        }

        $rules = [
            'velocidad' => [
                'liger', 'reactiv', 'explosiv', 'rapidez', 'veloc', 'agil'
            ],
            'control' => [
                'piel', 'tacto', 'precision', 'control', 'balon', 'toque'
            ],
            'resistencia' => [
                'durabil', 'resistent', 'refuerz', 'proteccion',
                'estabilidad', 'amortigu', 'impacto', 'traccion', 'agarre'
            ],
            'calidad-precio' => [
                'econom', 'oferta', 'precio competitivo'
            ]
        ];

        foreach ($products as $product_id) {

            $description = (string) get_field('fs_description_raw', $product_id);

            if (!$description) {
                continue;
            }

            // 1️⃣ Decodificar HTML escapado (&lt; etc)
            $description = html_entity_decode(
                $description,
                ENT_QUOTES | ENT_HTML5,
                'UTF-8'
            );

            // 2️⃣ Eliminar etiquetas HTML
            $description = wp_strip_all_tags($description);

            // 3️⃣ Normalizar
            $description = mb_strtolower($description);
            $description = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $description);

            if (!$description) {
                continue;
            }

            $scores = [];

            foreach ($rules as $term => $keywords) {

                $scores[$term] = 0;

                foreach ($keywords as $word) {
                    if (str_contains($description, $word)) {
                        $scores[$term]++;
                    }
                }
            }

            arsort($scores);
            $top = array_key_first($scores);

            if (!$top || $scores[$top] === 0) {
                continue;
            }

            if (!taxonomy_exists('fs_caracteristica')) {
                continue;
            }

            wp_set_post_terms(
                $product_id,
                ['resistencia'],
                'fs_caracteristica',
                false
            );
        }
    }
}