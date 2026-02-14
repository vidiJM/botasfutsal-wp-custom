<?php
declare(strict_types=1);

namespace FS\ShortcodeSuite\Admin\Pages;

defined('ABSPATH') || exit;

final class System_Page {

    public function render(): void {

        global $wpdb;

        $memory_limit  = ini_get('memory_limit');
        $php_version   = phpversion();
        $wp_version    = get_bloginfo('version');
        $mysql_version = $wpdb->db_version();

        $cache_enabled = function_exists('wp_cache_get') ? 'Sí' : 'No';
        $rest_enabled  = rest_url() ? 'Sí' : 'No';

        ?>
        <div class="wrap fs-admin-wrap">

            <div class="fs-admin-header fs-admin-header--brand">
                <div class="fs-admin-header__title">
                    <h1>System Information</h1>
                    <span class="fs-badge fs-badge--muted">Diagnóstico</span>
                </div>
                <p>Información técnica del entorno para diagnóstico y soporte.</p>
            </div>

            <div class="fs-admin-shell">
                <div class="fs-card">

                    <div class="fs-card__header">
                        <h2 class="fs-card__title">Entorno del servidor</h2>
                        <p class="fs-card__subtitle">Versiones y capacidades detectadas.</p>
                    </div>

                    <table class="widefat striped fs-table">
                        <tbody>
                            <tr>
                                <td><strong>Plugin Version</strong></td>
                                <td><?php echo esc_html(FS_SC_SUITE_VERSION); ?></td>
                            </tr>
                            <tr>
                                <td><strong>PHP Version</strong></td>
                                <td><?php echo esc_html((string) $php_version); ?></td>
                            </tr>
                            <tr>
                                <td><strong>WordPress Version</strong></td>
                                <td><?php echo esc_html((string) $wp_version); ?></td>
                            </tr>
                            <tr>
                                <td><strong>MySQL Version</strong></td>
                                <td><?php echo esc_html((string) $mysql_version); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Memory Limit</strong></td>
                                <td><?php echo esc_html((string) $memory_limit); ?></td>
                            </tr>
                            <tr>
                                <td><strong>REST API disponible</strong></td>
                                <td><?php echo esc_html($rest_enabled); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Object Cache disponible</strong></td>
                                <td><?php echo esc_html($cache_enabled); ?></td>
                            </tr>
                        </tbody>
                    </table>

                    <hr class="fs-divider">

                    <div class="fs-card__header">
                        <h2 class="fs-card__title">Estado del sistema FS</h2>
                        <p class="fs-card__subtitle">Detección de CPT principales.</p>
                    </div>

                    <table class="widefat striped fs-table">
                        <tbody>
                            <tr>
                                <td><strong>CPT fs_producto</strong></td>
                                <td><?php echo post_type_exists('fs_producto') ? 'Activo' : 'No detectado'; ?></td>
                            </tr>
                            <tr>
                                <td><strong>CPT fs_variante</strong></td>
                                <td><?php echo post_type_exists('fs_variante') ? 'Activo' : 'No detectado'; ?></td>
                            </tr>
                            <tr>
                                <td><strong>CPT fs_oferta</strong></td>
                                <td><?php echo post_type_exists('fs_oferta') ? 'Activo' : 'No detectado'; ?></td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="fs-actions">
                        <button
                            class="button button-primary fs-btn-primary"
                            id="fs-copy-system-info"
                            type="button"
                            data-fs-copy-system="1"
                            data-fs-copy-label="Copiar información"
                            data-fs-copy-done="Copiado ✔">
                            Copiar información
                        </button>
                    </div>

                </div>
            </div>

        </div>
        <?php
    }
}
