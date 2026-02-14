<?php
declare(strict_types=1);

namespace FS\ShortcodeSuite\Admin\Pages;

defined('ABSPATH') || exit;

final class Size_Guide_Page {

    public function render(): void {
        ?>
        <div class="wrap fs-admin-wrap">

            <div class="fs-admin-header fs-admin-header--brand">
                <div class="fs-admin-header__title">
                    <h1>Size Guide</h1>
                    <span class="fs-pill">[fs_size_guide]</span>
                </div>
                <p>Shortcode informativo para mostrar enlaces oficiales a guías de tallas.</p>
            </div>

            <div class="fs-card">

                <div class="fs-card__header">
                    <h2 class="fs-card__title">Uso</h2>
                </div>

                <div class="fs-snippet">
                    <code>[fs_size_guide]</code>
                </div>

                <p>Pega este shortcode en una página informativa.</p>

                <div class="fs-callout">
                    Diseño minimalista mobile-first con identidad visual del brand.
                </div>

            </div>

        </div>
        <?php
    }
}
