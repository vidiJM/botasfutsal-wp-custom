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
                    <h1>FS Selector Wizard</h1>
                    <span class="fs-pill">[fs_selector_wizard]</span>
                </div>
                <p>
                    Shortcode para mostrar un selector guiado paso a paso
                    que ayuda al usuario a encontrar las zapatillas ideales
                    según sus necesidades.
                </p>
            </div>
        
            <div class="fs-admin-shell fs-admin-shell--2col">
        
                <!-- LEFT COLUMN -->
                <div class="fs-card">
                    <div class="fs-card__header">
                        <h2 class="fs-card__title">Uso del shortcode</h2>
                        <p class="fs-card__subtitle">
                            El wizard funciona en cualquier página y guía al usuario
                            mediante filtros progresivos.
                        </p>
                    </div>
        
                    <div class="fs-doc">
        
                        <h3>Uso básico</h3>
                        <p>
                            Inserta el shortcode en cualquier página o bloque del editor.
                        </p>
        
                        <div class="fs-snippet">
                            <code>[fs_selector_wizard]</code>
                        </div>
        
                        <div class="fs-callout">
                            <strong>Recomendado:</strong>
                            Usarlo en una página específica tipo
                            <code>/elige-tus-botas</code>
                            para mejorar la conversión.
                        </div>
        
                        <h3>Qué hace</h3>
                        <ul>
                            <li>✔ Guía al usuario paso a paso</li>
                            <li>✔ Filtra por género, superficie y otras taxonomías</li>
                            <li>✔ Reduce fricción en la decisión</li>
                            <li>✔ Mejora experiencia en móvil</li>
                            <li>✔ Redirige al archivo filtrado automáticamente</li>
                        </ul>
        
                        <h3>Flujo típico</h3>
                        <p>Ejemplo de navegación:</p>
                        <ul>
                            <li>1️⃣ Selección de género</li>
                            <li>2️⃣ Selección de superficie</li>
                            <li>3️⃣ Opcional: marca o rango de precio</li>
                            <li>4️⃣ Redirección al listado filtrado</li>
                        </ul>
        
                    </div>
                </div>
        
                <!-- RIGHT COLUMN -->
                <div class="fs-card">
                    <div class="fs-card__header">
                        <h2 class="fs-card__title">Requisitos técnicos</h2>
                        <p class="fs-card__subtitle">
                            El sistema depende del modelo offer-driven del proyecto.
                        </p>
                    </div>
        
                    <div class="fs-doc">
        
                        <h3>Estructura necesaria</h3>
        
                        <table class="widefat striped fs-table">
                            <thead>
                                <tr>
                                    <th>Entidad</th>
                                    <th>Requisito</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><code>fs_producto</code></td>
                                    <td>Debe tener variantes y ofertas válidas</td>
                                </tr>
                                <tr>
                                    <td><code>fs_variante</code></td>
                                    <td>Debe tener género y superficie asignados</td>
                                </tr>
                                <tr>
                                    <td><code>fs_oferta</code></td>
                                    <td>Debe estar en stock (<code>fs_in_stock = true</code>)</td>
                                </tr>
                            </tbody>
                        </table>
        
                        <h3>Optimización</h3>
                        <div class="fs-callout">
                            <strong>Rendimiento:</strong>
                            El wizard no ejecuta consultas pesadas.
                            Solo construye parámetros y redirige al archivo optimizado.
                        </div>
        
                        <h3>Recomendación</h3>
                        <p>
                            Ideal para usuarios indecisos o tráfico frío.
                            Mejora la conversión guiando la decisión en lugar
                            de mostrar directamente el catálogo completo.
                        </p>
        
                    </div>
                </div>
        
            </div>
        </div>
        
        <?php
    }
    
}
