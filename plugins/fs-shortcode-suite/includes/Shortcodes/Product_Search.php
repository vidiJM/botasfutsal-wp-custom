<?php
declare(strict_types=1);

namespace FS\ShortcodeSuite\Shortcodes;

use FS\ShortcodeSuite\Core\Assets;

defined('ABSPATH') || exit;

final class Product_Search
{
    public function __construct()
    {
        add_shortcode('fs_search', [$this, 'render']);
    }

    public function render(array $atts = []): string
    {
        Assets::enqueue_search();

        wp_localize_script(
            'fs-search',
            'FSSearchConfig',
            [
                'restUrl' => esc_url_raw(rest_url('fs/v1/search')),
            ]
        );

        ob_start();
        ?>

        <div class="fs-search" data-fs-search>
            <button
                class="fs-search-trigger"
                type="button"
                aria-haspopup="dialog"
                aria-expanded="false">
                <span class="fs-search-placeholder">
                    <?php esc_html_e('Buscar', 'fs-shortcode-suite'); ?>
                </span>
            </button>
        </div>

        <div class="fs-search-overlay"
             data-fs-search-overlay
             aria-hidden="true"
             role="dialog"
             aria-modal="true">

            <div class="fs-search-panel">

                <!-- HEADER -->
                <div class="fs-search-header">

                    <div class="fs-search-logo">
                        <?php echo wp_kses_post(wp_get_attachment_image(41, 'full')); ?>
                    </div>

                    <input
                        type="search"
                        class="fs-search-input"
                        placeholder="<?php esc_attr_e('Buscar', 'fs-shortcode-suite'); ?>"
                        aria-label="<?php esc_attr_e('Buscar productos', 'fs-shortcode-suite'); ?>"
                        autocomplete="off"
                        spellcheck="false"
                    >

                    <button class="fs-search-close" type="button">
                        <?php esc_html_e('Cancelar', 'fs-shortcode-suite'); ?>
                    </button>

                </div>

                <!-- BODY -->
                <div class="fs-search-body fs-search-layout">

                    <aside class="fs-search-sidebar">

                        <?php
                        $sections = [
                            [
                                'label'  => 'Ordenar',
                                'type'   => 'orderby',
                                'static' => true,
                                'items'  => [
                                    ['value' => 'price_asc',  'text' => 'Precio ↑'],
                                    ['value' => 'price_desc', 'text' => 'Precio ↓'],
                                    ['value' => 'newest',     'text' => 'Más nuevos'],
                                ],
                            ],
                            ['label' => 'Marcas',      'type' => 'marca'],
                            ['label' => 'Superficies', 'type' => 'superficie'],
                            ['label' => 'Género',      'type' => 'genero'],
                            ['label' => 'Tallas',      'type' => 'talla'],
                            ['label' => 'Colores',     'type' => 'color'],
                            ['label' => 'Precio',      'type' => 'price'],
                        ];
                        ?>

                        <?php foreach ($sections as $section) : ?>

                            <div class="fs-filter-section">

                                <button type="button" class="fs-filter-header">
                                    <?php echo esc_html($section['label']); ?>
                                </button>

                                <div class="fs-filter-options"

                                    <?php if (empty($section['static'])) : ?>
                                        data-dynamic-filter="<?php echo esc_attr($section['type']); ?>"
                                    <?php endif; ?>
                                >

                                    <?php if (!empty($section['static'])) : ?>
                                        <?php foreach ($section['items'] as $item) : ?>
                                            <button type="button"
                                                data-filter="orderby"
                                                data-value="<?php echo esc_attr($item['value']); ?>">
                                                <?php echo esc_html($item['text']); ?>
                                            </button>
                                        <?php endforeach; ?>
                                    <?php endif; ?>

                                </div>

                            </div>

                        <?php endforeach; ?>

                        <!-- Pills activas -->
                        <div class="fs-active-filters" data-fs-active-filters></div>

                    </aside>

                    <div class="fs-search-results-col">
                        <div class="fs-search-results" data-fs-search-results></div>
                    </div>

                </div>

            </div>
        </div>

        <?php
        return (string) ob_get_clean();
    }
}
