<?php
/**
 * Plugin Name: FS Importer – Core
 * Description: Núcleo compartido para normalización y lógica de importación.
 * Version: 0.2.0
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

define('FS_IMPORTER_CORE_PATH', plugin_dir_path(__FILE__));
define('FS_IMPORTER_CORE_URL', plugin_dir_url(__FILE__));

$autoload = FS_IMPORTER_CORE_PATH . 'vendor/autoload.php';

if (!file_exists($autoload)) {
    add_action('admin_notices', static function () {
        echo '<div class="notice notice-error"><p>';
        echo '<strong>FS Importer Core:</strong> Dependencias no instaladas. Ejecuta <code>composer install</code>.';
        echo '</p></div>';
    });
    return;
}

require_once $autoload;

// Inicialización mínima del core
add_action('plugins_loaded', static function () {
    if (class_exists(\FS\ImporterCore\Core::class)) {
        \FS\ImporterCore\Core::init();
    }
});
