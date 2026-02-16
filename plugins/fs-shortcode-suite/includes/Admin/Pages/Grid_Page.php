<?php
declare(strict_types=1);

namespace FS\ShortcodeSuite\Admin\Pages;

defined('ABSPATH') || exit;

final class Grid_Page {

    public function render(): void {
        ?>
        <div class="wrap fs-admin-wrap">
            <div class="fs-admin-header fs-admin-header--brand">
                <div class="fs-admin-header__title">
                    <h1>FS Grid</h1>
                    <span class="fs-pill">[fs_grid]</span>
                </div>
                <p>Generador del shortcode con atributos. Copia y pega en cualquier página o bloque “Shortcode”.</p>
            </div>

            <div class="fs-admin-shell fs-admin-shell--2col">

                <div class="fs-card">
                    <div class="fs-card__header">
                        <h2 class="fs-card__title">Generador de shortcode</h2>
                        <p class="fs-card__subtitle">Rellena solo lo que necesites. Los campos vacíos no se incluyen.</p>
                    </div>

                    <div class="fs-form-grid">
                        <div class="fs-field">
                            <label for="fs-brand">Brand</label>
                            <input type="text" id="fs-brand" placeholder="nike, adidas..." />
                            <p class="fs-help">Slug o lista separada por comas.</p>
                        </div>

                        <div class="fs-field">
                            <label for="fs-color">Color</label>
                            <input type="text" id="fs-color" placeholder="negro, blanco..." />
                            <p class="fs-help">Slug o lista separada por comas.</p>
                        </div>

                        <div class="fs-field">
                            <label for="fs-gender">Gender</label>
                            <input type="text" id="fs-gender" placeholder="hombre, mujer..." />
                            <p class="fs-help">Valores esperados: hombre, mujer, unisex.</p>
                        </div>

                        <div class="fs-field">
                            <label for="fs-age">Age Group</label>
                            <input type="text" id="fs-age" placeholder="adult, infantil..." />
                            <p class="fs-help">Valores esperados: adult, infantil.</p>
                        </div>

                        <div class="fs-field">
                            <label for="fs-size">Size</label>
                            <input type="text" id="fs-size" placeholder="42..." />
                            <p class="fs-help">Talla EU (ej: 42) o lista por comas.</p>
                        </div>

                        <div class="fs-field">
                            <label for="fs-perpage">Per Page</label>
                            <input type="number" id="fs-perpage" value="12" min="1" max="48" />
                            <p class="fs-help">Recomendado: 8–16 para rendimiento.</p>
                        </div>
                    </div>

                    <div class="fs-actions">
                        <button class="button button-primary fs-btn-primary" id="fs-generate" type="button">
                            Generar shortcode
                        </button>
                    </div>

                    <div class="fs-output">
                        <div class="fs-output__top">
                            <label>Shortcode generado</label>
                            <span class="fs-badge fs-badge--muted">Listo para copiar</span>
                        </div>

                        <pre class="fs-code-box" id="fs-output">[fs_grid]</pre>

                        <button
                            class="button fs-copy-btn"
                            id="fs-copy"
                            type="button"
                            data-fs-copy="#fs-output"
                            data-fs-copy-label="Copiar"
                            data-fs-copy-done="Copiado ✓"
                        >Copiar</button>
                    </div>
                </div>

                <div class="fs-card">
                    <div class="fs-card__header">
                        <h2 class="fs-card__title">Manual rápido</h2>
                        <p class="fs-card__subtitle">Uso, ejemplos y recomendaciones de configuración.</p>
                    </div>

                    <div class="fs-doc">
                        <h3>Uso básico</h3>
                        <p>Pega el shortcode en una página o en un bloque “Shortcode”.</p>

                        <div class="fs-snippet">
                            <code>[fs_grid]</code>
                        </div>

                        <h3>Ejemplos</h3>
                        <div class="fs-snippet">
                            <code>[fs_grid brand="nike" per_page="12"]</code>
                        </div>
                        <div class="fs-snippet">
                            <code>[fs_grid gender="hombre" age_group="adult" per_page="8"]</code>
                        </div>
                        <div class="fs-snippet">
                            <code>[fs_grid color="negro,blanco" size="42,43"]</code>
                        </div>

                        <h3>Atributos</h3>
                        <table class="widefat striped fs-table">
                            <thead>
                                <tr>
                                    <th>Atributo</th>
                                    <th>Descripción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td><code>brand</code></td><td>Filtra por marca (slug o lista por comas).</td></tr>
                                <tr><td><code>color</code></td><td>Filtra por color (slug o lista por comas).</td></tr>
                                <tr><td><code>gender</code></td><td>Filtra por género (hombre, mujer, unisex).</td></tr>
                                <tr><td><code>age_group</code></td><td>Filtra por grupo de edad (adult, infantil).</td></tr>
                                <tr><td><code>size</code></td><td>Filtra por talla EU (ej: 42) o lista por comas.</td></tr>
                                <tr><td><code>per_page</code></td><td>Número de items por página (1–48).</td></tr>
                            </tbody>
                        </table>

                        <div class="fs-callout">
                            <strong>Rendimiento:</strong> evita <code>per_page</code> alto en páginas con mucho contenido.
                        </div>
                    </div>
                </div>

            </div>
        </div>
        <?php
    }
}
