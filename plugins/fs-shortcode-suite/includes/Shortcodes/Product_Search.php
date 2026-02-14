<?php
declare(strict_types=1);

/**
 * File: /includes/Shortcodes/Product_Search.php
 */

namespace FS\ShortcodeSuite\Shortcodes;

use FS\ShortcodeSuite\Core\Assets;

defined('ABSPATH') || exit;

final class Product_Search
{
    
    public function __construct()
    {
        add_shortcode('fs_search', [$this, 'render']);
    }

    /**
     * Render shortcode [fs_search]
     */
    public function render(array $atts = []): string
    {
        // Enqueue assets SOLO cuando se usa el shortcode
        Assets::enqueue_search();

        // Localizamos REST endpoint
        wp_localize_script(
            'fs-search',
            'FSSearchConfig',
            [
                'restUrl'   => esc_url_raw(rest_url('fs/v1/search')),
                'minLength' => 3,
            ]
        );

        ob_start();

        echo '<div class="fs-search" data-fs-search>
            <button
                class="fs-search-trigger"
                type="button"
                aria-haspopup="dialog"
                aria-expanded="false">
                <span class="fs-search-icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="20px" height="20px" viewBox="0 0 20 20" version="1.1">
                    <g id="surface1">
                    <path style=" stroke:none;fill-rule:nonzero;fill:rgb(51.764709%,80.000001%,8.627451%);fill-opacity:1;" d="M 15.007812 7.503906 C 15.007812 3.359375 11.648438 0 7.503906 0 C 3.359375 0 0 3.359375 0 7.503906 C 0 11.648438 3.359375 15.007812 7.503906 15.007812 C 11.648438 15.007812 15.007812 11.648438 15.007812 7.503906 Z M 7.503906 13.128906 C 4.398438 13.128906 1.875 10.605469 1.875 7.503906 C 1.875 4.398438 4.398438 1.875 7.503906 1.875 C 10.605469 1.875 13.132812 4.398438 13.132812 7.503906 C 13.132812 10.605469 10.605469 13.128906 7.503906 13.128906 Z M 7.503906 13.128906 "/>
                    <path style=" stroke:none;fill-rule:nonzero;fill:rgb(51.764709%,80.000001%,8.627451%);fill-opacity:1;" d="M 19.460938 16.808594 L 14.867188 12.214844 C 14.183594 13.28125 13.28125 14.183594 12.214844 14.867188 L 16.804688 19.460938 C 17.539062 20.191406 18.726562 20.191406 19.460938 19.460938 C 20.191406 18.726562 20.191406 17.539062 19.460938 16.808594 Z M 19.460938 16.808594 "/>
                    </g>
                    </svg>
                </span>
                <span class="fs-search-placeholder">Buscar</span>
            </button>
        </div>

        <div class="fs-search-overlay" data-fs-search-overlay aria-hidden="true" role="dialog" aria-modal="true">
    <div class="fs-search-panel">

        <div class="fs-search-header">

            <div class="fs-search-logo">';
               echo wp_get_attachment_image(41, "full");
            echo '</div>

            <input
                type="search"
                class="fs-search-input"
                placeholder="Buscar"
                aria-label="Buscar productos"
                autocomplete="off"
                spellcheck="false"
            >

            <button class="fs-search-close" type="button">
                Cancelar
            </button>

        </div>

        <div class="fs-search-body">
            <div class="fs-search-suggestions" data-fs-search-suggestions></div>
            <div class="fs-search-results" data-fs-search-results></div>
        </div>

    </div>
</div>';

        return (string) ob_get_clean();
    }
}
