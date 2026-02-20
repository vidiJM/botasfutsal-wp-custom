<?php
declare(strict_types=1);

namespace FS\ShortcodeSuite\Shortcodes;

use FS\ShortcodeSuite\Core\Assets;

if (!defined('ABSPATH')) {
    exit;
}

final class Selector_Wizard
{
    public function __construct()
    {
        add_shortcode('fs_selector_wizard', [$this, 'render']);
    }

    public function render(): string
    {
        Assets::enqueue_selector_wizard();

        ob_start();
        ?>

        <div class="fs-selector">
            <button class="fs-selector__trigger" data-fs-open>
                Empezar
            </button>
        </div>

        <div class="fs-wizard" data-fs-modal hidden>
            <div class="fs-wizard__overlay" data-fs-close></div>

            <div class="fs-wizard__container">

                <button class="fs-wizard__close" data-fs-close>✕</button>

                <!-- Progress -->
                <div class="fs-wizard__progress">
                    <div class="fs-wizard__progress-bar" data-fs-progress></div>
                </div>

                <!-- Slides -->
                <div class="fs-wizard__slides" data-fs-slides>
                    
                    <!-- Slide 0 -->
                    <div class="fs-wizard__slide active">
                    
                        <h2>¿Para quién son las zapatillas?</h2>
                    
                        <div class="fs-wizard__options">
                            <button data-value="infantil">Infantil</button>
                            <button data-value="hombre">Hombre</button>
                            <button data-value="mujer">Mujer</button>
                        </div>
                    
                    </div>
                    
                    <!-- Slide 1 -->
                    <div class="fs-wizard__slide active">
                        <h2>¿Dónde juegas?</h2>
                        <div class="fs-wizard__options">
                            <button data-value="indoor">Indoor</button>
                            <button data-value="cesped">Césped artificial</button>
                            <button data-value="exterior">Exterior</button>
                        </div>
                    </div>

                    <!-- Slide 2 -->
                    <div class="fs-wizard__slide">
                        <h2>¿Qué valoras más?</h2>
                        <div class="fs-wizard__options">
                            <button data-value="velocidad">Velocidad</button>
                            <button data-value="control">Control</button>
                            <button data-value="resistencia">Resistencia</button>
                            <button data-value="precio">Calidad-precio</button>
                        </div>
                    </div>

                    <!-- Slide 3 -->
                    <div class="fs-wizard__slide">
                        <h2>¿Cuál es tu presupuesto?</h2>
                        <div class="fs-wizard__options">
                            <button data-value="low">Menos de 60€</button>
                            <button data-value="mid">60€ - 100€</button>
                            <button data-value="high">Más de 100€</button>
                        </div>
                    </div>
                    
                    <!-- Slide 5 (Resultados) -->
                    <div class="fs-wizard__slide">
                        <h2>Tus zapatillas ideales</h2>
                    
                        <div class="fs-wizard__results" data-fs-results>
                            <!-- Aquí se inyectarán los 3 productos -->
                        </div>
                    </div>

                </div>

                <!-- Back button -->
                <div class="fs-wizard__footer">
                    <button class="fs-wizard__back" data-fs-back hidden>
                        ← Volver
                    </button>
                </div>

            </div>
        </div>

        <?php
        return (string) ob_get_clean();
    }
}
