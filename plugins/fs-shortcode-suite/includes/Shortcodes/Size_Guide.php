<?php
declare(strict_types=1);

namespace FS\ShortcodeSuite\Shortcodes;

use FS\ShortcodeSuite\Core\Assets;

defined('ABSPATH') || exit;

final class Size_Guide {

    public function __construct() {
        add_shortcode('fs_size_guide', [$this, 'render']);
    }

    public function render(): string {

        Assets::enqueue_size_guide();
    
        ob_start();
        ?>
    
        <section class="fs-size-guide">
    
            <div class="fs-size-guide__inner">
    
                <header class="fs-size-guide__header">
                    <h1>Guía Interactiva de Tallas Futsal</h1>
                    <p>Selecciona tu perfil y consulta equivalencias internacionales.</p>
                </header>
    
                <!-- Selector jugador -->
                <div class="fs-size-section">
                    <h2>Tipo de jugador</h2>
    
                    <div class="fs-player-selector">
                        <button class="fs-player-btn" data-player="explosivo">Explosivo</button>
                        <button class="fs-player-btn" data-player="tecnico">Técnico</button>
                        <button class="fs-player-btn" data-player="defensivo">Defensivo</button>
                    </div>
    
                    <div id="fs-player-recommendation" class="fs-player-recommendation">
                        Selecciona un perfil para ver recomendación.
                    </div>
                </div>
    
                <!-- Tabla tallas -->
                <div class="fs-size-section">
                    <h2>Tabla EU ↔ US ↔ CM</h2>
    
                    <div class="fs-table-wrapper">
                        <table class="fs-size-table">
                            <thead>
                                <tr>
                                    <th>EU</th>
                                    <th>US</th>
                                    <th>CM</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td>39</td><td>6.5</td><td>24.5</td></tr>
                                <tr><td>40</td><td>7</td><td>25</td></tr>
                                <tr><td>41</td><td>8</td><td>26</td></tr>
                                <tr><td>42</td><td>8.5</td><td>26.5</td></tr>
                                <tr><td>43</td><td>9.5</td><td>27.5</td></tr>
                                <tr><td>44</td><td>10</td><td>28</td></tr>
                                <tr><td>45</td><td>11</td><td>29</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- RECOMENDACIONES POR MARCA -->
                <div class="fs-size-section">
                    <h2>Recomendaciones técnicas por marca</h2>
                
                    <div class="fs-size-grid">
                
                        <div class="fs-size-box">
                            <h3>Nike</h3>
                            <p>
                                Horma generalmente estrecha.
                                Ideal para jugadores técnicos o explosivos.
                                Si tienes pie ancho, considera media talla más.
                            </p>
                        </div>
                
                        <div class="fs-size-box">
                            <h3>Adidas</h3>
                            <p>
                                Ajuste fiel a talla.
                                Sensación equilibrada entre comodidad y precisión.
                                Buena opción para jugadores mixtos.
                            </p>
                        </div>
                
                        <div class="fs-size-box">
                            <h3>Joma</h3>
                            <p>
                                Horma más amplia en antepié.
                                Recomendada para pies anchos.
                                Suele tallar ligeramente más cómoda.
                            </p>
                        </div>
                
                        <div class="fs-size-box">
                            <h3>Mizuno</h3>
                            <p>
                                Ajuste técnico y preciso.
                                Excelente para jugadores que buscan sensibilidad y control.
                                Talla bastante fiel.
                            </p>
                        </div>
                
                        <div class="fs-size-box">
                            <h3>Puma</h3>
                            <p>
                                Algunos modelos tallan ligeramente ajustados.
                                Ideal para juego rápido y perfiles dinámicos.
                            </p>
                        </div>
                
                        <div class="fs-size-box">
                            <h3>Kelme</h3>
                            <p>
                                Horma tradicional y cómoda.
                                Buena estabilidad.
                                Opción segura para jugadores defensivos.
                            </p>
                        </div>
                
                    </div>
                </div>
                
                <!-- ENLACES OFICIALES -->
                <div class="fs-size-section">
                    <h2>Guías oficiales por marca</h2>
    
                    <div class="fs-size-guide__brands">
    
                        <a href="https://www.nike.com/es/size-fit/mens-footwear" target="_blank" rel="noopener" class="fs-size-card">
                            <span class="fs-size-card__brand">Nike</span>
                            <span class="fs-size-card__cta">Ver guía oficial →</span>
                        </a>
    
                        <a href="https://www.adidas.es/guia-de-tallas" target="_blank" rel="noopener" class="fs-size-card">
                            <span class="fs-size-card__brand">Adidas</span>
                            <span class="fs-size-card__cta">Ver guía oficial →</span>
                        </a>
    
                        <a href="https://www.mizuno.eu/es/size-guide" target="_blank" rel="noopener" class="fs-size-card">
                            <span class="fs-size-card__brand">Mizuno</span>
                            <span class="fs-size-card__cta">Ver guía oficial →</span>
                        </a>
    
                        <a href="https://www.joma-sport.com/guia-de-tallas" target="_blank" rel="noopener" class="fs-size-card">
                            <span class="fs-size-card__brand">Joma</span>
                            <span class="fs-size-card__cta">Ver guía oficial →</span>
                        </a>
    
                    </div>
                </div>
                
            </div>
        </section>
    
        <?php
        return ob_get_clean();
    }
}
