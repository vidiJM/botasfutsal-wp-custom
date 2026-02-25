<?php
declare(strict_types=1);

namespace FS\ShortcodeSuite\Core;

use FS\ShortcodeSuite\Admin\Admin_Menu;

// Data / Services
use FS\ShortcodeSuite\Data\Repository\Product_Repository;
use FS\ShortcodeSuite\Data\Builders\Grid_Dataset_Builder;
use FS\ShortcodeSuite\Data\Services\Grid_Service;
use FS\ShortcodeSuite\Data\Services\Search_Service;

// Shortcodes
use FS\ShortcodeSuite\Shortcodes\Product_Grid;
use FS\ShortcodeSuite\Shortcodes\Product_Search;
use FS\ShortcodeSuite\Shortcodes\Size_Guide;
use FS\ShortcodeSuite\Shortcodes\Player_Types;
use FS\ShortcodeSuite\Shortcodes\Selector_Wizard;
use FS\ShortcodeSuite\Shortcodes\Product_Detail;

// REST Controllers (ajusta si tu namespace/carpeta difiere)
use FS\ShortcodeSuite\REST\Grid_Controller;
use FS\ShortcodeSuite\REST\Search_Controller;
use FS\ShortcodeSuite\REST\Selector_Wizard_Controller;
use FS\ShortcodeSuite\REST\Product_Controller;

defined('ABSPATH') || exit;

final class Loader
{
    public function init(): void
    {
        // Assets global registration (pero enqueue solo bajo demanda)
        $assets = new Assets();
        $assets->init();

        // Sistemas
        $this->boot_grid_system();
        $this->boot_search_system();

        // Shortcodes “sueltos”
        $this->boot_misc_shortcodes();

        // Products AJAX
        $this->boot_product_ajax();
        
        // Admin
        $this->boot_admin();
    }

    /**
     * GRID: requiere servicio
     */
    private function boot_grid_system(): void
    {
        $repository = new Product_Repository();
        $builder    = new Grid_Dataset_Builder($repository);
        $cache      = new Cache_Manager();
    
        $service = new Grid_Service($repository, $builder, $cache);
    
        // Shortcode
        new Product_Grid($service);
    
        // REST Grid (si ya existe)
        add_action('rest_api_init', static function () use ($service): void {
            $controller = new Grid_Controller($service);
            $controller->register_routes();
        });
    
        // 👉 REST Products (nuevo)
        add_action('rest_api_init', static function (): void {
            $controller = new \FS\ShortcodeSuite\REST\Product_Controller();
            $controller->register_routes();
        });
    }

    /**
     * SEARCH: si tu shortcode Search no necesita service, NO se le pasa.
     * Si el Search_Service solo se usa en el controller, lo dejamos ahí.
     */
    private function boot_search_system(): void
    {
        // Shortcode [fs_search]
        // (En tu código actual Product_Search registra el shortcode en __construct)
        new Product_Search();

        // REST /fs/v1/search
        $service = new Search_Service();

        add_action('rest_api_init', static function () use ($service): void {
            $controller = new Search_Controller($service);
            $controller->register_routes();
        });
    }

    /**
     * Shortcodes que pueden ser register() o constructor-based.
     * Los registramos de forma defensiva para no romper si cambia el patrón.
     */
    private function boot_misc_shortcodes(): void
    {
        $this->register_shortcode_object(new Size_Guide());
        $this->register_shortcode_object(new Player_Types());
        $this->register_shortcode_object(new Selector_Wizard());
        $this->register_shortcode_object(new Product_Detail());

        // REST Wizard (si existe en tu plugin)
        add_action('rest_api_init', function (): void {
            // Wizard usa Search_Service
            $service = new Search_Service();
            $controller = new Selector_Wizard_Controller($service);
            $controller->register_routes();
        });
    }

    /**
     * Admin menu
     */
    private function boot_admin(): void
    {
        if (!is_admin()) {
            return;
        }

        $admin = new Admin_Menu();
        $admin->init();
    }

    private function boot_product_ajax(): void
    {
        add_action('rest_api_init', function (): void {
            $controller = new Product_Controller();
            $controller->register_routes();
        });
    }
    /**
     * Compatibilidad: algunas clases usan register(), otras registran en __construct.
     * Si existe register() lo llamamos.
     */
    private function register_shortcode_object(object $obj): void
    {
        if (method_exists($obj, 'register')) {
            $obj->register();
        }
    }
}