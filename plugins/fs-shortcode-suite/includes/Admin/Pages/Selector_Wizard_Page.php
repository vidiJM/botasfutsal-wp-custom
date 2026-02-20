<?php
declare(strict_types=1);

namespace FS\ShortcodeSuite\Admin\Pages;

if (!defined('ABSPATH')) {
    exit;
}

final class Selector_Wizard_Page
{

    public function render(): void
    {
        ?>
        <div class="wrap fs-admin-wrap">

            <div class="fs-admin-header fs-admin-header--brand">
                <div class="fs-admin-header__title">
                    <h1>Selector Inteligente de Zapatillas</h1>
                    <span class="fs-pill">[fs_selector_wizard]</span>
                </div>
                <p>Usa el siguiente shortcode para mostrar el botón que activa el cuestionario inteligente.</p>
            </div>

            <div class="fs-card">

                <div class="fs-card__header">
                    <h2 class="fs-card__title">Uso</h2>
                </div>
                <pre class="fs-code-box" id="fs-search-output">[fs_selector_wizard]</pre>
                
                <button
                            class="button fs-copy-btn"
                            type="button"
                            data-fs-copy="#fs-shortcode-selector-wizard"
                            data-fs-copy-label="Copiar"
                            data-fs-copy-done="Copiado ✓"
                        >Copiar</button>
                
                <p> El cuestionario mostrará:</p>

                <div class="fs-callout">
                    <ul>
                        <li>1 pregunta por slide</li>
                        <li>Barra de progreso</li>
                        <li>Botón volver atrás</li>
                        <li>Resultado comparativo de 3 productos</li>
                    </ul>
                </div>
            </div>

        </div>
        
        <?php
    }
    
}
