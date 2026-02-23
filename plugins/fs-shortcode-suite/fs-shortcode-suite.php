<?php
declare(strict_types=1);

/**
 * Plugin Name: FS Shortcode Suite
 */

defined('ABSPATH') || exit;

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