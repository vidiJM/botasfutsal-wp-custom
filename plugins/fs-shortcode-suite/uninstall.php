<?php
/**
 * Uninstall file for FS Shortcode Suite
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/**
 * SAFETY:
 * We do NOT delete CPT content (fs_producto, fs_variante, fs_oferta)
 * because those may belong to another system (importer).
 *
 * This uninstall only removes:
 * - Plugin options
 * - Transients
 * - Cache entries
 * - Custom plugin settings
 */

/* ==============================
   DELETE OPTIONS
============================== */

$options = [
    'fs_shortcode_suite_settings',
    'fs_shortcode_suite_version',
    'fs_shortcode_suite_cache_enabled',
];

foreach ($options as $option) {
    delete_option($option);
    delete_site_option($option);
}

/* ==============================
   DELETE TRANSIENTS
============================== */

global $wpdb;

$wpdb->query(
    "DELETE FROM {$wpdb->options}
     WHERE option_name LIKE '_transient_fs_%'
     OR option_name LIKE '_transient_timeout_fs_%'"
);

/* ==============================
   CLEAR OBJECT CACHE (if any)
============================== */

if (function_exists('wp_cache_flush')) {
    wp_cache_flush();
}