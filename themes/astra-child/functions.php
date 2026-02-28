<?php
function astra_child_enqueue_styles() {
    wp_enqueue_style(
        'astra-child-style',
        get_stylesheet_uri(),
        ['astra-theme-css'],
        wp_get_theme()->get('Version')
    );
}

add_action('wp_enqueue_scripts', 'astra_child_enqueue_styles');

add_filter('astra_breadcrumb_trail_items', function ($items) {

    if (is_post_type_archive('fs_producto')) {

        $home = $items[0];

        $items = [
            $home,
            '<span class="trail-end" aria-current="page">Zapatillas de Fútbol Sala</span>'
        ];
    }

    if (is_singular('fs_producto')) {

        $home = $items[0];

        $items = [
            $home,
            '<a href="' . get_post_type_archive_link('fs_producto') . '">Zapatillas de Fútbol Sala</a>',
            '<span class="trail-end" aria-current="page">' . get_the_title() . '</span>'
        ];
    }

    return $items;

});

add_filter('astra_breadcrumb_trail_args', function($args){
    $args['separator'] = '<span class="separator">›</span>';
    $args['show_browse'] = false;
    return $args;
});

add_action('astra_before_breadcrumb', function(){
    echo '<nav class="fs-breadcrumb" aria-label="Breadcrumb">';
});

add_action('astra_after_breadcrumb', function(){
    echo '</nav>';
});

/** ARCHVIVE PRODUCT **/
add_action('wp_head', function(){

    if (!is_post_type_archive('fs_producto') && !is_singular('fs_producto')) {
        return;
    }

    $items = [];

    $items[] = [
        "@type" => "ListItem",
        "position" => 1,
        "name" => "Inicio",
        "item" => home_url('/')
    ];

    $items[] = [
        "@type" => "ListItem",
        "position" => 2,
        "name" => "Zapatillas de Fútbol Sala",
        "item" => get_post_type_archive_link('fs_producto')
    ];

    if (is_singular('fs_producto')) {
        $items[] = [
            "@type" => "ListItem",
            "position" => 3,
            "name" => get_the_title(),
            "item" => get_permalink()
        ];
    }

    echo '<script type="application/ld+json">';
    echo wp_json_encode([
        "@context" => "https://schema.org",
        "@type" => "BreadcrumbList",
        "itemListElement" => $items
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    echo '</script>';

});

/** SINGLE PRODUCT **/
add_action('wp_head', function(){

    if (!is_singular('fs_producto')) {
        return;
    }

    global $post;
    $product_id = $post->ID;

    $name  = get_the_title($product_id);
    $url   = get_permalink($product_id);
    $image = get_post_meta($product_id, 'fs_image_main_url', true);

    // Marca
    $brand_terms = get_the_terms($product_id, 'fs_marca');
    $brand_name  = (!empty($brand_terms) && !is_wp_error($brand_terms))
        ? $brand_terms[0]->name
        : '';

    // 🔎 Obtener precio mínimo real (igual que haces en build_products)
    $min_price = null;
    $in_stock  = false;

    $variants = get_posts([
        'post_type'      => 'fs_variante',
        'post_parent'    => $product_id,
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'no_found_rows'  => true,
    ]);

    foreach ($variants as $variant_id) {

        $variant_global_id = get_post_meta($variant_id, 'fs_variant_id', true);

        if (!$variant_global_id) continue;

        $offers = get_posts([
            'post_type'      => 'fs_oferta',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'no_found_rows'  => true,
            'meta_query'     => [
                [
                    'key'     => 'fs_variant_id',
                    'value'   => $variant_global_id,
                ],
            ],
        ]);

        foreach ($offers as $offer_id) {

            $stock = get_post_meta($offer_id, 'fs_in_stock', true);
            if (!$stock) continue;

            $in_stock = true;

            $price_raw      = get_post_meta($offer_id, 'fs_price', true);
            $price_sale_raw = get_post_meta($offer_id, 'fs_price_sale', true);

            $price      = (float) str_replace(',', '.', $price_raw);
            $price_sale = (float) str_replace(',', '.', $price_sale_raw);

            $final = ($price_sale > 0) ? $price_sale : $price;

            if ($final > 0 && ($min_price === null || $final < $min_price)) {
                $min_price = $final;
            }
        }
    }

    if (!$min_price) return;

    $schema = [
        "@context" => "https://schema.org",
        "@type"    => "Product",
        "name"     => $name,
        "url"      => $url,
        "image"    => $image,
        "brand"    => [
            "@type" => "Brand",
            "name"  => $brand_name,
        ],
        "offers"   => [
            "@type"         => "Offer",
            "priceCurrency" => "EUR",
            "price"         => number_format($min_price, 2, '.', ''),
            "availability"  => $in_stock
                ? "https://schema.org/InStock"
                : "https://schema.org/OutOfStock",
            "url"           => $url,
        ],
    ];

    echo '<script type="application/ld+json">';
    echo wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    echo '</script>';

});