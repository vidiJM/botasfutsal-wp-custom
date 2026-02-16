<?php
declare(strict_types=1);

namespace FS\ShortcodeSuite\Admin\Pages;

defined('ABSPATH') || exit;

final class Search_Page {

    public function render(): void {
        ?>
        <div class="wrap fs-admin-wrap">
            
            <div class="fs-admin-header fs-admin-header--brand">
                <div class="fs-admin-header__title">
                    <h1>FS Search</h1>
                    <span class="fs-pill">[fs_search]</span>
                </div>
                <p>Búsqueda fullscreen con overlay y autocompletado vía REST. Inserta el shortcode donde quieras el trigger.</p>
            </div>

            <div class="fs-admin-shell fs-admin-shell--2col">

                <div class="fs-card">
                    <div class="fs-card__header">
                        <h2 class="fs-card__title">Shortcode</h2>
                        <p class="fs-card__subtitle">Este shortcode no requiere atributos.</p>
                    </div>

                    <div class="fs-output">
                        <div class="fs-output__top">
                            <label>Shortcode</label>
                            <span class="fs-badge fs-badge--muted">Listo para copiar</span>
                        </div>

                        <pre class="fs-code-box" id="fs-search-output">[fs_search]</pre>

                        <button
                            class="button fs-copy-btn"
                            type="button"
                            data-fs-copy="#fs-search-output"
                            data-fs-copy-label="Copiar"
                            data-fs-copy-done="Copiado ✓"
                        >Copiar</button>
                    </div>

                    <div class="fs-callout">
                        <strong>Nota:</strong> los assets de búsqueda se encolan solo cuando el shortcode está presente.
                    </div>
                </div>

                <div class="fs-card">
                    <div class="fs-card__header">
                        <h2 class="fs-card__title">Manual rápido</h2>
                        <p class="fs-card__subtitle">Uso y consideraciones de configuración.</p>
                    </div>

                    <div class="fs-doc">
                        <h3>Uso</h3>
                        <p>Pega <code>[fs_search]</code> donde quieras el botón trigger (header, sección superior, etc.).</p>

                        <h3>Comportamiento</h3>
                        <ul class="fs-list">
                            <li>Abre un overlay fullscreen con input de búsqueda.</li>
                            <li>Consulta el endpoint REST de búsqueda para autocompletar resultados.</li>
                            <li>Optimizado para mobile-first.</li>
                        </ul>

                        <h3>Recomendaciones</h3>
                        <div class="fs-callout">
                            <strong>UX:</strong> en desktop suele funcionar mejor junto al grid. En móvil, ideal en el header.
                        </div>
                    </div>
                </div>

            </div>
        </div>
        <?php
    }
}
