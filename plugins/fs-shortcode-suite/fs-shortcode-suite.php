<?php
declare(strict_types=1);

/**
 * Plugin Name: FS Shortcode Suite
 */

defined('ABSPATH') || exit;
add_action('init', function () {

    if (!isset($_GET['fs_run_classification'])) {
        return;
    }

    if (!current_user_can('manage_options')) {
        return;
    }

    $taxonomy = 'fs_caracteristica';
    $classified = 0;

    function fs_normalize($text) {
        $text = strtolower(strip_tags($text));
        $text = remove_accents($text);
        return $text;
    }

    $categories = [
        'velocidad' => ['liger','veloc','explosiv','reactiv','aceler','agil'],
        'control' => ['control','precisi','toque','dominio','balon'],
        'resistencia' => ['durabil','resisten','reforz','traccion','agarre'],
        'calidad-precio' => ['comodidad','confort','amortigu','equilibr','precio']
    ];

    $term_ids = [];

    foreach ($categories as $slug => $keywords) {
        $term = get_term_by('slug', $slug, $taxonomy);
        if ($term) {
            $term_ids[$slug] = (int) $term->term_id;
        }
    }

    $query = new WP_Query([
        'post_type' => 'fs_producto',
        'posts_per_page' => -1,
        'post_status' => 'publish',
    ]);

    while ($query->have_posts()) {
        $query->the_post();

        $post_id = get_the_ID();
        $raw = get_post_meta($post_id, 'fs_description_raw', true);

        if (!$raw) continue;

        $description = fs_normalize($raw);
        $scores = [];

        foreach ($categories as $slug => $keywords) {
            $scores[$slug] = 0;
            foreach ($keywords as $keyword) {
                if (str_contains($description, $keyword)) {
                    $scores[$slug]++;
                }
            }
        }

        arsort($scores);
        $top_slug = array_key_first($scores);

        if (isset($term_ids[$top_slug]) && $scores[$top_slug] > 0) {
            wp_set_object_terms($post_id, [$term_ids[$top_slug]], $taxonomy, false);
            $classified++;
        }
    }

    wp_reset_postdata();

    wp_die('Clasificación completada. Productos clasificados: ' . $classified);
});
/*
|--------------------------------------------------------------------------
| Constants
|--------------------------------------------------------------------------
*/

if (!defined('FS_SC_SUITE_PATH')) {
    define('FS_SC_SUITE_PATH', plugin_dir_path(__FILE__));
}

if (!defined('FS_SC_SUITE_URL')) {
    define('FS_SC_SUITE_URL', plugin_dir_url(__FILE__));
}

if (!defined('FS_SC_SUITE_VERSION')) {
    define('FS_SC_SUITE_VERSION', '1.0.0');
}

/*
|--------------------------------------------------------------------------
| Simple PSR-4 Autoloader (internal only)
|--------------------------------------------------------------------------
*/

if (!class_exists(\FS\ShortcodeSuite\Core\Loader::class)) {

    spl_autoload_register(function ($class) {

        $prefix = 'FS\\ShortcodeSuite\\';
    
        if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
            return;
        }
    
        $relative_class = substr($class, strlen($prefix));
        $relative_path  = str_replace('\\', '/', $relative_class) . '.php';
    
        // 1️⃣ Buscar en src/
        $file = FS_SC_SUITE_PATH . 'src/' . $relative_path;
    
        if (is_readable($file)) {
            require $file;
            return;
        }
    
        // 2️⃣ Buscar en includes/
        $file = FS_SC_SUITE_PATH . 'includes/' . $relative_path;
    
        if (is_readable($file)) {
            require $file;
            return;
        }
    });
}

/*
|--------------------------------------------------------------------------
| Boot Plugin
|--------------------------------------------------------------------------
*/

add_action('plugins_loaded', static function (): void {
    (new FS\ShortcodeSuite\Core\Loader())->init();
});