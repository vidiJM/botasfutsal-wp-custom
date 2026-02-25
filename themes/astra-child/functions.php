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

        // Eliminamos "Productos FS"
        array_pop($items);

        if (!empty($items)) {

            $last_key = array_key_last($items);
            $items[$last_key] = '<span class="trail-end">' 
                . strip_tags($items[$last_key]) 
                . '</span>';
        }
    }
    
    // Si estamos en single del CPT fs_producto
    if (is_singular('fs_producto')) {

        if (count($items) >= 3) {
            // Eliminamos el penúltimo ("Productos FS")
            array_splice($items, -2, 1);
        }

        // Convertimos el último en texto plano
        $last_key = array_key_last($items);
        $items[$last_key] = '<span class="trail-end">' 
            . strip_tags($items[$last_key]) 
            . '</span>';
    }

    return $items;
});
add_filter('astra_breadcrumb_trail_args', function($args){
    $args['separator'] = '<span class="separator">›</span>';
    return $args;
});
