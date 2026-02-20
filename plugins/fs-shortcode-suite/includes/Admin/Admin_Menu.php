<?php
declare(strict_types=1);

namespace FS\ShortcodeSuite\Admin;

use FS\ShortcodeSuite\Admin\Pages\Dashboard_Page;
use FS\ShortcodeSuite\Admin\Pages\Grid_Page;
use FS\ShortcodeSuite\Admin\Pages\Search_Page;
use FS\ShortcodeSuite\Admin\Pages\Settings_Page;
use FS\ShortcodeSuite\Admin\Pages\System_Page;
use FS\ShortcodeSuite\Admin\Pages\Player_Types_Page;
use FS\ShortcodeSuite\Admin\Pages\Selector_Wizard_Page;
use FS\ShortcodeSuite\Admin\Pages\Product_Detail_Page;

defined('ABSPATH') || exit;

final class Admin_Menu {

    public function init(): void {
        add_action('admin_menu', [$this, 'register_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function register_menu(): void {

        /**
         * Menu principal SIN callback (solo contenedor)
         */
        add_menu_page(
            'FS Shortcode Suite',
            'FS Shortcodes',
            'manage_options',
            'fs-shortcode-suite',
            '__return_null', // 🔥 clave para evitar doble render
            'dashicons-screenoptions',
            58
        );

        /**
         * Submenu real Dashboard
         */
        add_submenu_page(
            'fs-shortcode-suite',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'fs-shortcode-suite',
            [new Dashboard_Page(), 'render']
        );

        add_submenu_page(
            'fs-shortcode-suite',
            'FS Grid',
            'FS Grid',
            'manage_options',
            'fs-shortcode-suite-grid',
            [new Grid_Page(), 'render']
        );

        add_submenu_page(
            'fs-shortcode-suite',
            'FS Search',
            'FS Search',
            'manage_options',
            'fs-shortcode-suite-search',
            [new Search_Page(), 'render']
        );

        add_submenu_page(
            'fs-shortcode-suite',
            'Size Guide',
            'Size Guide',
            'manage_options',
            'fs-shortcode-suite-size-guide',
            [new \FS\ShortcodeSuite\Admin\Pages\Size_Guide_Page(), 'render']
        );
        
        add_submenu_page(
            'fs-shortcode-suite',
            'Player Types',
            'Player Types',
            'manage_options',
            'fs-shortcode-suite-player-types',
            [new \FS\ShortcodeSuite\Admin\Pages\Player_Types_Page(), 'render']
        );
        
        add_submenu_page(
            'fs-shortcode-suite',
            'Selector Wizard',
            'Selector Wizard',
            'manage_options',
            'fs-shortcode-selector-wizard',
            [new \FS\ShortcodeSuite\Admin\Pages\Selector_Wizard_Page(), 'render']
        );
        
        add_submenu_page(
            'fs-shortcode-suite',
            'Product Detail',
            'Product Detail',
            'manage_options',
            'fs-shortcode-suite-product-detail',
            [new \FS\ShortcodeSuite\Admin\Pages\Product_Detail_Page(), 'render']
        );
        
        add_submenu_page(
            'fs-shortcode-suite',
            'Settings',
            'Settings',
            'manage_options',
            'fs-shortcode-suite-settings',
            [new Settings_Page(), 'render']
        );

        add_submenu_page(
            'fs-shortcode-suite',
            'System Info',
            'System Info',
            'manage_options',
            'fs-shortcode-suite-system',
            [new System_Page(), 'render']
        );
    }

    public function enqueue_assets(string $hook): void {

        if (strpos($hook, 'fs-shortcode-suite') === false) {
            return;
        }

        wp_enqueue_style(
            'fs-admin-style',
            FS_SC_SUITE_URL . 'includes/Admin/Assets/admin.css',
            [],
            FS_SC_SUITE_VERSION
        );

        wp_enqueue_script(
            'fs-admin-script',
            FS_SC_SUITE_URL . 'includes/Admin/Assets/admin.js',
            [],
            FS_SC_SUITE_VERSION,
            true
        );
    }
}
