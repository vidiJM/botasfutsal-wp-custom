<?php
declare(strict_types=1);

namespace FS\ShortcodeSuite\Core;

defined('ABSPATH') || exit;

final class Assets {

    public function init(): void {
        add_action('wp_enqueue_scripts', [$this, 'register']);
    }

    /**
     * Registro global de assets
     */
    public function register(): void {

        /*
        |--------------------------------------------------------------------------
        | GRID
        |--------------------------------------------------------------------------
        */

        wp_register_style(
            'fs-grid',
            FS_SC_SUITE_URL . 'public/css/grid.css',
            [],
            FS_SC_SUITE_VERSION
        );

        wp_register_script(
            'fs-grid',
            FS_SC_SUITE_URL . 'public/js/grid.js',
            [],
            FS_SC_SUITE_VERSION,
            true
        );

        /*
        |--------------------------------------------------------------------------
        | SEARCH
        |--------------------------------------------------------------------------
        */

        wp_register_style(
            'fs-search',
            FS_SC_SUITE_URL . 'public/css/search.css',
            [],
            FS_SC_SUITE_VERSION
        );

        wp_register_script(
            'fs-search',
            FS_SC_SUITE_URL . 'public/js/search.js',
            [],
            FS_SC_SUITE_VERSION,
            true
        );

        /*
        |--------------------------------------------------------------------------
        | SIZE GUIDE
        |--------------------------------------------------------------------------
        */

        wp_register_style(
            'fs-size-guide',
            FS_SC_SUITE_URL . 'public/css/size-guide.css',
            [],
            FS_SC_SUITE_VERSION
        );
        
        wp_register_script(
            'fs-size-guide',
            FS_SC_SUITE_URL . 'public/js/size-guide.js',
            [],
            FS_SC_SUITE_VERSION,
            true
        );

        // No registramos JS porque es contenido informativo estático.
    }

    /*
    |--------------------------------------------------------------------------
    | Enqueue GRID
    |--------------------------------------------------------------------------
    */

    public static function enqueue_grid(): void {

        wp_enqueue_style('fs-grid');
        wp_enqueue_script('fs-grid');
    }

    /*
    |--------------------------------------------------------------------------
    | Enqueue SEARCH
    |--------------------------------------------------------------------------
    */

    public static function enqueue_search(): void {

        wp_enqueue_style('fs-search');
        wp_enqueue_script('fs-search');
    }

    /*
    |--------------------------------------------------------------------------
    | Enqueue SIZE GUIDE
    |--------------------------------------------------------------------------
    */

    public static function enqueue_size_guide(): void {

        wp_enqueue_style('fs-size-guide');
        wp_enqueue_script('fs-size-guide');
    }
}
