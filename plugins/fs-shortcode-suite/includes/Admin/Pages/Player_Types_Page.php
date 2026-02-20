<?php
declare(strict_types=1);

namespace FS\ShortcodeSuite\Admin\Pages;

defined('ABSPATH') || exit;

final class Player_Types_Page {

    public function render(): void {
        ?>
        <div class="fs-admin-header fs-admin-header--brand">
                <div class="fs-admin-header__title">
                    <h1>Player Types</h1>
                    <span class="fs-pill">[fs_player_types]</span>
                </div>
                <p>Shortcode sección en la Home para mostrar tipos de jugadores.</p>
            </div>

            <div class="fs-card">

                <div class="fs-card__header">
                    <h2 class="fs-card__title">Uso</h2>
                </div>

                <pre class="fs-code-box" id="fs-search-output">[fs_player_types]</pre>
                
                <button
                            class="button fs-copy-btn"
                            type="button"
                            data-fs-copy="#fs-player-types"
                            data-fs-copy-label="Copiar"
                            data-fs-copy-done="Copiado ✓"
                        >Copiar</button>

                <p>Pega este shortcode en una página informativa.</p>

                <div class="fs-callout">
                    Diseño minimalista mobile-first con identidad visual del brand.
                </div>

            </div>
        <?php
    }
}
