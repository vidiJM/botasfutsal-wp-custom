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

                <!-- ================= HEADER ================= -->

                <header class="fs-size-guide__header">
                    <h1>Guía Profesional de Tallas Fútbol Sala</h1>
                    <p>Calcula tu talla exacta según tu tipo de juego, pie y marca.</p>
                </header>

                <!-- ================= WIZARD ================= -->

                <div class="fs-size-wizard">

                    <!-- PASO 1 -->
                    <div class="fs-step">
                        <div class="fs-step__title">
                            1. Tipo de jugador
                        </div>

                        <div class="fs-player-selector">
                            <button type="button" class="fs-player-btn" data-player="explosivo">Explosivo</button>
                            <button type="button" class="fs-player-btn" data-player="tecnico">Técnico</button>
                            <button type="button" class="fs-player-btn" data-player="defensivo">Defensivo</button>
                        </div>
                    </div>

                    <!-- PASO 2 -->
                    <div class="fs-step">
                        <div class="fs-step__title">
                            2. Tipo de pie
                        </div>

                        <div class="fs-player-selector">
                            <button type="button" class="fs-foot-btn" data-foot="estrecho">Estrecho</button>
                            <button type="button" class="fs-foot-btn" data-foot="normal">Normal</button>
                            <button type="button" class="fs-foot-btn" data-foot="ancho">Ancho</button>
                        </div>
                    </div>

                    <!-- PASO 3 -->
                    <div class="fs-step">
                        <div class="fs-step__title">
                            3. Longitud del pie (cm)
                        </div>

                        <div class="fs-calc-inputs">
                            <input 
                                type="number" 
                                step="0.1" 
                                min="19" 
                                max="30" 
                                id="fs-foot-cm" 
                                placeholder="Ej: 26.5"
                            >
                            <button type="button" id="fs-calc-btn">
                                Calcular talla
                            </button>
                        </div>

                        <div id="fs-calc-result" class="fs-calc-result"></div>
                    </div>

                    <!-- PASO 4 (OPCIONAL) -->
                    <div class="fs-step">
                        <div class="fs-step__title">
                            4. Marca (opcional)
                        </div>

                        <div class="fs-player-selector">
                            <button type="button" class="fs-brand-btn" data-brand="nike">Nike</button>
                            <button type="button" class="fs-brand-btn" data-brand="adidas">Adidas</button>
                            <button type="button" class="fs-brand-btn" data-brand="joma">Joma</button>
                            <button type="button" class="fs-brand-btn" data-brand="mizuno">Mizuno</button>
                            <button type="button" class="fs-brand-btn" data-brand="puma">Puma</button>
                            <button type="button" class="fs-brand-btn" data-brand="kelme">Kelme</button>
                        </div>
                    </div>

                    <!-- RECOMENDACIÓN DINÁMICA -->
                    <div id="fs-player-recommendation" class="fs-player-recommendation"></div>

                    <!-- RESULTADO FINAL -->
                    <div id="fs-final-result" class="fs-result-box"></div>

                </div>

                <!-- ================= TABLA REFERENCIA ================= -->

                <div class="fs-size-section fs-size-section--table">

                    <h2>Tabla de equivalencias EU ↔ US ↔ CM</h2>

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
                                <tr><td>32</td><td>1</td><td>20</td></tr>
                                <tr><td>33</td><td>1.5</td><td>20.5</td></tr>
                                <tr><td>34</td><td>2</td><td>21.5</td></tr>
                                <tr><td>35</td><td>3</td><td>22</td></tr>
                                <tr><td>36</td><td>4</td><td>23</td></tr>
                                <tr><td>37</td><td>4.5</td><td>23.5</td></tr>
                                <tr><td>38</td><td>5.5</td><td>24</td></tr>
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

                <!-- ================= RECOMENDACIONES POR MARCA ================= -->

                <div class="fs-size-section fs-size-section--brands">

                    <h2>Recomendaciones técnicas por marca</h2>

                    <div class="fs-size-grid">

                        <div class="fs-size-box">
                            <h3>Nike</h3>
                            <p>Horma estrecha. Ajuste ceñido. Si tienes pie ancho considera media talla más.</p>
                        </div>

                        <div class="fs-size-box">
                            <h3>Adidas</h3>
                            <p>Ajuste fiel a talla. Equilibrio entre precisión y comodidad.</p>
                        </div>

                        <div class="fs-size-box">
                            <h3>Joma</h3>
                            <p>Horma amplia. Ideal para pies anchos. Sensación más cómoda.</p>
                        </div>

                        <div class="fs-size-box">
                            <h3>Mizuno</h3>
                            <p>Ajuste técnico y preciso. Gran sensibilidad de balón.</p>
                        </div>

                        <div class="fs-size-box">
                            <h3>Puma</h3>
                            <p>Algunos modelos ajustan ligeramente pequeño.</p>
                        </div>

                        <div class="fs-size-box">
                            <h3>Kelme</h3>
                            <p>Horma tradicional y estable. Buena opción defensiva.</p>
                        </div>

                    </div>

                </div>

                <!-- ================= GUÍAS OFICIALES ================= -->

                <div class="fs-size-section fs-size-section--official">

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