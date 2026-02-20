<?php
declare(strict_types=1);

namespace FS\ShortcodeSuite\Admin\Pages;

defined('ABSPATH') || exit;

final class Product_Detail_Page {

    public function render(): void {
        ?>
        <div class="wrap fs-admin-wrap">
            <div class="fs-admin-header fs-admin-header--brand">
                <div class="fs-admin-header__title">
                    <h1>FS Product Detail</h1>
                    <span class="fs-pill">[fs_product_detail]</span>
                </div>
                <p>
                    Shortcode para mostrar el detalle dinámico completo de un producto
                    (colores, tallas, precio y botón de tienda).
                </p>
            </div>

            <div class="fs-admin-shell fs-admin-shell--2col">

                <!-- LEFT COLUMN -->
                <div class="fs-card">
                    <div class="fs-card__header">
                        <h2 class="fs-card__title">Uso del shortcode</h2>
                        <p class="fs-card__subtitle">
                            Este shortcode no necesita atributos. Detecta automáticamente
                            el producto actual en la plantilla single.
                        </p>
                    </div>

                    <div class="fs-doc">

                        <h3>Uso básico</h3>
                        <p>
                            Inserta el shortcode en la plantilla de
                            <code>single-fs_producto.php</code>
                            o en el editor de bloques del producto.
                        </p>

                        <div class="fs-snippet">
                            <code>[fs_product_detail]</code>
                        </div>

                        <div class="fs-callout">
                            <strong>Importante:</strong>
                            Solo funciona en páginas individuales del CPT
                            <code>fs_producto</code>.
                        </div>

                        <h3>Qué muestra</h3>
                        <ul>
                            <li>✔ Galería del color seleccionado</li>
                            <li>✔ Selector dinámico de colores</li>
                            <li>✔ Tallas disponibles en stock</li>
                            <li>✔ Mejor precio por color</li>
                            <li>✔ Botón dinámico hacia la mejor oferta</li>
                        </ul>

                        <h3>Comportamiento dinámico</h3>
                        <p>
                            Al cambiar el color:
                        </p>
                        <ul>
                            <li>Se actualiza la imagen principal</li>
                            <li>Se actualizan las tallas disponibles</li>
                            <li>Se recalcula el mejor precio</li>
                            <li>Se actualiza la URL de la tienda</li>
                        </ul>

                    </div>
                </div>

                <!-- RIGHT COLUMN -->
                <div class="fs-card">
                    <div class="fs-card__header">
                        <h2 class="fs-card__title">Requisitos técnicos</h2>
                        <p class="fs-card__subtitle">
                            El producto debe estar correctamente relacionado con sus variantes y ofertas.
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
                                    <td>Debe tener <code>fs_product_id</code> definido</td>
                                </tr>
                                <tr>
                                    <td><code>fs_variante</code></td>
                                    <td>Debe estar vinculada por <code>fs_product_id</code></td>
                                </tr>
                                <tr>
                                    <td><code>fs_color</code></td>
                                    <td>Asignado a cada variante</td>
                                </tr>
                                <tr>
                                    <td><code>fs_oferta</code></td>
                                    <td>Debe tener <code>fs_in_stock = true</code></td>
                                </tr>
                            </tbody>
                        </table>

                        <h3>Optimización</h3>
                        <div class="fs-callout">
                            <strong>Rendimiento:</strong>
                            El sistema carga todas las variantes y ofertas del producto en
                            solo 2 consultas optimizadas.
                            No utiliza AJAX ni recargas.
                        </div>

                        <h3>Recomendación</h3>
                        <p>
                            Usa este shortcode únicamente en plantillas de producto.
                            No se recomienda usarlo en páginas generales o listados.
                        </p>

                    </div>
                </div>

            </div>
        </div>
        <?php
    }
}
