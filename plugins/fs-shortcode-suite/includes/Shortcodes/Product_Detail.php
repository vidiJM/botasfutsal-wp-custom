<?php
declare(strict_types=1);

namespace FS\ShortcodeSuite\Shortcodes;

use FS\ShortcodeSuite\Core\Assets;
use WP_Query;

if (!defined('ABSPATH')) {
    exit;
}

final class Product_Detail
{
    public function __construct()
    {
        add_shortcode('fs_product_detail', [$this, 'render']);
    }

    public function render(): string
    {
        if (!is_singular('fs_producto')) {
            return '';
        }

        global $post;

        if (!$post instanceof \WP_Post) {
            return '';
        }

        $product_id = (int) $post->ID;

        $data = $this->build_product_data($product_id);

        if (empty($data['colors'])) {
            return '';
        }

        Assets::enqueue_product_detail();

        wp_add_inline_script(
            'fs-product-detail',
            'window.FS_PRODUCT_DATA = ' . wp_json_encode($data) . ';',
            'before'
        );

        ob_start();
        ?>

        <section class="fs-product-detail">

            <div class="fs-product-detail__gallery">
                <div class="fs-product-detail__thumbs"></div>

                <div class="fs-product-detail__main-wrapper">
                    <button type="button" class="fs-product-detail__nav fs-product-detail__nav--prev">‹</button>
                    <img src="" class="fs-product-detail__main-image" alt="">
                    <button type="button" class="fs-product-detail__nav fs-product-detail__nav--next">›</button>
                </div>
            </div>

            <div class="fs-product-detail__info">
                <h1 class="fs-product-detail__title">
                    <?php echo esc_html(get_the_title($product_id)); ?>
                </h1>

                <div class="fs-product-detail__price"></div>
                <div class="fs-product-detail__colors"></div>
                <div class="fs-product-detail__sizes"></div>

                <a href="#"
                   target="_blank"
                   rel="noopener"
                   class="fs-product-detail__cta">
                    Ir a tienda
                </a>
            </div>

            <div class="fs-product-detail__description"></div>

        </section>
        
    <?php
        // ===============================
        // SCHEMA PRODUCT (AggregateOffer)
        // ===============================
        
        $min_price = null;
        $max_price = null;
        $offer_count = 0;
        $images = [];
        
        if (!empty($data['colors'])) {
        
            foreach ($data['colors'] as $color => $color_data) {
        
                if (!empty($color_data['price'])) {
        
                    $price = (float) $color_data['price'];
        
                    if ($min_price === null || $price < $min_price) {
                        $min_price = $price;
                    }
        
                    if ($max_price === null || $price > $max_price) {
                        $max_price = $price;
                    }
        
                    $offer_count++;
                }
        
                if (!empty($color_data['images'][0])) {
                    $images[] = $color_data['images'][0];
                }
            }
        }
        
        if ($min_price !== null) {
        
            $schema = [
                "@context" => "https://schema.org",
                "@type"    => "Product",
                "name"     => get_the_title($product_id),
                "url"      => get_permalink($product_id),
                "image"    => array_values(array_unique($images)),
                "offers"   => [
                    "@type"         => "AggregateOffer",
                    "priceCurrency" => "EUR",
                    "lowPrice"      => number_format($min_price, 2, '.', ''),
                    "highPrice"     => number_format($max_price, 2, '.', ''),
                    "offerCount"    => $offer_count,
                    "availability"  => "https://schema.org/InStock"
                ]
            ];
        
            echo '<script type="application/ld+json">';
            echo wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            echo '</script>';
        }
        ?>
        
        <?php
        return (string) ob_get_clean();
    }

    private function build_product_data(int $product_id): array
    {
        $product_code = get_field('fs_product_id', $product_id);
    
        if (!$product_code) {
            return [];
        }
    
        /*
         * =========================================
         * 0️⃣ MARCA (tax fs_marca en fs_producto)
         * =========================================
         */
    
        $brand_slug = null;
        $brand_name = null;
    
        $brand_terms = wp_get_post_terms($product_id, 'fs_marca');
    
        if (!empty($brand_terms) && !is_wp_error($brand_terms)) {
            $brand_slug = $brand_terms[0]->slug;
            $brand_name = $brand_terms[0]->name;
        }
    
        /*
         * =========================================
         * 1️⃣ VARIANTES
         * =========================================
         */
    
        $variants_query = new WP_Query([
            'post_type'      => 'fs_variante',
            'posts_per_page' => -1,
            'meta_key'       => 'fs_product_id',
            'meta_value'     => $product_code,
            'fields'         => 'ids',
            'no_found_rows'  => true,
        ]);
    
        if (empty($variants_query->posts)) {
            return [];
        }
    
        $variant_map = [];
    
        foreach ($variants_query->posts as $variant_post_id) {
            $external_variant_id = get_field('fs_variant_id', $variant_post_id);
            if ($external_variant_id) {
                $variant_map[$external_variant_id] = $variant_post_id;
            }
        }
    
        if (empty($variant_map)) {
            return [];
        }
    
        /*
         * =========================================
         * 2️⃣ OFERTAS CON STOCK
         * =========================================
         */
    
        $offers_query = new WP_Query([
            'post_type'      => 'fs_oferta',
            'posts_per_page' => -1,
            'meta_query'     => [
                [
                    'key'     => 'fs_variant_id',
                    'value'   => array_keys($variant_map),
                    'compare' => 'IN'
                ],
                [
                    'key'   => 'fs_in_stock',
                    'value' => '1'
                ]
            ],
            'fields'        => 'ids',
            'no_found_rows' => true,
        ]);
    
        $colors = [];
    
        foreach ($offers_query->posts as $offer_id) {
    
            $variant_external_id = get_field('fs_variant_id', $offer_id);
    
            if (!isset($variant_map[$variant_external_id])) {
                continue;
            }
    
            $variant_post_id = $variant_map[$variant_external_id];
    
            $color_terms = wp_get_post_terms($variant_post_id, 'fs_color');
            if (empty($color_terms)) {
                continue;
            }
    
            $color_slug = $color_terms[0]->slug;
    
            if (!isset($colors[$color_slug])) {
    
                $variant_description = get_field('fs_description_raw', $variant_post_id);
                if (!$variant_description) {
                    $variant_description = get_field('fs_description_raw', $product_id);
                }
    
                $colors[$color_slug] = [
                    'images'      => [],
                    'sizes'       => [],
                    'price'       => null,
                    'shop_url'    => null,
                    'description' => $this->sanitize_description($variant_description),
                ];
            }
    
            $images_raw = (string) get_field('fs_images', $variant_post_id);
    
            if ($images_raw !== '') {
                $parts = preg_split('/[\r\n,]+/', $images_raw);
    
                foreach ($parts as $url) {
                    $url = trim($url);
                    if ($url !== '' && filter_var($url, FILTER_VALIDATE_URL)) {
                        $colors[$color_slug]['images'][] = esc_url_raw($url);
                    }
                }
            }
    
            $size  = get_field('fs_size_eu', $offer_id);
            $price = (float) get_field('fs_price', $offer_id);
            $url   = get_field('fs_url', $offer_id);
    
            if ($size) {
                $colors[$color_slug]['sizes'][] = $size;
            }
    
            if (
                is_null($colors[$color_slug]['price']) ||
                $price < $colors[$color_slug]['price']
            ) {
                $colors[$color_slug]['price']    = $price;
                $colors[$color_slug]['shop_url'] = $url;
            }
        }
    
        foreach ($colors as $slug => $data) {
            $colors[$slug]['images'] = array_values(array_unique($data['images']));
            $colors[$slug]['sizes']  = array_values(array_unique($data['sizes']));
        }
    
        return [
            'brand_slug' => $brand_slug,
            'brand_name' => $brand_name,
            'colors'     => $colors,
        ];
    }

    /**
     * Sanitiza y formatea descripción SIN romper HTML bueno.
     * Flujo:
     *  1) decode entities si vienen escapadas
     *  2) si hay HTML estructurado => wp_kses + limpieza ligera
     *  3) si hay bullets => reconstruir bullets (soporta líneas partidas)
     *  4) si es texto plano => motor editorial (intelligent_format)
     */
    private function sanitize_description(?string $description): string
    {
        if (!$description) {
            return '';
        }
    
        $description = trim((string) $description);
    
        // Decodificar si viene escapado (muy común en feeds)
        if (strpos($description, '&lt;') !== false || strpos($description, '&gt;') !== false) {
            $description = html_entity_decode($description, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $description = trim($description);
        }
    
        // Quitar atributos class para evitar basura visual heredada del feed
        $description = preg_replace('/\sclass="[^"]*"/i', '', $description);
    
        // 1) Si ya trae HTML “bueno”, lo respetamos
        if (preg_match('/<(p|ul|ol|li|h[1-6])\b/i', $description)) {
            return $this->sanitize_html_description($description);
        }
    
        // Normalización básica del texto plano antes de detectar bullets
        $plain = $this->normalize_plaintext($description);
    
        // 2) Bullets clásicos: "• foo" o "- foo"
        if (preg_match('/(^|\n)\s*[•\-]\s+/u', $plain)) {
            return $this->format_bullet_description($plain);
        }
    
        // 3) Texto plano => motor editorial premium
        return $this->intelligent_format($plain);
    }
    
    /**
     * Sanitizado de HTML: conservamos estructura y evitamos que el feed meta cosas raras.
     */
    private function sanitize_html_description(string $html): string
    {
        $html = trim($html);
    
        // Normaliza saltos
        $html = str_replace(["\r\n", "\r"], "\n", $html);
    
        $allowed_tags = [
            'p'      => [],
            'ul'     => [],
            'ol'     => [],
            'li'     => [],
            'br'     => [],
            'strong' => [],
            'b'      => [],
            'em'     => [],
            'h2'     => [],
            'h3'     => [],
            'h4'     => [],
        ];
    
        $html = wp_kses($html, $allowed_tags);
    
        // Evitar títulos vacíos o saltos excesivos
        $html = preg_replace('/<p>\s*<\/p>/i', '', $html);
        $html = preg_replace('/(\n\s*){3,}/', "\n\n", $html);
    
        return trim($html);
    }
    
    /**
     * Bullets robustos:
     * - Une líneas de continuación dentro del MISMO bullet
     *   Ej:
     *   • Parte superior
     *     en PU
     *   => "Parte superior en PU"
     */
    private function format_bullet_description(string $text): string
    {
        $text = trim($text);
        if ($text === '') {
            return '';
        }
    
        $text = str_replace(["\r\n", "\r"], "\n", $text);
    
        $lines = preg_split('/\n+/u', $text) ?: [];
        $items = [];
        $current = '';
    
        foreach ($lines as $line) {
            $raw = trim($line);
            if ($raw === '') {
                continue;
            }
    
            // Nueva viñeta
            if (preg_match('/^[•\-]\s+(.*)$/u', $raw, $m)) {
                // flush anterior
                if ($current !== '') {
                    $items[] = $this->normalize_sentence($current);
                }
                $current = trim((string) $m[1]);
                continue;
            }
    
            // Línea de continuación (misma viñeta)
            if ($current !== '') {
                // Evita duplicar espacios y arregla palabras pegadas
                $raw = preg_replace('/\s+/', ' ', $raw);
                $current .= ' ' . $raw;
                $current = trim($current);
                continue;
            }
    
            // Si no hay current, lo tratamos como narrativa
            $items[] = $this->normalize_sentence($raw);
        }
    
        // flush final
        if ($current !== '') {
            $items[] = $this->normalize_sentence($current);
        }
    
        $items = $this->unique_keep_order(array_filter($items));
    
        // Si realmente eran bullets => ul, si no => párrafos
        if (count($items) >= 2) {
            $html = '<ul>';
            foreach ($items as $it) {
                $html .= '<li>' . esc_html($it) . '</li>';
            }
            $html .= '</ul>';
            return $html;
        }
    
        // 1 ítem: mejor como párrafo
        return '<div class="fs-product-detail__block"><p>' . esc_html($items[0] ?? '') . '</p></div>';
    }
    
    /**
     * Motor editorial: segmenta texto plano en:
     * - narrativa (p)
     * - secciones (h3 + ul)
     * - detecta subtítulos tipo "tacto y control: piel premium" => h3 + p
     * - extrae listas inline con guiones
     */
    private function intelligent_format(string $text): string
    {
        $text = $this->normalize_plaintext($text);
        if ($text === '') {
            return '';
        }
    
        // EXTRA: si vienen “bloques” separados con dobles saltos
        $blocks = preg_split("/\n{2,}/", $text) ?: [$text];
        $blocks = array_values(array_filter(array_map('trim', $blocks)));
    
        $narrative = [];
        $features = [];
        $composition = [];
        $sole = [];
        $season = [];
    
        $html = '';
    
        foreach ($blocks as $block) {
    
            // 1) Detectar “título: subtítulo” como heading editorial
            // Ej: "tacto y control insuperables: piel de vacuno premium"
            if ($this->looks_like_editorial_heading($block)) {
                [$h, $rest] = $this->split_heading_block($block);
    
                if ($h !== '') {
                    $html .= '<h3 class="fs-product-detail__subtitle">' . esc_html($this->normalize_title($h)) . '</h3>';
                }
    
                if ($rest !== '') {
                    // El resto lo procesamos como narrativa normal (puede ser largo)
                    $sentences = $this->split_sentences($rest);
                    $html .= '<div class="fs-product-detail__block">';
                    foreach ($sentences as $s) {
                        $s = $this->normalize_sentence($s);
                        if ($s !== '') {
                            $html .= '<p>' . esc_html($s) . '</p>';
                        }
                    }
                    $html .= '</div>';
                }
    
                continue;
            }
    
            // 2) Detectar listas inline con guiones: "Características- ... - ... - ..."
            $inline = $this->split_inline_dash_list($block);
            if (count($inline) >= 4) {
                foreach ($inline as $item) {
                    $this->route_sentence_to_section($item, $narrative, $features, $composition, $sole, $season);
                }
                continue;
            }
    
            // 3) Frases normales
            $sentences = $this->split_sentences($block);
            foreach ($sentences as $s) {
                $this->route_sentence_to_section($s, $narrative, $features, $composition, $sole, $season);
            }
        }
    
        // De-dup sin perder orden
        $narrative   = $this->unique_keep_order($narrative);
        $features    = $this->unique_keep_order($features);
        $composition = $this->unique_keep_order($composition);
        $sole        = $this->unique_keep_order($sole);
        $season      = $this->unique_keep_order($season);
    
        // Render: narrativa primero
        if (!empty($narrative)) {
            $html .= '<div class="fs-product-detail__block">';
            foreach ($narrative as $p) {
                $html .= '<p>' . esc_html($p) . '</p>';
            }
            $html .= '</div>';
        }
    
        // Render secciones
        $html .= $this->render_section_list('Características', $features);
        $html .= $this->render_section_list('Composición', $composition);
        $html .= $this->render_section_list('Suela', $sole);
        $html .= $this->render_section_list('Temporada', $season);
    
        // Fallback: si nada cuajó (raro), render básico
        if (trim($html) === '') {
            return '<div class="fs-product-detail__block"><p>' . esc_html($text) . '</p></div>';
        }
    
        return $html;
    }
    
    /**
     * Normaliza texto plano (sin bajar todo a minúsculas).
     * OJO: aquí arreglamos saltos absurdos y frases pegadas sin destruir nombres propios.
     */
    private function normalize_plaintext(string $text): string
    {
        $text = trim($text);
        if ($text === '') {
            return '';
        }
    
        $text = str_replace(["\r\n", "\r"], "\n", $text);
    
        // Quitar dobles espacios
        $text = preg_replace('/[ \t]+/u', ' ', $text);
    
        // Arreglar frases pegadas: "juego.características" => "juego. Características"
        $text = preg_replace('/([a-záéíóúñ])\.([a-záéíóúñ])/iu', '$1. $2', $text);
    
        // Espacio tras puntuación
        $text = preg_replace('/([.!?;:])([^\s"”’])/u', '$1 $2', $text);
    
        // Reducir saltos brutales
        $text = preg_replace("/\n{3,}/u", "\n\n", $text);
    
        return trim($text);
    }
    
    /**
     * Split de frases robusto y estable.
     */
    private function split_sentences(string $text): array
    {
        $text = trim($text);
        if ($text === '') {
            return [];
        }
    
        // Cortes por saltos primero
        $parts = preg_split("/\n+/u", $text) ?: [$text];
    
        $out = [];
        foreach ($parts as $p) {
            $p = trim($p);
            if ($p === '') continue;
    
            $chunks = preg_split('/(?<=[.!?])\s+(?=[\p{L}\d])/u', $p) ?: [$p];
            foreach ($chunks as $c) {
                $c = trim($c);
                if ($c !== '') {
                    $out[] = $c;
                }
            }
        }
    
        return $out;
    }
    
    /**
     * Divide listas inline por separador " - " (no guiones internos).
     */
    private function split_inline_dash_list(string $text): array
    {
        $text = trim($text);
        if ($text === '') {
            return [];
        }
    
        $text = str_replace(["–", "—"], "-", $text);
    
        // Split por guión separador real
        $items = preg_split('/\s-\s+/u', $text) ?: [$text];
        $items = array_values(array_filter(array_map('trim', $items)));
    
        // Segunda pasada si venía "foo- bar"
        if (count($items) === 1 && preg_match('/[a-záéíóúñ]\s*-\s*[a-záéíóúñ]/iu', $text)) {
            $items = preg_split('/\s*-\s*/u', $text) ?: [$text];
            $items = array_values(array_filter(array_map('trim', $items)));
        }
    
        // Limpiar prefijos tipo "Características-"
        $items = array_map(function ($it) {
            $it = preg_replace('/^caracter[ií]sticas\s*:?\s*/iu', '', $it);
            $it = preg_replace('/^caracter[ií]sticas\s*/iu', '', $it);
            return trim($it);
        }, $items);
    
        return array_values(array_filter($items));
    }
    
    /**
     * Clasifica una frase en la sección adecuada sin meter tochos en <li>.
     */
    private function route_sentence_to_section(
        string $sentence,
        array &$narrative,
        array &$features,
        array &$composition,
        array &$sole,
        array &$season
    ): void {
    
        $sentence = $this->normalize_sentence($sentence);
        if ($sentence === '') return;
    
        $lower = mb_strtolower($sentence);
    
        // Muy largo => párrafo, no bullet
        $isVeryLong = mb_strlen($sentence) > 220;
    
        // COMPOSICIÓN
        if (preg_match('/\b\d{1,3}\s*%\b/u', $sentence) || str_contains($lower, 'composición')) {
            if ($isVeryLong) $narrative[] = $sentence;
            else $composition[] = $sentence;
            return;
        }
    
        // SUELA / TRACCIÓN
        if (str_contains($lower, 'suela') || str_contains($lower, 'tracción') || str_contains($lower, 'agarre')) {
            if ($isVeryLong) $narrative[] = $sentence;
            else $sole[] = $sentence;
            return;
        }
    
        // TEMPORADA / LANZAMIENTO / AÑO
        if (str_contains($lower, 'temporada') || str_contains($lower, 'lanzamiento') || preg_match('/\b20\d{2}\b/u', $sentence)) {
            if ($isVeryLong) $narrative[] = $sentence;
            else $season[] = $sentence;
            return;
        }
    
        // FEATURES (solo si es bullet-friendly)
        $featureHint = (
            str_contains($lower, 'sistema') ||
            str_contains($lower, 'ideal') ||
            str_contains($lower, 'diseño') ||
            str_contains($lower, 'ajuste') ||
            str_contains($lower, 'material') ||
            str_contains($lower, 'protección') ||
            str_contains($lower, 'textur') ||
            str_contains($lower, 'recomendad') ||
            str_contains($lower, 'superficie') ||
            str_contains($lower, 'indoor') ||
            str_contains($lower, 'turf')
        );
    
        if ($featureHint && !$isVeryLong) {
            $features[] = $sentence;
            return;
        }
    
        // NARRATIVA
        $narrative[] = $sentence;
    }
    
    /**
     * Render secciones.
     */
    private function render_section_list(string $title, array $items): string
    {
        $items = array_values(array_filter($items));
        if (empty($items)) {
            return '';
        }
    
        $html = '<h3 class="fs-product-detail__subtitle">' . esc_html($title) . '</h3><ul>';
        foreach ($items as $it) {
            $html .= '<li>' . esc_html($it) . '</li>';
        }
        $html .= '</ul>';
    
        return $html;
    }
    
    /**
     * De-dup manteniendo orden.
     */
    private function unique_keep_order(array $items): array
    {
        $out = [];
        $seen = [];
        foreach ($items as $it) {
            $it = trim((string) $it);
            if ($it === '') continue;
    
            $k = mb_strtolower($it);
            if (isset($seen[$k])) continue;
    
            $seen[$k] = true;
            $out[] = $it;
        }
        return $out;
    }
    
    /**
     * Normaliza oración SIN convertirla a “title case” (para no romper marcas/nombres).
     * Solo asegura:
     * - espacios
     * - primera letra mayúscula UTF-8 si venía todo en minúscula
     */
    private function normalize_sentence(string $text): string
    {
        $text = trim($text);
        if ($text === '') return '';
    
        $text = preg_replace('/\s+/u', ' ', $text);
        $text = preg_replace('/([.!?;:])([^\s"”’])/u', '$1 $2', $text);
    
        // Si viene “todo en minúsculas”, subimos solo la primera letra
        // (heurística: si no hay ninguna mayúscula)
        if (!preg_match('/\p{Lu}/u', $text)) {
            $first = mb_substr($text, 0, 1);
            $rest  = mb_substr($text, 1);
            $text  = mb_strtoupper($first) . $rest;
        }
    
        return $text;
    }
    
    /**
     * Heurística: bloque tipo "algo: algo" que parece heading editorial.
     */
    private function looks_like_editorial_heading(string $block): bool
    {
        $block = trim($block);
        if ($block === '') return false;
    
        // Debe tener ":" y el prefijo no puede ser larguísimo
        if (!str_contains($block, ':')) return false;
    
        [$h, $rest] = $this->split_heading_block($block);
        if ($h === '' || $rest === '') return false;
    
        // Heading corto (estilo Nike)
        return mb_strlen($h) <= 60;
    }
    
    /**
     * Split "heading: rest" (solo primer ":")
     */
    private function split_heading_block(string $block): array
    {
        $pos = mb_strpos($block, ':');
        if ($pos === false) {
            return ['', $block];
        }
    
        $h = trim(mb_substr($block, 0, $pos));
        $rest = trim(mb_substr($block, $pos + 1));
    
        return [$h, $rest];
    }
    
    /**
     * Título: capitaliza primera letra, sin reventar nombres propios.
     */
    private function normalize_title(string $title): string
    {
        $title = trim($title);
        if ($title === '') return '';
    
        // Min: si todo venía en minúscula, subir primera
        if (!preg_match('/\p{Lu}/u', $title)) {
            $first = mb_substr($title, 0, 1);
            $rest  = mb_substr($title, 1);
            $title = mb_strtoupper($first) . $rest;
        }
    
        return $title;
    }

}
